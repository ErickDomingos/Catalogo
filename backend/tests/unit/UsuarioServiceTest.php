<?php
declare(strict_types=1);

use GameShelf\Core\Logger;
use GameShelf\Services\UsuarioService;
use Tests\Fakes\FakeUsuarioRepository;

function novoServicoUsuario(?FakeUsuarioRepository &$repo = null): UsuarioService
{
    $repo = new FakeUsuarioRepository();
    return new UsuarioService($repo, new Logger(tempnam(sys_get_temp_dir(), 'gs-log-')), 'padrao@x.com');
}

T::teste('UsuarioService: o administrador padrão não pode ser excluído nem rebaixado (409)', function () {
    $s = novoServicoUsuario($repo);
    $padrao = $repo->criar('Padrão', 'padrao@x.com', 'hash', 'admin');
    $outro = $repo->porId($repo->criar('Outro Admin', 'outro@x.com', 'hash', 'admin'));
    T::lanca(409, fn () => $s->excluir($outro, $padrao));
    T::lanca(409, fn () => $s->alterarPerfil($outro, $padrao, ['perfil' => 'usuario']));
    T::verdadeiro($repo->porId($padrao) !== null, 'o administrador padrão deveria continuar existindo');
    T::igual('admin', $repo->porId($padrao)['perfil']);
});

T::teste('UsuarioService: listagem marca o administrador padrão como protegido; usuário comum é excluído normalmente', function () {
    $s = novoServicoUsuario($repo);
    $padraoId = $repo->criar('Padrão', 'PADRAO@x.com', 'hash', 'admin');
    $comumId = $repo->criar('Ana', 'ana@x.com', 'hash', 'usuario');
    $lista = $s->listar([])['dados'];
    T::igual(true, $lista[0]['protegido']);
    T::igual(false, $lista[1]['protegido']);
    $s->excluir($repo->porId($padraoId), $comumId);
    T::igual(null, $repo->porId($comumId));
});
