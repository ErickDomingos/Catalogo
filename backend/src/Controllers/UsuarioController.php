<?php
declare(strict_types=1);

namespace GameShelf\Controllers;

use GameShelf\Core\Auth;
use GameShelf\Core\Request;
use GameShelf\Core\Response;
use GameShelf\Services\UsuarioService;

final class UsuarioController
{
    public function __construct(
        private readonly UsuarioService $servico,
        private readonly Auth $auth,
    ) {
    }

    public function listar(Request $req, array $params): Response
    {
        return Response::json($this->servico->listar($req->query));
    }

    public function alterarPerfil(Request $req, array $params): Response
    {
        $this->servico->alterarPerfil((array) $this->auth->usuario(), Request::id($params), $req->corpo);
        return Response::vazio();
    }

    public function excluir(Request $req, array $params): Response
    {
        $this->servico->excluir((array) $this->auth->usuario(), Request::id($params));
        return Response::vazio();
    }
}
