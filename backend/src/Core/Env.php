<?php
declare(strict_types=1);

namespace GameShelf\Core;

/** Leitura de configuração a partir de .env / variáveis de ambiente (nada sensível no código). */
final class Env
{
    /** @var array<string,string> */
    private static array $valores = [];

    public static function carregar(string $arquivo): void
    {
        if (!is_file($arquivo)) {
            return;
        }
        foreach (file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
            $linha = trim($linha);
            if ($linha === '' || $linha[0] === '#' || !str_contains($linha, '=')) {
                continue;
            }
            [$chave, $valor] = explode('=', $linha, 2);
            $valor = trim($valor);
            if ($valor !== '' && ($valor[0] === '"' || $valor[0] === "'")) {
                if (strlen($valor) >= 2 && substr($valor, -1) === $valor[0]) {
                    $valor = substr($valor, 1, -1);
                }
            } else {
                // comentário no fim da linha (" # ...") quando o valor não está entre aspas
                $valor = trim(preg_replace('/\s+#.*$/', '', $valor) ?? $valor);
            }
            self::$valores[trim($chave)] = $valor;
        }
    }

    public static function definir(string $chave, string $valor): void
    {
        self::$valores[$chave] = $valor;
    }

    public static function get(string $chave, ?string $padrao = null): ?string
    {
        if (array_key_exists($chave, self::$valores)) {
            return self::$valores[$chave];
        }
        $valor = getenv($chave);
        return $valor === false ? $padrao : $valor;
    }

    public static function obrigatoria(string $chave): string
    {
        $valor = self::get($chave);
        if ($valor === null || $valor === '') {
            throw new \RuntimeException("Variável de configuração ausente: {$chave} (veja backend/.env.example)");
        }
        return $valor;
    }

    public static function bool(string $chave, bool $padrao = false): bool
    {
        $valor = self::get($chave);
        return $valor === null ? $padrao : in_array(strtolower($valor), ['1', 'true', 'on', 'sim', 'yes'], true);
    }

    public static function desenvolvimento(): bool
    {
        return self::get('APP_ENV', 'production') === 'development';
    }
}
