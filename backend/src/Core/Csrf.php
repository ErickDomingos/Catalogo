<?php
declare(strict_types=1);

namespace GameShelf\Core;

/** Proteção CSRF por token de sessão (enviado no cabeçalho X-CSRF-Token) + checagem de Origin. */
final class Csrf
{
    public static function token(): string
    {
        Session::iniciar();
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function valido(?string $enviado): bool
    {
        if ($enviado === null || !Session::existeCookie()) {
            return false;
        }
        Session::iniciar();
        $esperado = $_SESSION['csrf'] ?? '';
        return is_string($esperado) && $esperado !== '' && hash_equals($esperado, $enviado);
    }

    /** Se o navegador informou Origin, ele precisa ser o mesmo host que atende a API. */
    public static function origemConfiavel(?string $origin, ?string $host): bool
    {
        if ($origin === null || $origin === '') {
            return true;
        }
        $origemHost = parse_url($origin, PHP_URL_HOST);
        $origemPorta = parse_url($origin, PHP_URL_PORT);
        if (!is_string($origemHost) || $host === null) {
            return false;
        }
        $completo = $origemPorta !== null ? $origemHost . ':' . $origemPorta : $origemHost;
        return strcasecmp($completo, $host) === 0;
    }
}
