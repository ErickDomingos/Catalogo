<?php
declare(strict_types=1);

namespace GameShelf\Controllers;

use GameShelf\Core\Auth;
use GameShelf\Core\Request;
use GameShelf\Core\Response;
use GameShelf\Services\JogoService;

final class JogoController
{
    public function __construct(
        private readonly JogoService $servico,
        private readonly Auth $auth,
    ) {
    }

    public function listar(Request $req, array $params): Response
    {
        return Response::json($this->servico->listar($this->auth->usuario(), $req->query));
    }

    public function obter(Request $req, array $params): Response
    {
        return Response::json(['jogo' => $this->servico->obter($this->auth->usuario(), Request::id($params))]);
    }

    public function criar(Request $req, array $params): Response
    {
        return Response::json(['jogo' => $this->servico->criar((array) $this->auth->usuario(), $req->corpo)], 201);
    }

    public function atualizar(Request $req, array $params): Response
    {
        return Response::json(['jogo' => $this->servico->atualizar((array) $this->auth->usuario(), Request::id($params), $req->corpo)]);
    }

    public function excluir(Request $req, array $params): Response
    {
        $this->servico->excluir((array) $this->auth->usuario(), Request::id($params));
        return Response::vazio();
    }

    public function generos(Request $req, array $params): Response
    {
        return Response::json(['generos' => $this->servico->generos()]);
    }
}
