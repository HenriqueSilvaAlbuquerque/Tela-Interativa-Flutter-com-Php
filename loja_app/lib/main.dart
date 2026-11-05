import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

// Chrome + Laragon:           http://localhost/loja
// Emulador Android + Laragon: http://10.0.2.2/loja
const baseUrl = 'http://localhost/loja';

const verde = Color(0xFF1B6B4F);
const cinza = Color(0xFF5E5C57);

void main() => runApp(MaterialApp(
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorSchemeSeed: verde,
        scaffoldBackgroundColor: const Color(0xFFF7F6F3),
      ),
      home: const Loja(),
    ));

class Produto {
  final int id, quantidade;
  final String nome;
  final double valor;

  Produto.fromJson(Map j)
      : id = j['id'],
        nome = j['nome'],
        quantidade = j['quantidade'],
        valor = (j['valor'] as num).toDouble();
}

String brl(double v) {
  final p = v.toStringAsFixed(2).split('.');
  final inteiro = p[0].replaceAllMapped(RegExp(r'\B(?=(\d{3})+$)'), (_) => '.');
  return 'R\$ $inteiro,${p[1]}';
}

Widget linha(String a, String b, {bool negrito = false}) => Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: DefaultTextStyle.merge(
        style: TextStyle(fontWeight: negrito ? FontWeight.bold : FontWeight.normal),
        child: Row(children: [Expanded(child: Text(a)), Text(b)]),
      ),
    );

class Loja extends StatefulWidget {
  const Loja({super.key});

  @override
  State<Loja> createState() => _LojaState();
}

class _LojaState extends State<Loja> {
  List<Produto>? lista;
  String? erro;
  late final AppLifecycleListener ciclo;
  final carrinho = <int, int>{};

  @override
  void initState() {
    super.initState();
    atualizar();
    // Ao voltar para o app (ou para a aba do Chrome), busca o estoque de novo.
    ciclo = AppLifecycleListener(onResume: atualizar);
  }

  @override
  void dispose() {
    ciclo.dispose();
    super.dispose();
  }

  // Busca o estoque e mantém o carrinho, limitando-o ao que ainda existe.
  Future<void> atualizar() async {
    try {
      final r = await http.get(Uri.parse('$baseUrl/api/henriqueapi.php'));
      final novos = (json.decode(r.body) as List).map((j) => Produto.fromJson(j)).toList();
      if (!mounted) return;
      setState(() {
        lista = novos;
        erro = null;
        for (final p in novos) {
          if ((carrinho[p.id] ?? 0) > p.quantidade) carrinho[p.id] = p.quantidade;
        }
      });
    } catch (e) {
      if (mounted) setState(() => erro = '$e');
    }
  }

  void mudar(Produto p, int d) {
    final novo = (carrinho[p.id] ?? 0) + d;
    if (novo >= 0 && novo <= p.quantidade) setState(() => carrinho[p.id] = novo);
  }

  Future<void> finalizar(double total) async {
    final itens = lista!.where((p) => (carrinho[p.id] ?? 0) > 0).toList();

    final confirmou = await showDialog<bool>(
      context: context,
      builder: (c) => AlertDialog(
        title: const Text('Confirmar compra?'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          for (final p in itens) linha('${carrinho[p.id]}× ${p.nome}', brl(carrinho[p.id]! * p.valor)),
          const Divider(),
          linha('Total', brl(total), negrito: true),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(c, true), child: const Text('Confirmar')),
        ],
      ),
    );
    if (confirmou != true) return;

    final r = await http.post(
      Uri.parse('$baseUrl/api/henriquecompra.php'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode([for (final p in itens) {'id': p.id, 'qtd': carrinho[p.id]}]),
    );
    if (!mounted) return;

    final ok = r.statusCode == 200;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(ok ? 'Compra realizada!' : json.decode(r.body)['erro']),
    ));
    if (ok) carrinho.clear();
    atualizar();
  }

  Widget card(Produto p) {
    final q = carrinho[p.id] ?? 0;
    return Card(
      elevation: 0,
      color: Colors.white,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: const BorderSide(color: Color(0xFFE3E1DC)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(p.nome, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500)),
              Text(p.quantidade > 0 ? 'Disponível: ${p.quantidade} un.' : 'Sem estoque',
                  style: const TextStyle(color: cinza)),
              Text(brl(p.valor),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: verde)),
            ]),
          ),
          if (p.quantidade == 0)
            const Chip(label: Text('Esgotado'))
          else ...[
            IconButton.outlined(
              onPressed: q > 0 ? () => mudar(p, -1) : null,
              icon: const Icon(Icons.remove),
              style: IconButton.styleFrom(
                foregroundColor: verde,
                side: const BorderSide(color: verde, width: 2),
              ),
            ),
            SizedBox(
              width: 28,
              child: Text('$q', textAlign: TextAlign.center, style: const TextStyle(fontSize: 16)),
            ),
            IconButton.filled(
              onPressed: q < p.quantidade ? () => mudar(p, 1) : null,
              icon: const Icon(Icons.add),
              style: IconButton.styleFrom(backgroundColor: verde),
            ),
          ],
        ]),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final appBar = AppBar(title: const Text('Loja'), backgroundColor: Colors.white);

    if (lista == null) {
      return Scaffold(
        appBar: appBar,
        body: Center(
          child: erro == null
              ? const CircularProgressIndicator()
              : Column(mainAxisSize: MainAxisSize.min, children: [
                  Text('Erro: $erro', textAlign: TextAlign.center),
                  TextButton(onPressed: atualizar, child: const Text('Tentar de novo')),
                ]),
        ),
      );
    }

    final itens = carrinho.values.fold(0, (a, b) => a + b);
    final total = lista!.fold(0.0, (t, p) => t + (carrinho[p.id] ?? 0) * p.valor);

    return Scaffold(
      appBar: appBar,
      body: Column(children: [
        // A lista continua visível se a conexão cair.
        if (erro != null)
          Container(
            width: double.infinity,
            color: const Color(0xFFF8E5E0),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            child: Row(children: [
              const Expanded(child: Text('Sem conexão com a API.')),
              TextButton(onPressed: atualizar, child: const Text('Tentar de novo')),
            ]),
          ),
        Expanded(
          child: ListView(padding: const EdgeInsets.all(16), children: lista!.map(card).toList()),
        ),
      ]),
      bottomNavigationBar: Container(
        color: Colors.white,
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
        child: SafeArea(
          child: Row(children: [
            Expanded(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('$itens ${itens == 1 ? 'item' : 'itens'} no carrinho', style: const TextStyle(color: cinza)),
                  Text(brl(total), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
            FilledButton(
              onPressed: itens > 0 ? () => finalizar(total) : null,
              style: FilledButton.styleFrom(backgroundColor: verde, minimumSize: const Size(0, 48)),
              child: const Text('Finalizar compra'),
            ),
          ]),
        ),
      ),
    );
  }
}
