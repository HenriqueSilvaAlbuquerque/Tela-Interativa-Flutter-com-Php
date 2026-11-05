# Loja – Flutter + API PHP

App de loja em Flutter que lista produtos (nome, quantidade em estoque e valor) de uma API PHP com MySQL. A compra é confirmada num pop-up e desconta do estoque. Na loja web, o botão "Ajustar estoque" permite adicionar ou remover unidades; a página de cadastro inclui produtos novos.

## Estrutura

```
loja/                      → vai em C:\laragon\www\loja
  index.php                loja web (compra e ajuste de estoque)
  henriquefrontend.php     cadastro de produtos
  config.exemplo.php       modelo do config.php (o config.php não vai para o Git)
  api/
    henriqueapi.php        GET  – lista os produtos em JSON
    henriquecompra.php     POST – recebe [{"id":1,"qtd":2}] e desconta do estoque
    henriqueestoque.php    POST – recebe {"id":1,"qtd":5} (adiciona) ou {"id":1,"qtd":-3} (remove)
  includes/conexao.php     conexão com o banco
  css/estilo.css           visual das páginas
  sql/produtos.sql         tabela e produtos de exemplo
loja_app/                  app Flutter (código em lib/main.dart)
```

## Instalar no Laragon (primeira vez)

Depois de clonar o repositório, deixe a pasta `loja` acessível em `C:\laragon\www\loja`: copie a pasta para lá ou crie um atalho (junction) no PowerShell:

```
New-Item -ItemType Junction -Path C:\laragon\www\loja -Target <pasta do repositório>\loja
```

Depois, no Terminal do Laragon (o PowerShell não aceita o `<`):

```
cd C:\laragon\www\loja
copy config.exemplo.php config.php
mysql -u root -e "CREATE DATABASE loja CHARACTER SET utf8mb4"
mysql -u root --default-character-set=utf8mb4 loja < sql\produtos.sql
```

Teste: `http://localhost/loja/api/henriqueapi.php` deve mostrar os produtos em JSON.

## App Flutter

```
cd loja_app
flutter pub get
flutter run -d chrome
```

O endereço da API fica na constante `baseUrl`, no topo do `main.dart`:

| Onde roda o app | baseUrl |
|---|---|
| Chrome + Laragon | `http://localhost/loja` |
| Emulador Android + Laragon | `http://10.0.2.2/loja` |

## Problemas comuns

| Erro | Causa |
|---|---|
| `Failed to fetch` no app | API fora do ar ou `baseUrl` errado. Abra a URL da API no navegador. |
| `config.php não encontrado` | Falta copiar o `config.exemplo.php` para `config.php`. |
| `Unknown database 'loja'` | Falta criar e importar o banco. |
| Acentos como `mecÃ¢nico` | Importação sem `--default-character-set=utf8mb4`. |
