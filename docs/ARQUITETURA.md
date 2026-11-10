# Arquitetura

Estilo: **arquitetura em camadas** (MVC simplificado sem views no servidor) com **inversão de dependência** nos repositórios (hexagonal simplificada).

```
Navegador (frontend/: HTML + JS, fetch)
   │  JSON + cookie de sessão + X-CSRF-Token
   ▼
backend/index.php  → Request → Router (guardas: publica | usuario | admin, CSRF)
   ▼
Controllers   (HTTP: lê Request, devolve Response)
   ▼
Services      (regras de negócio: validação, permissões, duplicidade, logs)
   ▼
Repositories  (interfaces + implementação PDO: único lugar com SQL)
   ▼
MySQL/MariaDB (database/schema.sql)
```

| Camada | Pasta | Responsabilidade |
|---|---|---|
| Interface | `frontend/` | telas e chamadas `fetch` (`js/api.js`) |
| Lógica de aplicação | `backend/src/Controllers`, `Services` | casos de uso e regras |
| Acesso a dados | `backend/src/Repositories` | SQL parametrizado |
| Infraestrutura | `backend/src/Core` | Router, Request/Response, Validator, Auth, Csrf, Session, Logger, Env, ErroHandler |

Decisões: configuração em `.env` (fora do Git); erros tratados em um só ponto (`ErroHandler`); `Services` dependem de **interfaces** de repositório, o que permite testar as regras com repositórios em memória (`tests/fakes`); sem dependências externas (autoloader PSR-4 próprio em `src/autoload.php`).

Servidor: `php -S ... router.php` serve `frontend/` como estático e envia `/api/*` para `backend/index.php`. Em Apache/Nginx, aponte `/api` para `backend/index.php` (front controller) e o resto para `frontend/`.
