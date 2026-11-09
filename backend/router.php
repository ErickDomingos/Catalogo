<?php
declare(strict_types=1);

/**
 * Roteador do servidor embutido do PHP (somente desenvolvimento):
 *   php -S localhost:8000 -t ../frontend router.php
 * /api/* vai para a API; qualquer outro caminho é servido como arquivo estático do frontend.
 */
$caminho = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

if ($caminho === '/api' || str_starts_with($caminho, '/api/')) {
    require __DIR__ . '/index.php';
    return true;
}
return false;
