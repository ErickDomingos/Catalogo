# Checklist de requisitos — Trabalho Individual II

Marque `[x]` só depois de verificar no seu computador.

**Requisitos obrigatórios**
- [ ] 1 Arquitetura em camadas documentada (`docs/ARQUITETURA.md`)
- [ ] 2 Entidade persistente + CRUD + consultas parametrizadas + constraints + script SQL (`backend/database/schema.sql`)
- [ ] 3 Autenticação por sessão, hash Argon2id/bcrypt, logout, rotas protegidas
- [ ] 4 Dois perfis (usuario/admin) e matriz de permissões (`docs/SEGURANCA.md`)
- [ ] 5 Validação/sanitização no backend com erros estruturados
- [ ] 6 Proteção: SQL Injection, XSS, CSRF, acesso não autorizado (+ evidências)
- [ ] 7 Erros padronizados (status, mensagem, detalhes só em desenvolvimento)
- [ ] 8 Documentação da API (`docs/API.md`)
- [ ] 9 ≥ 5 testes automatizados (32 unitários) com casos válidos e inválidos
- [ ] 10 Logs com timestamp: login ok, falha, criação, erro interno

**Entrega**
- [ ] Código-fonte · [ ] script SQL · [ ] relatório PDF (14 seções) · [ ] link do Git · [ ] **vídeo** (ausência: −10)
- [ ] Evidências: SQL, API, testes (`docs/evidencias/`), capturas de segurança, amostra de logs, `git log --oneline --graph`, este checklist
- [ ] Declaração de IA (`docs/USO-DE-IA-T2.md`) · [ ] `.env` fora do Git · [ ] sistema roda localmente

**Bônus (+5 cada, teto 100)**
- [x] `.env` · [ ] migrações · [x] paginação · [x] rate limiting · [x] testes de integração
