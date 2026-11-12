# Checklist de requisitos — Trabalho Individual II

Itens marcados `[x]` foram verificados no código e nas execuções dos testes (35 unitários e 16 de integração passando). Os demais dependem de você.

**Requisitos obrigatórios**
- [x] 1 Arquitetura em camadas documentada (`docs/ARQUITETURA.md`)
- [x] 2 Entidade persistente + CRUD + consultas parametrizadas + constraints + script SQL (`backend/database/schema.sql`)
- [x] 3 Autenticação por sessão, hash Argon2id/bcrypt, logout, rotas protegidas
- [x] 4 Dois perfis (usuario/admin) e matriz de permissões (`docs/SEGURANCA.md`)
- [x] 5 Validação/sanitização no backend com erros estruturados
- [x] 6 Proteção: SQL Injection, XSS, CSRF, acesso não autorizado (+ evidências)
- [x] 7 Erros padronizados (status, mensagem, detalhes só em desenvolvimento)
- [x] 8 Documentação da API (`docs/API.md`)
- [x] 9 ≥ 5 testes automatizados (35 unitários) com casos válidos e inválidos
- [x] 10 Logs com timestamp: login ok, falha, criação, erro interno

**Entrega**
- [x] Código-fonte · [x] script SQL (`backend/database/schema.sql`)
- [ ] relatório PDF (14 seções) · [ ] link do repositório Git · [ ] **vídeo** (ausência: −10)
- [ ] Evidências em `docs/evidencias/`: [ ] testes regenerados em UTF-8 · [ ] capturas de segurança · [ ] amostra de logs · [ ] `git log --oneline --graph`
- [x] Declaração de IA (`docs/USO-DE-IA-T2.md`) · [x] `.env` fora do Git · [ ] confirmar que o zip de entrega não leva `backend/.env` nem `app.log`
- [ ] sistema roda localmente (siga o README em uma pasta limpa antes de entregar)

**Bônus (+5 cada, teto 100)**
- [x] `.env` · [ ] migrações · [x] paginação · [x] rate limiting · [x] testes de integração
