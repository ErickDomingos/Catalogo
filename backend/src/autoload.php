<?php
declare(strict_types=1);

// Autoloader PSR-4 mínimo: GameShelf\Pasta\Classe => src/Pasta/Classe.php (sem Composer).
spl_autoload_register(static function (string $classe): void {
    $prefixo = 'GameShelf\\';
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }
    $arquivo = __DIR__ . '/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});
