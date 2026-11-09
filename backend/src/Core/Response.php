<?php
declare(strict_types=1);

namespace GameShelf\Core;

final class Response
{
    /** @param array<string,string> $cabecalhos */
    public function __construct(
        public readonly int $status,
        public readonly mixed $dados = null,
        public readonly array $cabecalhos = [],
    ) {
    }

    public static function json(mixed $dados, int $status = 200): self
    {
        return new self($status, $dados);
    }

    public static function vazio(): self
    {
        return new self(204);
    }

    /** Escapa < > & ' " no JSON (\u003C...), de modo que o corpo nunca carregue HTML ativo. */
    public function corpo(): string
    {
        if ($this->status === 204 || $this->dados === null) {
            return '';
        }
        return json_encode(
            $this->dados,
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        foreach ($this->cabecalhos as $nome => $valor) {
            header($nome . ': ' . $valor);
        }
        echo $this->corpo();
    }
}
