<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loja</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <header>
        <div>
            <h1>Loja</h1>
            <p class="suave" id="contagem">Carregando produtos…</p>
        </div>
        <div class="menu">
            <button class="secundario" id="modo">Ajustar estoque</button>
            <a class="link" href="henriquefrontend.php">Cadastrar produto</a>
        </div>
    </header>

    <main>
        <p class="aviso" id="aviso" hidden></p>
        <div id="lista"></div>
    </main>

    <footer id="rodape">
        <div>
            <span class="suave" id="itens">0 itens no carrinho</span>
            <span id="total">R$ 0,00</span>
        </div>
        <button class="primario" id="finalizar" disabled>Finalizar compra</button>
    </footer>

    <dialog id="confirmar">
        <h2>Confirmar compra?</h2>
        <p class="suave">Confira os itens antes de finalizar.</p>
        <div id="resumo"></div>
        <div class="acoes">
            <button class="texto" id="cancelar">Cancelar</button>
            <button class="primario" id="ok">Confirmar</button>
        </div>
    </dialog>

    <script>
        const brl = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const $ = id => document.getElementById(id);
        let produtos = [], carrinho = {}, estoque = false;

        // Busca o estoque e mantém o carrinho, limitando-o ao que ainda existe.
        // Roda ao abrir, depois de cada ação e ao voltar para esta aba ou janela.
        async function atualizar() {
            if ($('confirmar').open) return;
            let novos;
            try {
                novos = await (await fetch('api/henriqueapi.php')).json();
            } catch {
                avisar('Sem conexão com a API.', true);
                $('aviso').dataset.conexao = '1';
                return;
            }
            if ($('aviso').dataset.conexao) {
                $('aviso').hidden = true;
                delete $('aviso').dataset.conexao;
            }
            if (JSON.stringify(novos) === JSON.stringify(produtos)) return;
            produtos = novos;
            produtos.forEach(p => { if (carrinho[p.id] > p.quantidade) carrinho[p.id] = p.quantidade; });
            $('contagem').textContent = produtos.length + ' produtos';
            render();
        }

        function avisar(texto, erro) {
            $('aviso').textContent = texto;
            $('aviso').className = 'aviso' + (erro ? ' erro' : '');
            $('aviso').hidden = false;
        }

        async function postar(url, dados) {
            const r = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            return { ok: r.ok, ...(await r.json()) };
        }

        function acaoCompra(p) {
            const q = carrinho[p.id] || 0;
            if (p.quantidade === 0) return '<span class="esgotado">Esgotado</span>';
            return `<div class="stepper">
                      <button class="redondo menos" aria-label="Diminuir" onclick="mudar(${p.id}, -1)" ${q === 0 ? 'disabled' : ''}>−</button>
                      <span>${q}</span>
                      <button class="redondo mais" aria-label="Aumentar" onclick="mudar(${p.id}, 1)" ${q >= p.quantidade ? 'disabled' : ''}>+</button>
                    </div>`;
        }

        function acaoEstoque(p) {
            return `<div class="repor">
                      <input type="number" id="q${p.id}" min="1" value="1" aria-label="Unidades de ${p.nome}">
                      <button class="secundario" onclick="ajustar(${p.id}, -1)" ${p.quantidade === 0 ? 'disabled' : ''}>Remover</button>
                      <button class="primario" onclick="ajustar(${p.id}, 1)">Adicionar</button>
                    </div>`;
        }

        function render() {
            $('modo').textContent = estoque ? 'Voltar à loja' : 'Ajustar estoque';
            $('rodape').hidden = estoque;

            // Guarda o que foi digitado nos campos para não perder ao atualizar.
            const digitado = {};
            document.querySelectorAll('#lista input').forEach(i => digitado[i.id] = i.value);
            const foco = document.activeElement?.id;

            $('lista').innerHTML = produtos.map(p => `
                <article class="card">
                  <div class="info">
                    <h2>${p.nome}</h2>
                    <span class="suave">${p.quantidade > 0 ? 'Disponível: ' + p.quantidade + ' un.' : 'Sem estoque'}</span>
                    <span class="preco">${brl(p.valor)}</span>
                  </div>${estoque ? acaoEstoque(p) : acaoCompra(p)}
                </article>`).join('');

            document.querySelectorAll('#lista input').forEach(i => { if (i.id in digitado) i.value = digitado[i.id]; });
            if (foco && $(foco)) $(foco).focus();

            const itens = Object.values(carrinho).reduce((a, b) => a + b, 0);
            const total = produtos.reduce((s, p) => s + (carrinho[p.id] || 0) * p.valor, 0);
            $('itens').textContent = itens + (itens === 1 ? ' item' : ' itens') + ' no carrinho';
            $('total').textContent = brl(total);
            $('finalizar').disabled = itens === 0;
        }

        function mudar(id, d) {
            const p = produtos.find(p => p.id === id);
            carrinho[id] = Math.min(p.quantidade, Math.max(0, (carrinho[id] || 0) + d));
            render();
        }

        async function ajustar(id, sinal) {
            const qtd = parseInt($('q' + id).value, 10);
            if (!(qtd > 0)) return avisar('Informe uma quantidade maior que zero.', true);
            const r = await postar('api/henriqueestoque.php', { id, qtd: qtd * sinal });
            avisar(r.ok ? (sinal > 0 ? 'Unidades adicionadas ao estoque!' : 'Unidades removidas do estoque!') : r.erro, !r.ok);
            if (r.ok) $('q' + id).value = 1;
            atualizar();
        }

        $('modo').onclick = () => {
            estoque = !estoque;
            carrinho = {};
            $('aviso').hidden = true;
            render();
        };

        $('finalizar').onclick = () => {
            const sel = produtos.filter(p => carrinho[p.id] > 0);
            const total = sel.reduce((s, p) => s + carrinho[p.id] * p.valor, 0);
            $('resumo').innerHTML = sel.map(p =>
                `<div class="linha"><span>${carrinho[p.id]}× ${p.nome}</span><span>${brl(carrinho[p.id] * p.valor)}</span></div>`
            ).join('') + `<div class="linha total"><span>Total</span><span>${brl(total)}</span></div>`;
            $('confirmar').showModal();
        };

        $('cancelar').onclick = () => $('confirmar').close();

        $('ok').onclick = async () => {
            const itens = Object.entries(carrinho).filter(([, q]) => q > 0).map(([id, qtd]) => ({ id: +id, qtd }));
            const r = await postar('api/henriquecompra.php', itens);
            $('confirmar').close();
            avisar(r.ok ? 'Compra realizada!' : r.erro, !r.ok);
            if (r.ok) carrinho = {};
            atualizar();
        };

        atualizar();
        window.addEventListener('focus', atualizar);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) atualizar(); });
    </script>
</body>
</html>
