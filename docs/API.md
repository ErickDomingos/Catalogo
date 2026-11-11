# Documentação da API

Base: `http://localhost:8000/api` · JSON (`Content-Type: application/json`) · sessão por cookie `gameshelf_sid` (HttpOnly, SameSite=Lax).
**CSRF:** toda requisição `POST/PUT/PATCH/DELETE` exige o cabeçalho `X-CSRF-Token` obtido em `GET /api/csrf` (o token muda após login/logout).
**Acesso:** P = pública · U = usuário logado · A = admin.

**Erros (formato único):**
```json
{"erro":{"status":422,"codigo":"validacao","mensagem":"Dados inválidos.","campos":{"titulo":"Título é obrigatório(a)."}}}
```
`debug` (tipo, mensagem, local) aparece só com `APP_ENV=development` e apenas em erros 500.

**Objeto jogo:** `id, titulo, estudio, ano, genero, console, plataformas[pc|playstation|xbox|switch], duracao, metacritic(0-100), comunidade(0-10), resenha, capa, oficial, pode_editar, minha_lista`.

| Método | URL | Acesso | Parâmetros | Sucesso | Erros |
|---|---|---|---|---|---|
| GET | `/csrf` | P | — | 200 `{"csrf_token":"..."}` | — |
| POST | `/auth/registro` | P | corpo: `nome`(2-80), `email`, `senha`(8-72, letra+número) | 201 `{"usuario":{...}}` (já logado) | 403, 409 e-mail repetido, 422 |
| POST | `/auth/login` | P | corpo: `email`, `senha` | 200 `{"usuario":{...}}` | 401, 403, 422, 429 |
| POST | `/auth/logout` | U | — | 204 | 401, 403 |
| GET | `/auth/me` | U | — | 200 `{"usuario":{...}}` | 401 |
| GET | `/generos` | P | — | 200 `{"generos":[...]}` | — |
| GET | `/jogos` | P | query: `busca`, `genero`, `plataforma`, `escopo`(todos/meus/oficiais), `ordem`(titulo/ano/nota/duracao), `direcao`, `pagina`, `por_pagina`(≤50) | 200 `{"dados":[...],"paginacao":{...}}` | 401 (escopo=meus), 422 |
| GET | `/jogos/{id}` | P | — | 200 `{"jogo":{...}}` | 404 |
| POST | `/jogos` | U | corpo: `titulo`*, `genero`*, `plataformas[]` ou `console`, `estudio`, `ano`, `duracao`, `metacritic`, `comunidade`, `resenha` | 201 `{"jogo":{...}}` | 401, 403, 409 título repetido, 422 |
| PUT | `/jogos/{id}` | U | mesmo corpo do POST (substituição) | 200 `{"jogo":{...}}` | 401, 403 (oficial/alheio), 404, 409, 422 |
| DELETE | `/jogos/{id}` | U | — | 204 | 401, 403, 404 |
| GET | `/listas` | U | — | 200 `{"itens":[...],"contagens":{"quero":0,...}}` | 401 |
| PUT | `/listas/{jogoId}` | U | corpo: `estado` (quero/jogando/joguei/zerado) | 204 | 401, 403, 404, 422 |
| DELETE | `/listas/{jogoId}` | U | — | 204 | 401, 403 |
| GET | `/usuarios` | A | query: `pagina`, `por_pagina` | 200 `{"dados":[...],"paginacao":{...}}` | 401, 403 |
| PATCH | `/usuarios/{id}` | A | corpo: `perfil` (usuario/admin) | 204 | 401, 403, 404, 409 (último admin), 422 |
| DELETE | `/usuarios/{id}` | A | — | 204 | 401, 403, 404, 409 (a si mesmo) |

O objeto `jogo` inclui `slug` (único). Para os 128 jogos do catálogo ele é o mesmo do HTML (`id="base-<slug>"`), e é por ele que a página Catálogo liga cada cartão ao registro do banco.

Outros: 400 JSON inválido · 404 rota inexistente · 405 método não permitido (cabeçalho `Allow`) · 413 corpo > 64 KB · 415 sem `application/json` · 500 erro interno.

**Exemplo — criar jogo**
```http
POST /api/jogos
X-CSRF-Token: <token>
Content-Type: application/json

{"titulo":"Hades","genero":"Roguelike","plataformas":["pc","switch"],"ano":2020,"duracao":22,"metacritic":93}
```
```json
201 {"jogo":{"id":129,"slug":"jogo-3f9a1c0b7d2e","titulo":"Hades","estudio":null,"ano":2020,"genero":"Roguelike","console":null,"duracao":22,"metacritic":93,"comunidade":null,"resenha":null,"capa":null,"plataformas":["pc","switch"],"minha_lista":null,"oficial":false,"pode_editar":true}}
```
**Exemplo — erro de validação**
```json
422 {"erro":{"status":422,"codigo":"validacao","mensagem":"Dados inválidos.","campos":{"ano":"Ano deve estar entre 1950 e 2100."}}}
```
