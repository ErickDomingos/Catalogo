<?php
declare(strict_types=1);

use GameShelf\Controllers\AuthController;
use GameShelf\Controllers\JogoController;
use GameShelf\Controllers\ListaController;
use GameShelf\Controllers\UsuarioController;
use GameShelf\Core\Router;

/** Tabela de rotas: método, caminho (sem /api), handler e nível de acesso. */
return static function (Router $r, AuthController $auth, JogoController $jogos, ListaController $listas, UsuarioController $usuarios): void {
    $r->adicionar('GET', '/csrf', $auth->csrf(...));
    $r->adicionar('POST', '/auth/registro', $auth->registro(...));
    $r->adicionar('POST', '/auth/login', $auth->login(...));
    $r->adicionar('POST', '/auth/logout', $auth->logout(...), 'usuario');
    $r->adicionar('GET', '/auth/me', $auth->eu(...), 'usuario');

    $r->adicionar('GET', '/generos', $jogos->generos(...));
    $r->adicionar('GET', '/jogos', $jogos->listar(...));
    $r->adicionar('GET', '/jogos/{id}', $jogos->obter(...));
    $r->adicionar('POST', '/jogos', $jogos->criar(...), 'usuario');
    $r->adicionar('PUT', '/jogos/{id}', $jogos->atualizar(...), 'usuario');
    $r->adicionar('DELETE', '/jogos/{id}', $jogos->excluir(...), 'usuario');

    $r->adicionar('GET', '/listas', $listas->listar(...), 'usuario');
    $r->adicionar('PUT', '/listas/{jogoId}', $listas->definir(...), 'usuario');
    $r->adicionar('DELETE', '/listas/{jogoId}', $listas->remover(...), 'usuario');

    $r->adicionar('GET', '/usuarios', $usuarios->listar(...), 'admin');
    $r->adicionar('GET', '/usuarios/{id}/jogos', $jogos->doUsuario(...), 'admin');
    $r->adicionar('PATCH', '/usuarios/{id}', $usuarios->alterarPerfil(...), 'admin');
    $r->adicionar('DELETE', '/usuarios/{id}', $usuarios->excluir(...), 'admin');
};
