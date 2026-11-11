# Segurança e controle de acesso

## Perfis e matriz de permissões
| Ação | Visitante | Usuário | Admin |
|---|:-:|:-:|:-:|
| Ver catálogo oficial | ✔ | ✔ | ✔ |
| Ver jogos privados | — | só os seus | todos |
| Cadastrar jogo | — | privado (seu) | oficial |
| Editar/excluir jogo | — | só os seus | qualquer |
| Editar/excluir jogo oficial | — | — | ✔ |
| Gerenciar listas pessoais | — | ✔ | ✔ |
| Listar/alterar/excluir usuários | — | — | ✔ |

## Proteções
| Ameaça | Medida | Onde |
|---|---|---|
| SQL Injection | PDO com prepared statements nativos (`EMULATE_PREPARES=false`); ordenação por whitelist; ids validados como inteiros | `Repositories/*`, `Request::id` |
| XSS armazenado | `strip_tags` + remoção de caracteres de controle na entrada; front usa `textContent` | `Validator::limpar`, `js/*.js` |
| XSS refletido | respostas só em JSON com `JSON_HEX_TAG/AMP/APOS/QUOT`, `nosniff`, CSP `default-src 'none'`; entrada inválida nunca é refletida na mensagem | `Response` |
| CSRF | token por sessão em `X-CSRF-Token` + checagem de `Origin`; cookie `SameSite=Lax` | `Csrf`, `Router` |
| Acesso não autorizado | guardas `usuario`/`admin` no roteador; perfil relido do banco a cada requisição; jogo privado alheio responde 404 | `Router`, `Auth`, `JogoService` |
| Senhas | `password_hash` Argon2id (ou bcrypt), rehash automático; mensagem de erro genérica e tempo igualado | `AuthService` |
| Sessão | cookie HttpOnly/SameSite, modo estrito, `session_regenerate_id` no login, destruição no logout, expira após 30 min ociosos | `Session`, `Auth` |
| Força bruta | 5 falhas por e-mail+IP em 15 min → 429 | `AuthService`, tabela `login_falhas` |
| Mass assignment | `perfil` nunca vem da entrada pública | `AuthService::registrar` |
| Segredos | `.env` fora do Git; admin criado por script a partir do `.env` | `Env`, `criar_admin.php` |
| Log injection | contexto serializado em JSON; senhas nunca logadas | `Logger` |

Limitações conhecidas: sem HTTPS no ambiente local (use `SESSION_SECURE=true` em produção); CSP só nas respostas da API (as páginas HTML estáticas não enviam cabeçalhos de segurança).
