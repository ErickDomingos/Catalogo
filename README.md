# GameShelf — Trabalho Individual II (SCOM)

Catálogo de 128 jogos (Trabalho 1) evoluído para um sistema cliente-servidor: **PHP 8.1+ puro (sem Composer) + MySQL/MariaDB**, com autenticação por sessão, dois perfis de acesso (usuário e administrador), validação e sanitização no backend, proteções contra SQL Injection, XSS e CSRF, logs com data e hora e testes automatizados.

Autor: Érick Domingos da Silva — Unesp, Instituto de Ciência e Tecnologia (Sorocaba).

## O que o sistema faz

- **Visitante:** consulta o catálogo oficial (busca, filtros, ordenação), sem login.
- **Usuário:** cadastra jogos privados, edita e exclui os seus, organiza listas (Quero jogar, Jogando, Já joguei, Zerado). Catálogo e "Minha conta" leem e gravam no mesmo banco, então o que se faz em uma página aparece na outra.
- **Administrador:** tudo do usuário, mais gestão do catálogo oficial (criar, editar e excluir jogos oficiais) e gestão de usuários (listar, mudar perfil, excluir).

## Estrutura

```
frontend/   site do Trabalho 1 (HTML/CSS/JS) + telas do Trabalho 2: entrar, conta, admin
backend/    API PHP: index.php, router.php, src/ (Core, Controllers, Services, Repositories),
            database/ (schema.sql, seed.php, criar_admin.php, jogos.json), tests/, storage/logs/
docs/       arquitetura, API, segurança, testes, checklist, declaração de uso de IA e evidências
```

## Como executar

Requisitos: PHP 8.1+ (extensões `pdo_mysql` e `mbstring`) e MySQL 8 ou MariaDB 10.4+ (por exemplo, o XAMPP). Os comandos abaixo são para o PowerShell, na pasta raiz do projeto.

**Primeira vez**

```powershell
# 1. Crie o usuário do banco (uma vez)
mysql -u root -p
#   CREATE USER 'gameshelf'@'localhost' IDENTIFIED BY 'SUA_SENHA_DO_BANCO';
#   GRANT ALL PRIVILEGES ON gameshelf.* TO 'gameshelf'@'localhost';

# 2. Configure o ambiente
copy backend\.env.example backend\.env      # preencha DB_USER, DB_PASS, ADMIN_EMAIL e ADMIN_SENHA

# 3. Banco, dados e administrador
mysql -u root -p --default-character-set=utf8mb4 -e "source backend/database/schema.sql"
php backend\database\seed.php
php backend\database\criar_admin.php
```

**Sempre que for usar**

```powershell
php -S localhost:8000 -t frontend backend\router.php
```

Abra http://localhost:8000 (início e catálogo) e http://localhost:8000/entrar.html (login e cadastro). O administrador é o e-mail e a senha definidos em `ADMIN_EMAIL` e `ADMIN_SENHA` no `.env`.

> O `backend/.env` guarda senhas e **nunca** vai para o Git nem para o arquivo de entrega. Use `backend/.env.example` como modelo.

## Testes

```powershell
php backend\tests\run.php                       # 32 testes unitários (não precisam de banco)
php backend\tests\integration\seguranca.php     # 16 verificações de segurança e integração (servidor ligado)
```

Os testes de integração criam usuários descartáveis (`teste+...@gameshelf.local`). Para limpar:
`DELETE FROM usuarios WHERE email LIKE 'teste+%@gameshelf.local';`

O roteiro de testes manuais (visitante, usuário e administrador) e como gerar as evidências estão em `docs/TESTES.md`.

## Problemas comuns

| Sintoma | Causa e solução |
|---|---|
| Aviso "O banco está sem jogos do catálogo" | O seed não rodou: `php backend\database\seed.php`. |
| Erro 500 ao abrir o catálogo | MySQL desligado ou `.env` com usuário/senha errados. |
| Um jogo excluído pelo admin voltou | O `seed.php` repõe todo jogo oficial que estiver faltando. Não rode o seed depois de excluir de propósito. |
| Login recusado com a senha certa | 5 falhas seguidas bloqueiam e-mail + IP por 15 minutos (HTTP 429). |
| Foi deslogado sozinho | A sessão expira após 30 minutos sem uso. |
| Página parece desatualizada | Cache do navegador: Ctrl+F5. |

## Documentação

| Arquivo | Conteúdo |
|---|---|
| `docs/ARQUITETURA.md` | Camadas, pastas, fluxo de dados entre catálogo e banco |
| `docs/API.md` | Todos os endpoints, parâmetros, exemplos e códigos de status |
| `docs/SEGURANCA.md` | Matriz de permissões e proteções implementadas |
| `docs/TESTES.md` | Estratégia de testes, roteiro manual e como gerar evidências |
| `docs/CHECKLIST-T2.md` | Checklist dos requisitos do enunciado |
| `docs/USO-DE-IA-T2.md` | Declaração do uso de IA generativa |
| `docs/evidencias/` | Saída dos testes, amostra de logs e histórico Git |
| `frontend/README.md` | Detalhes do frontend |
| `frontend/docs/FONTES.md` | Fontes e créditos do catálogo (Trabalho 1) |

## Limitações conhecidas

- Sem HTTPS no ambiente local (em produção, `SESSION_SECURE=true`).
- Cabeçalhos de segurança (CSP) só nas respostas da API; as páginas HTML estáticas não os enviam.
- Usuário comum não exclui nem oculta jogos oficiais; só o administrador. No Trabalho 1 a remoção ficava só no navegador (`localStorage`) e foi retirada para não divergir do banco.
- O formulário "Sugerir um jogo" continua sendo uma demonstração sem envio.
- Sem migrações de banco: o esquema é recriado por `schema.sql`.
