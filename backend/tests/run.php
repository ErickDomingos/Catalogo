<?php
declare(strict_types=1);

/** Executa todos os testes unitários:  php tests/run.php   (código de saída 1 se algum falhar) */
require __DIR__ . '/bootstrap.php';

echo "GameShelf - testes automatizados\n" . str_repeat('=', 60) . "\n";
foreach (glob(__DIR__ . '/unit/*Test.php') ?: [] as $arquivo) {
    echo "\n# " . basename($arquivo) . "\n";
    require $arquivo;
}
$total = T::$ok + T::$falhas;
echo "\n" . str_repeat('=', 60) . "\n{$total} teste(s): " . T::$ok . " ok, " . T::$falhas . " falha(s)\n";
exit(T::$falhas === 0 ? 0 : 1);
