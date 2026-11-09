<?php
declare(strict_types=1);

namespace GameShelf\Core;

/** Sessão PHP com cookie endurecido: HttpOnly, SameSite=Lax, Secure (em HTTPS) e modo estrito. */
final class Session
{
    public const NOME = 'gameshelf_sid';

    public static function existeCookie(): bool
    {
        return isset($_COOKIE[self::NOME]) && is_string($_COOKIE[self::NOME]);
    }

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(self::NOME);
        ini_set('session.use_strict_mode', '1');   // não aceita IDs de sessão inventados pelo cliente
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => Env::bool('SESSION_SECURE', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function destruir(): void
    {
        self::iniciar();
        $_SESSION = [];
        $cookie = session_get_cookie_params();
        session_destroy();
        if (!headers_sent()) {
            setcookie(self::NOME, '', [
                'expires' => time() - 42000,
                'path' => $cookie['path'],
                'domain' => $cookie['domain'],
                'secure' => $cookie['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}
