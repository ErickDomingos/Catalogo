<?php
declare(strict_types=1);

use GameShelf\Core\Validator;

T::teste('Validator: texto válido é aceito e aparado', function () {
    $v = new Validator(['titulo' => '  Hollow   Knight  ']);
    T::igual('Hollow Knight', $v->texto('titulo', 'Título', 1, 80));
    T::verdadeiro(!$v->falhou());
});

T::teste('Validator: texto acima do tamanho máximo é rejeitado', function () {
    $v = new Validator(['titulo' => str_repeat('a', 81)]);
    $v->texto('titulo', 'Título', 1, 80);
    T::verdadeiro(isset($v->erros()['titulo']), 'deveria haver erro em titulo');
});

T::teste('Validator: tipo errado (array no lugar de texto) é rejeitado', function () {
    $v = new Validator(['titulo' => ['x']]);
    $v->texto('titulo', 'Título', 1, 80);
    T::verdadeiro(isset($v->erros()['titulo']));
});

T::teste('Validator: sanitização remove tags HTML e caracteres de controle', function () {
    $v = new Validator(['titulo' => "<script>alert(1)</script>Zelda\x00"]);
    $titulo = $v->texto('titulo', 'Título', 1, 80);
    T::igual('alert(1)Zelda', $titulo);
    T::verdadeiro(!str_contains((string) $titulo, '<'));
});

T::teste('Validator: inteiro fora da faixa e decimal fora do passo são rejeitados', function () {
    $v = new Validator(['ano' => 1800, 'nota' => 7.55]);
    $v->inteiro('ano', 'Ano', 1950, 2100);
    $v->decimal('nota', 'Nota', 0, 10, 0.1);
    T::verdadeiro(isset($v->erros()['ano']) && isset($v->erros()['nota']));
});

T::teste('Validator: senha fraca (sem número ou curta) é rejeitada e forte é aceita', function () {
    $fraca = new Validator(['senha' => 'somenteletras']);
    $fraca->senha('senha', 'Senha');
    T::verdadeiro($fraca->falhou());
    $curta = new Validator(['senha' => 'a1']);
    $curta->senha('senha', 'Senha');
    T::verdadeiro($curta->falhou());
    $ok = new Validator(['senha' => 'Senha1234']);
    T::igual('Senha1234', $ok->senha('senha', 'Senha'));
});

T::teste('Validator: lancar() responde 422 com campos estruturados', function () {
    $v = new Validator([]);
    $v->texto('titulo', 'Título', 1, 80);
    $e = T::lanca(422, fn () => $v->lancar());
    T::verdadeiro(isset($e->campos['titulo']));
});
