<?php
declare(strict_types=1);

use GameShelf\Core\Logger;
use GameShelf\Services\AuthService;
use Tests\Fakes\FakeUsuarioRepository;

function novoServicoAuth(?FakeUsuarioRepository &$repo = null, ?string &$arquivoLog = null): AuthService
{
    $repo = new FakeUsuarioRepository();
    $arquivoLog = tempnam(sys_get_temp_dir(), 'gs-log-');
    return new AuthService($repo, new Logger($arquivoLog));
}

T::teste('AuthService: cadastro guarda hash seguro (nunca a senha) e perfil "usuario"', function () {
    $s = novoServicoAuth($repo);
    $u = $s->registrar(['nome' => 'Ana', 'email' => 'Ana@Exemplo.com', 'senha' => 'Senha1234']);
    $linha = $repo->porEmail('ana@exemplo.com');
    T::verdadeiro($linha !== null, 'e-mail deveria ser normalizado para minúsculas');
    T::verdadeiro($linha['senha_hash'] !== 'Senha1234');
    T::verdadeiro(password_verify('Senha1234', $linha['senha_hash']));
    T::verdadeiro(str_starts_with($linha['senha_hash'], '$argon2id$') || str_starts_with($linha['senha_hash'], '$2y$'));
    T::igual('usuario', $u['perfil']);
});

T::teste('AuthService: tentativa de se cadastrar como admin (mass assignment) é ignorada', function () {
    $s = novoServicoAuth($repo);
    $s->registrar(['nome' => 'Mal', 'email' => 'mal@x.com', 'senha' => 'Senha1234', 'perfil' => 'admin']);
    T::igual('usuario', $repo->porEmail('mal@x.com')['perfil']);
});

T::teste('AuthService: e-mail duplicado gera 409 e entrada inválida gera 422', function () {
    $s = novoServicoAuth($repo);
    $dados = ['nome' => 'Ana', 'email' => 'ana@x.com', 'senha' => 'Senha1234'];
    $s->registrar($dados);
    T::lanca(409, fn () => $s->registrar($dados));
    T::lanca(422, fn () => $s->registrar(['nome' => 'A', 'email' => 'nao-e-email', 'senha' => '123']));
});

T::teste('AuthService: login correto devolve o usuário sem o hash e registra login_ok', function () {
    $s = novoServicoAuth($repo, $log);
    $s->registrar(['nome' => 'Ana', 'email' => 'ana@x.com', 'senha' => 'Senha1234']);
    $u = $s->login(['email' => 'ana@x.com', 'senha' => 'Senha1234'], '127.0.0.1');
    T::verdadeiro(!isset($u['senha_hash']), 'hash não pode vazar');
    T::verdadeiro(str_contains((string) file_get_contents($log), 'login_ok'));
});

T::teste('AuthService: senha errada e e-mail inexistente dão a MESMA resposta 401 e registram login_falha', function () {
    $s = novoServicoAuth($repo, $log);
    $s->registrar(['nome' => 'Ana', 'email' => 'ana@x.com', 'senha' => 'Senha1234']);
    $a = T::lanca(401, fn () => $s->login(['email' => 'ana@x.com', 'senha' => 'Errada999'], '127.0.0.1'));
    $b = T::lanca(401, fn () => $s->login(['email' => 'ninguem@x.com', 'senha' => 'Errada999'], '127.0.0.1'));
    T::igual($a->getMessage(), $b->getMessage());
    $conteudo = (string) file_get_contents($log);
    T::verdadeiro(str_contains($conteudo, 'login_falha'));
    T::verdadeiro(!str_contains($conteudo, 'Errada999'), 'senha não pode aparecer no log');
    T::verdadeiro((bool) preg_match('/^\[\d{4}-\d{2}-\d{2}T/m', $conteudo), 'log precisa de timestamp');
});

T::teste('AuthService: após 5 falhas o login é bloqueado com 429 (mesmo com a senha certa)', function () {
    $s = novoServicoAuth($repo);
    $s->registrar(['nome' => 'Ana', 'email' => 'ana@x.com', 'senha' => 'Senha1234']);
    for ($i = 0; $i < AuthService::MAX_FALHAS; $i++) {
        T::lanca(401, fn () => $s->login(['email' => 'ana@x.com', 'senha' => 'Errada999'], '10.0.0.1'));
    }
    T::lanca(429, fn () => $s->login(['email' => 'ana@x.com', 'senha' => 'Senha1234'], '10.0.0.1'));
});

T::teste('AuthService: payload de SQL injection no e-mail é rejeitado antes de qualquer consulta', function () {
    $s = novoServicoAuth($repo);
    T::lanca(422, fn () => $s->login(['email' => "' OR '1'='1' -- ", 'senha' => 'qualquer1'], '127.0.0.1'));
});
