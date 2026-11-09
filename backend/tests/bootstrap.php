<?php
declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/fakes/FakeUsuarioRepository.php';
require __DIR__ . '/fakes/FakeJogoRepository.php';

use GameShelf\Core\HttpException;

/** Mini framework de testes (sem Composer): T::teste(), T::igual(), T::lanca()... */
final class T
{
    public static int $ok = 0;
    public static int $falhas = 0;

    public static function teste(string $nome, callable $corpo): void
    {
        try {
            $corpo();
            self::$ok++;
            echo "[OK]    {$nome}\n";
        } catch (Throwable $e) {
            self::$falhas++;
            echo "[FALHA] {$nome}\n        -> " . $e->getMessage() . "\n";
        }
    }

    public static function igual(mixed $esperado, mixed $real, string $msg = ''): void
    {
        if ($esperado !== $real) {
            throw new RuntimeException(($msg !== '' ? $msg . ': ' : '') . 'esperado ' . var_export($esperado, true) . ', obtido ' . var_export($real, true));
        }
    }

    public static function verdadeiro(bool $cond, string $msg = 'condição falsa'): void
    {
        if (!$cond) {
            throw new RuntimeException($msg);
        }
    }

    /** Exige que $corpo lance HttpException com o status dado. */
    public static function lanca(int $status, callable $corpo): HttpException
    {
        try {
            $corpo();
        } catch (HttpException $e) {
            if ($e->status !== $status) {
                throw new RuntimeException("esperado HTTP {$status}, obtido {$e->status} ({$e->getMessage()})");
            }
            return $e;
        }
        throw new RuntimeException("esperado HttpException {$status}, mas nada foi lançado");
    }
}
