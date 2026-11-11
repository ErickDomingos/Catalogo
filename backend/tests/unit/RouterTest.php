<?php
declare(strict_types=1);

use GameShelf\Core\Csrf;
use GameShelf\Core\Request;
use GameShelf\Core\Response;
use GameShelf\Core\Router;

function novoRoteador(?array $usuario, bool $csrfOk = true): Router
{
    $r = new Router(fn () => $usuario, fn () => $csrfOk);
    $ok = fn (Request $req, array $p) => Response::json(['ok' => true, 'params' => $p]);
    $r->adicionar('GET', '/publica', $ok);
    $r->adicionar('GET', '/privada', $ok, 'usuario');
    $r->adicionar('GET', '/admin', $ok, 'admin');
    $r->adicionar('POST', '/escrita', $ok, 'usuario');
    $r->adicionar('GET', '/jogos/{id}', $ok);
    return $r;
}

T::teste('Router: rota pública responde 200 sem login', function () {
    T::igual(200, novoRoteador(null)->despachar(new Request('GET', '/publica'))->status);
});

T::teste('Router: rota protegida sem login responde 401', function () {
    T::lanca(401, fn () => novoRoteador(null)->despachar(new Request('GET', '/privada')));
});

T::teste('Router: perfil insuficiente (usuário em rota admin) responde 403; admin passa', function () {
    T::lanca(403, fn () => novoRoteador(['id' => 2, 'perfil' => 'usuario'])->despachar(new Request('GET', '/admin')));
    T::igual(200, novoRoteador(['id' => 1, 'perfil' => 'admin'])->despachar(new Request('GET', '/admin'))->status);
});

T::teste('Router: método de escrita sem token CSRF válido responde 403', function () {
    $usuario = ['id' => 2, 'perfil' => 'usuario'];
    $e = T::lanca(403, fn () => novoRoteador($usuario, false)->despachar(new Request('POST', '/escrita')));
    T::igual('csrf_invalido', $e->codigo);
    T::igual(200, novoRoteador($usuario, true)->despachar(new Request('POST', '/escrita'))->status);
});

T::teste('Router: rota inexistente 404, método errado 405 com cabeçalho Allow, parâmetro capturado', function () {
    $r = novoRoteador(null);
    T::lanca(404, fn () => $r->despachar(new Request('GET', '/nao-existe')));
    $e = T::lanca(405, fn () => $r->despachar(new Request('DELETE', '/publica')));
    T::igual('GET', $e->cabecalhos['Allow']);
    T::igual(['id' => '7'], $r->despachar(new Request('GET', '/jogos/7'))->dados['params']);
});

T::teste('Request::id: id não numérico ou malicioso vira 404 (nunca chega ao banco)', function () {
    T::igual(15, Request::id(['id' => '15']));
    T::lanca(404, fn () => Request::id(['id' => '1 OR 1=1']));
    T::lanca(404, fn () => Request::id(['id' => '0']));
});

T::teste('Csrf: Origin de outro site é recusado; mesmo host é aceito', function () {
    T::verdadeiro(Csrf::origemConfiavel('http://localhost:8000', 'localhost:8000'));
    T::verdadeiro(!Csrf::origemConfiavel('http://site-malicioso.com', 'localhost:8000'));
    T::verdadeiro(Csrf::origemConfiavel(null, 'localhost:8000'));
});

T::teste('Response: JSON escapa < > & aspas (nenhum HTML ativo no corpo)', function () {
    $corpo = Response::json(['t' => '<script>alert("x")</script> & co'])->corpo();
    T::verdadeiro(!str_contains($corpo, '<') && !str_contains($corpo, '>'), 'corpo não pode ter < ou >');
    T::verdadeiro(str_contains($corpo, '\u003Cscript'));
});
