<?php
declare(strict_types=1);

/** Front controller da API (/api/*): monta as dependências, despacha e trata erros em um só lugar. */

use GameShelf\Controllers\{AuthController, JogoController, ListaController, UsuarioController};
use GameShelf\Core\{Auth, Csrf, Database, Env, ErroHandler, Logger, Request, Router};
use GameShelf\Repositories\{JogoRepository, ListaRepository, UsuarioRepository};
use GameShelf\Services\{AuthService, JogoService, ListaService, UsuarioService};

require __DIR__ . '/src/autoload.php';

Env::carregar(__DIR__ . '/.env');
date_default_timezone_set(Env::get('APP_TZ', 'America/Sao_Paulo') ?? 'America/Sao_Paulo');
ini_set('display_errors', '0');

set_error_handler(static function (int $nivel, string $msg, string $arquivo, int $linha): bool {
    if (!(error_reporting() & $nivel) || $nivel === E_DEPRECATED || $nivel === E_USER_DEPRECATED) {
        return false; // avisos de depreciação (ex.: PHP 8.5) não devem derrubar a API
    }
    throw new ErrorException($msg, 0, $nivel, $arquivo, $linha);
});

$log = new Logger(Env::get('LOG_FILE') ?: __DIR__ . '/storage/logs/app.log');

try {
    $req = Request::fromGlobals();
    $pdo = Database::conexao();

    $repoUsuarios = new UsuarioRepository($pdo);
    $repoJogos = new JogoRepository($pdo);
    $repoListas = new ListaRepository($pdo);
    $auth = new Auth($repoUsuarios);

    $router = new Router(
        static fn (): ?array => $auth->usuario(),
        static fn (Request $r): bool => Csrf::origemConfiavel($r->cabecalho('origin'), $r->cabecalho('host'))
            && Csrf::valido($r->cabecalho('x-csrf-token')),
    );
    (require __DIR__ . '/src/rotas.php')(
        $router,
        new AuthController(new AuthService($repoUsuarios, $log), $auth, $log),
        new JogoController(new JogoService($repoJogos, $log), $auth),
        new ListaController(new ListaService($repoListas, $repoJogos), $auth),
        new UsuarioController(new UsuarioService($repoUsuarios, $log), $auth),
    );

    $resposta = $router->despachar($req);
} catch (Throwable $e) {
    $resposta = ErroHandler::responder($e, $log, Env::desenvolvimento());
}

$resposta->enviar();
