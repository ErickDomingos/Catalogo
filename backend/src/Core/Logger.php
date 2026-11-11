<?php
declare(strict_types=1);

namespace GameShelf\Core;

/** Log de aplicação: uma linha por evento, com timestamp ISO-8601 e contexto em JSON. */
final class Logger
{
    private const CAMPOS_SECRETOS = ['senha', 'password', 'senha_hash', 'token', 'csrf'];

    public function __construct(private readonly string $arquivo)
    {
    }

    /** @param array<string,mixed> $contexto */
    public function info(string $evento, array $contexto = []): void
    {
        $this->escrever('INFO', $evento, $contexto);
    }

    /** @param array<string,mixed> $contexto */
    public function warning(string $evento, array $contexto = []): void
    {
        $this->escrever('WARNING', $evento, $contexto);
    }

    /** @param array<string,mixed> $contexto */
    public function error(string $evento, array $contexto = []): void
    {
        $this->escrever('ERROR', $evento, $contexto);
    }

    /** @param array<string,mixed> $contexto */
    private function escrever(string $nivel, string $evento, array $contexto): void
    {
        foreach (self::CAMPOS_SECRETOS as $campo) {
            unset($contexto[$campo]);
        }
        // json_encode escapa quebras de linha: impede forjar linhas falsas no log (log injection).
        $json = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        $linha = sprintf("[%s] %s %s %s\n", (new \DateTimeImmutable())->format('c'), $nivel, $evento, $json === false ? '{}' : $json);

        $pasta = dirname($this->arquivo);
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }
        @file_put_contents($this->arquivo, $linha, FILE_APPEND | LOCK_EX);
    }
}
