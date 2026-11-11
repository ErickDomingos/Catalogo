# Testes

## 1) Unitários — 32 testes, sem banco
`cd backend && php tests/run.php` (saída 0 = tudo passou). Cobrem casos válidos e inválidos de: `Validator` (7), `JogoService` (10), `AuthService` (7), `Router/Csrf/Response` (8), usando repositórios em memória (`tests/fakes`).
Evidência: `php tests/run.php | tee ../docs/evidencias/testes-unitarios.txt`

## 2) Segurança + integração — HTTP real e banco real
```bash
cd backend
php -S localhost:8000 -t ../frontend router.php      # terminal 1
php tests/integration/seguranca.php | tee ../docs/evidencias/seguranca.txt   # terminal 2
```
Verifica: SQL Injection (busca, DROP TABLE, login, rota), XSS armazenado e refletido, acesso sem login, perfil insuficiente, CSRF, sessão antiga/forjada após logout, mass assignment, rate limiting, CRUD completo e (com admin no `.env`) fluxo de admin.
Limpeza dos usuários de teste: `DELETE FROM usuarios WHERE email LIKE 'teste+%@gameshelf.local';`

## 3) Logs (amostra)
`backend/storage/logs/app.log` — ex.: `[2026-11-04T10:15:02-03:00] WARNING login_falha {"email":"...","ip":"127.0.0.1"}`. Eventos: `login_ok`, `login_falha`, `login_bloqueado`, `logout`, `usuario_criado`, `jogo_criado/atualizado/excluido`, `usuario_perfil_alterado/excluido`, `erro_interno`.
Para gerar `erro_interno` em desenvolvimento: pare o MySQL e abra `/api/jogos` (resposta 500 com `debug`); com `APP_ENV=production` o `debug` some.

## 4) Desempenho (para a seção 12 do relatório)
Exemplo: `curl -s -o /dev/null -w "%{time_total}\n" "http://localhost:8000/api/jogos?por_pagina=12"` repetido 20x (ou `ab -n 200 -c 10 URL`). Anote média e máximo.
