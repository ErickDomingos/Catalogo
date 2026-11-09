<?php
declare(strict_types=1);

namespace GameShelf\Controllers;

use GameShelf\Core\Auth;
use GameShelf\Core\Request;
use GameShelf\Core\Response;
use GameShelf\Services\ListaService;

final class ListaController
{
    public function __construct(
        private readonly ListaService $servico,
        private readonly Auth $auth,
    ) {
    }

    public function listar(Request $req, array $params): Response
    {
        return Response::json($this->servico->listar($this->uid()));
    }

    public function definir(Request $req, array $params): Response
    {
        $this->servico->definir($this->uid(), Request::id($params, 'jogoId'), $req->corpo);
        return Response::vazio();
    }

    public function remover(Request $req, array $params): Response
    {
        $this->servico->remover($this->uid(), Request::id($params, 'jogoId'));
        return Response::vazio();
    }

    private function uid(): int
    {
        return (int) ($this->auth->usuario()['id'] ?? 0);
    }
}
