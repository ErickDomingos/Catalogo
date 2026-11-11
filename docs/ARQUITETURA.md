# Arquitetura

Estilo: **arquitetura em camadas** (MVC simplificado, sem views no servidor) com **inversão de dependência** nos repositórios (hexagonal simplificada). O servidor só entrega JSON; as telas são HTML estático com JavaScript que consome a API.

```
Navegador (frontend/: HTML + JS, fetch)
   │  JSON + cookie de sessão + X-CSRF-Token
   ▼
backend/index.php  → Request → Router (guardas: publica | usuario | admin, CSRF)
   ▼
Controllers   (HTTP: lê a Request, devolve a Response)
   ▼
Services      (regras de negócio: validação, permissões, duplicidade, logs)
   ▼
Repositories  (interfaces + implementação PDO: único lugar com SQL)
   ▼
MySQL/MariaDB (backend/database/schema.sql)
```

| Camada | Pasta | Responsabilidade |
|---|---|---|
| Interface | `frontend/` | telas e chamadas `fetch` (`js/api.js`) |
| Lógica de aplicação | `backend/src/Controllers`, `Services` | casos de uso e regras |
| Acesso a dados | `backend/src/Repositories` | SQL parametrizado (PDO, prepared statements nativos) |
| Infraestrutura | `backend/src/Core` | Router, Request/Response, Validator, Auth, Csrf, Session, Logger, Env, ErroHandler |

## Decisões

- Configuração em `.env`, fora do Git.
- Erros tratados em um só ponto (`ErroHandler`), no formato único `{erro:{status,codigo,mensagem,campos}}`; `debug` só em desenvolvimento.
- Os `Services` dependem de **interfaces** de repositório, então as regras são testadas com repositórios em memória (`tests/fakes`), sem banco.
- Sem dependências externas: autoloader PSR-4 próprio em `src/autoload.php`.
- Servidor de desenvolvimento: `php -S ... backend\router.php` serve `frontend/` como estático e envia `/api/*` para `backend/index.php`. Em Apache ou Nginx, aponte `/api` para `backend/index.php` (front controller) e o resto para `frontend/`.

## Frontend e fonte única de dados

| Arquivo | Função |
|---|---|
| `js/api.js` | cliente da API: cookie de sessão e token CSRF renovado automaticamente |
| `js/acervo.js` | página do catálogo: carrega jogos pessoais e listas da API; cadastra, exclui e marca listas |
| `js/catalogo.js` | busca, filtros e ordenação sobre os cartões da página |
| `js/conta.js` | página Minha conta: CRUD de jogos e listas |
| `js/admin.js` | gestão de usuários (somente admin) |
| `js/entrar.js` | login e cadastro |
| `js/totais.js` | resumo "N títulos · M gêneros" da página inicial, vindo do banco |
| `js/validacao.js`, `js/formulario.js` | validação de formulários no navegador (UX; a validação de verdade é a do backend) |

O catálogo tem 128 fichas escritas no HTML (herança do Trabalho 1, visíveis mesmo sem JavaScript). Cada ficha tem `id="base-<slug>"`, e o `slug` é o mesmo da coluna `jogos.slug` do banco. Ao carregar:

1. `acervo.js` busca o catálogo oficial no banco (`GET /api/jogos?escopo=oficiais`, público).
2. Fichas do HTML sem registro no banco (excluídas pelo administrador) são removidas da página; jogos oficiais criados pelo administrador e ausentes do HTML são desenhados pelo JavaScript.
3. Se houver sessão, os jogos pessoais (`escopo=meus`) e o estado das listas (`minha_lista`) são carregados; sem sessão, a página é somente leitura.
4. Qualquer mudança (cadastro, exclusão, lista) vai à API antes de aparecer na tela. Por isso **catálogo e Minha conta enxergam sempre o mesmo banco**.

Se a API estiver fora do ar, o catálogo continua legível com as fichas do HTML e avisa que cadastro e listas estão indisponíveis.
