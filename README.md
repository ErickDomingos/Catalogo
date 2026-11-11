# GameShelf — Trabalho Individual II (SCOM)

Catálogo de 128 jogos (Trabalho 1) evoluído para um sistema cliente-servidor: **PHP 8.1+ puro (sem Composer) + MySQL/MariaDB**, com autenticação por sessão, dois perfis de acesso, validação, logs e testes automatizados.

## Estrutura
```
frontend/   site do Trabalho 1 (HTML/CSS/JS) + telas novas: entrar, conta, admin
  docs/FONTES.md, README.md   materiais do Trabalho 1
backend/    API PHP (index.php, router.php, src/, database/, tests/, storage/logs/)
docs/       Trabalho 2: arquitetura, API, segurança, testes, relatório, checklist, uso de IA, roteiro Git
```

## Como executar
Requisitos: PHP 8.1+ (extensões `pdo_mysql` e `mbstring`) e MySQL 8 / MariaDB 10.4+ (por exemplo, o XAMPP).

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

# 4. Servidor de desenvolvimento
php -S localhost:8000 -t frontend backend\router.php
```
Abra http://localhost:8000 (catálogo) e http://localhost:8000/entrar.html (login e cadastro).

## Testes
```powershell
php backend\tests\run.php                              # 32 testes unitários (sem banco)
php backend\tests\integration\seguranca.php            # segurança e integração (servidor rodando)
```

Detalhes e como gerar as evidências: `docs/TESTES.md`. Documentação: `docs/ARQUITETURA.md`, `docs/API.md`, `docs/SEGURANCA.md`.
