<?php
declare(strict_types=1);

namespace GameShelf\Core;

/** Erro "esperado" com status HTTP, mensagem legível e (opcional) erros por campo. */
class HttpException extends \RuntimeException
{
    /**
     * @param array<string,string> $campos     erros de validação por campo
     * @param array<string,string> $cabecalhos cabeçalhos extras (ex.: Allow, Retry-After)
     */
    public function __construct(
        public readonly int $status,
        string $mensagem,
        public readonly array $campos = [],
        public readonly string $codigo = 'erro',
        public readonly array $cabecalhos = [],
    ) {
        parent::__construct($mensagem);
    }
}
