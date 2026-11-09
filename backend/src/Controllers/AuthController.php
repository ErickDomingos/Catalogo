<?php
declare(strict_types=1);

namespace GameShelf\Controllers;

use GameShelf\Core\Auth;
use GameShelf\Core\Csrf;
use GameShelf\Core\Logger;
use GameShelf\Core\Request;
use GameShelf\Core\Response;
use GameShelf\Services\AuthService;

final class AuthController
{
    public function __construct(
        private readonly AuthService $servico,
        private readonly Auth $auth,
        private readonly Logger $log,
    ) {
    }

    public function csrf(Request $req, array $params): Response
    {
        return Response::json(['csrf_token' => Csrf::token()]);
    }

    public function registro(Request $req, array $params): Response
    {
        $usuario = $this->servico->registrar($req->corpo);
        $this->auth->entrar($usuario);
        return Response::json(['usuario' => $this->publico($usuario)], 201);
    }

    public function login(Request $req, array $params): Response
    {
        $usuario = $this->servico->login($req->corpo, $req->ip);
        $this->auth->entrar($usuario);
        return Response::json(['usuario' => $this->publico($usuario)]);
    }

    public function logout(Request $req, array $params): Response
    {
        $usuario = $this->auth->usuario();
        $this->auth->sair();
        $this->log->info('logout', ['usuario_id' => $usuario['id'] ?? null]);
        return Response::vazio();
    }

    public function eu(Request $req, array $params): Response
    {
        return Response::json(['usuario' => $this->publico((array) $this->auth->usuario())]);
    }

    /** @param array<string,mixed> $u */
    private function publico(array $u): array
    {
        return ['id' => (int) $u['id'], 'nome' => $u['nome'], 'email' => $u['email'], 'perfil' => $u['perfil']];
    }
}
