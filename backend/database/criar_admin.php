<?php
declare(strict_types=1);

/**
 * Cria (ou promove) o administrador usando ADMIN_NOME/ADMIN_EMAIL/ADMIN_SENHA do .env.
 * Assim nenhuma credencial fica no repositório.   Uso:  php database/criar_admin.php
 */

use GameShelf\Core\{Database, Env, Validator};
use GameShelf\Repositories\UsuarioRepository;
use GameShelf\Services\AuthService;

require __DIR__ . '/../src/autoload.php';
Env::carregar(__DIR__ . '/../.env');

$v = new Validator([
    'nome' => Env::get('ADMIN_NOME') ?: 'Administrador',
    'email' => Env::get('ADMIN_EMAIL'),
    'senha' => Env::get('ADMIN_SENHA'),
]);
$nome = $v->texto('nome', 'ADMIN_NOME', 2, 80);
$email = $v->email('email', 'ADMIN_EMAIL');
$senha = $v->senha('senha', 'ADMIN_SENHA');
if ($v->falhou()) {
    fwrite(STDERR, "Configuração inválida no .env:\n");
    foreach ($v->erros() as $campo => $msg) {
        fwrite(STDERR, " - {$msg}\n");
    }
    exit(1);
}

$repo = new UsuarioRepository(Database::conexao());
$existente = $repo->porEmail((string) $email);
if ($existente !== null) {
    $repo->atualizarPerfil((int) $existente['id'], 'admin');
    echo "Usuário {$email} já existia e agora é admin.\n";
} else {
    $repo->criar((string) $nome, (string) $email, password_hash((string) $senha, AuthService::algoritmo()), 'admin');
    echo "Administrador {$email} criado.\n";
}
