# Testes

## 1) Unitários — 35 testes, sem banco
```powershell
php backend\tests\run.php
```
Saída 0 = tudo passou. Cobrem casos válidos e inválidos de `Validator` (7), `JogoService` (11), `UsuarioService` (2), `AuthService` (7) e `Router`/`Csrf`/`Response` (8), usando repositórios em memória (`tests/fakes`).

## 2) Segurança e integração — 16 verificações, HTTP e banco reais
Com o servidor ligado em outro terminal (`php -S localhost:8000 -t frontend backend\router.php`):
```powershell
php backend\tests\integration\seguranca.php
```
Verifica: catálogo oficial com 128 jogos (pré-condição), SQL Injection (busca, DROP TABLE, login e rota), XSS armazenado e refletido, acesso sem login (401), perfil insuficiente (403), CSRF, sessão antiga e forjada após o logout, mass assignment de perfil, limite de tentativas de login (429), CRUD completo com lista pessoal, consistência entre telas (jogo e lista criados aparecem em `escopo=meus` e em `/api/listas`; a API expõe o `slug`) e, com administrador no `.env`, o fluxo de administração.

A pré-condição espera 128 jogos oficiais. Se um jogo oficial foi excluído, reponha com `php backend\database\seed.php`.

Limpeza dos usuários de teste: `DELETE FROM usuarios WHERE email LIKE 'teste+%@gameshelf.local';`

## 3) Como gerar as evidências em UTF-8 (PowerShell)
```powershell
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
php backend\tests\run.php | Out-File -Encoding utf8 docs\evidencias\testes-unitarios.txt
php backend\tests\integration\seguranca.php | Out-File -Encoding utf8 docs\evidencias\seguranca.txt
git log --oneline --graph --all | Out-File -Encoding utf8 docs\evidencias\git-log.txt
Get-Content backend\storage\logs\app.log -Tail 30 | Out-File -Encoding utf8 docs\evidencias\amostra-logs.txt
```
Antes de salvar a amostra de logs, confira se não há e-mails reais. As capturas de tela dos testes de segurança vão em `docs/evidencias/capturas/`.

## 4) Roteiro manual (navegador, Ctrl+F5 em cada página)

**Visitante**
1. O catálogo mostra as fichas oficiais e o aviso de visitante; não há formulário de cadastro nem filtro de lista.
2. Abrir `conta.html` ou `admin.html` sem login não mostra dados.

**Usuário**
3. Cadastrar um jogo em `catalogo.html` e conferir em `conta.html`; cadastrar outro em `conta.html` e conferir no catálogo.
4. Marcar uma lista num jogo oficial em uma página e conferir na outra.
5. Excluir um jogo seu em uma página e conferir que sumiu da outra.
6. No console do navegador, `fetch('/api/usuarios').then(r => r.status)` deve retornar 403.

**Administrador**
7. Cadastrar um jogo em `conta.html`: nasce como oficial e aparece no catálogo e na página inicial (contagem de títulos).
8. Excluí-lo em `conta.html`: some do catálogo, mesmo em janela anônima, e a contagem da página inicial diminui.
9. Em `admin.html`: listar usuários, mudar perfil, tentar excluir a si mesmo e rebaixar o único admin (ambos recusados).

**Segurança**
10. Buscar `' OR '1'='1` no catálogo: nenhum resultado. Cadastrar título `<img src=x onerror=alert(1)>`: nenhum alerta, tags removidas.
11. Após o logout, o botão Voltar não mostra dados da conta.

## 5) Logs
`backend/storage/logs/app.log`, uma linha por evento, com timestamp: `[2026-11-09T18:50:39-03:00] WARNING login_falha {"email":"...","ip":"::1"}`.
Eventos: `login_ok`, `login_falha`, `login_bloqueado`, `logout`, `usuario_criado`, `jogo_criado`, `jogo_atualizado`, `jogo_excluido`, `usuario_perfil_alterado`, `usuario_excluido`, `erro_interno`.
Para gerar `erro_interno` em desenvolvimento: pare o MySQL e abra `/api/jogos` (resposta 500 com `debug`); com `APP_ENV=production` o `debug` some.

## 6) Desempenho (seção 12 do relatório)
```powershell
1..20 | ForEach-Object { (Measure-Command { Invoke-WebRequest -UseBasicParsing "http://localhost:8000/api/jogos?por_pagina=12" | Out-Null }).TotalMilliseconds }
```
Anote média e máximo em milissegundos. Repita para `/api/jogos?escopo=oficiais&por_pagina=50`.
