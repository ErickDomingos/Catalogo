<?php
declare(strict_types=1);

use GameShelf\Core\Logger;
use GameShelf\Services\JogoService;
use Tests\Fakes\FakeJogoRepository;

function novoServicoJogo(?FakeJogoRepository &$repo = null): JogoService
{
    $repo = new FakeJogoRepository();
    return new JogoService($repo, new Logger(sys_get_temp_dir() . '/gameshelf-teste.log'));
}

$comum = ['id' => 2, 'perfil' => 'usuario', 'nome' => 'Ana', 'email' => 'ana@x.com'];
$outro = ['id' => 3, 'perfil' => 'usuario', 'nome' => 'Beto', 'email' => 'beto@x.com'];
$admin = ['id' => 1, 'perfil' => 'admin', 'nome' => 'Adm', 'email' => 'adm@x.com'];
$valido = ['titulo' => 'Meu Jogo', 'genero' => 'RPG', 'plataformas' => ['pc'], 'ano' => 2020];

T::teste('JogoService: usuário cria jogo privado (dono = ele)', function () use ($comum, $valido) {
    $s = novoServicoJogo($repo);
    $jogo = $s->criar($comum, $valido);
    T::igual(false, $jogo['oficial']);
    T::igual(true, $jogo['pode_editar']);
});

T::teste('JogoService: admin cria jogo do catálogo oficial', function () use ($admin, $valido) {
    $s = novoServicoJogo($repo);
    T::igual(true, $s->criar($admin, $valido)['oficial']);
});

T::teste('JogoService: dados inválidos (gênero inexistente, ano absurdo) geram 422', function () use ($comum) {
    $s = novoServicoJogo($repo);
    $e = T::lanca(422, fn () => $s->criar($comum, ['titulo' => 'X', 'genero' => 'Inexistente', 'plataformas' => ['pc'], 'ano' => 3000]));
    T::verdadeiro(isset($e->campos['genero']) && isset($e->campos['ano']));
});

T::teste('JogoService: título duplicado para o mesmo dono gera 409', function () use ($comum, $valido) {
    $s = novoServicoJogo($repo);
    $s->criar($comum, $valido);
    T::lanca(409, fn () => $s->criar($comum, $valido));
});

T::teste('JogoService: sem plataforma e sem console gera 422', function () use ($comum) {
    $s = novoServicoJogo($repo);
    $e = T::lanca(422, fn () => $s->criar($comum, ['titulo' => 'Sem plat', 'genero' => 'RPG']));
    T::verdadeiro(isset($e->campos['plataformas']));
});

T::teste('JogoService: console adiciona a família de plataforma automaticamente', function () use ($comum) {
    $s = novoServicoJogo($repo);
    $s->criar($comum, ['titulo' => 'Console', 'genero' => 'RPG', 'console' => 'ps5']);
    T::igual(['playstation'], array_values($repo->linhas)[0]['plataformas']);
});

T::teste('JogoService: usuário não edita jogo oficial (403)', function () use ($comum, $admin, $valido) {
    $s = novoServicoJogo($repo);
    $oficial = $s->criar($admin, $valido);
    T::lanca(403, fn () => $s->atualizar($comum, $oficial['id'], $valido));
    T::lanca(403, fn () => $s->excluir($comum, $oficial['id']));
});

T::teste('JogoService: jogo privado de outro usuário é invisível (404), mas o admin o vê', function () use ($comum, $outro, $admin, $valido) {
    $s = novoServicoJogo($repo);
    $privado = $s->criar($comum, $valido);
    T::lanca(404, fn () => $s->obter($outro, $privado['id']));
    T::lanca(404, fn () => $s->obter(null, $privado['id']));
    T::igual($privado['id'], $s->obter($admin, $privado['id'])['id']);
});

T::teste('JogoService: listagem de visitante mostra só o catálogo oficial', function () use ($comum, $admin, $valido) {
    $s = novoServicoJogo($repo);
    $s->criar($comum, $valido);
    $s->criar($admin, ['titulo' => 'Oficial', 'genero' => 'Ação', 'plataformas' => ['xbox']]);
    $lista = $s->listar(null, []);
    T::igual(1, $lista['paginacao']['total']);
    T::igual('Oficial', $lista['dados'][0]['titulo']);
});

T::teste('JogoService: escopo "meus" sem login gera 401 e parâmetro inválido gera 422', function () {
    $s = novoServicoJogo($repo);
    T::lanca(401, fn () => $s->listar(null, ['escopo' => 'meus']));
    T::lanca(422, fn () => $s->listar(null, ['genero' => "<script>alert(1)</script>"]));
});
