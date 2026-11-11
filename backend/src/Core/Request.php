<?php
declare(strict_types=1);

namespace GameShelf\Core;

final class Request
{
    private const LIMITE_CORPO = 65536; // 64 KB

    /**
     * @param array<string,mixed>  $query
     * @param array<string,mixed>  $corpo
     * @param array<string,string> $cabecalhos chaves em minúsculas
     */
    public function __construct(
        public readonly string $metodo,
        public readonly string $caminho,
        public readonly array $query = [],
        public readonly array $corpo = [],
        public readonly array $cabecalhos = [],
        public readonly string $ip = '0.0.0.0',
    ) {
    }

    public static function fromGlobals(): self
    {
        $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $caminho = rawurldecode((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/'));
        $caminho = preg_replace('#^/api(?=/|$)#', '', $caminho) ?? $caminho;
        $caminho = '/' . trim($caminho, '/');

        $cabecalhos = [];
        foreach ($_SERVER as $chave => $valor) {
            if (is_string($valor) && str_starts_with((string) $chave, 'HTTP_')) {
                $cabecalhos[strtolower(str_replace('_', '-', substr((string) $chave, 5)))] = $valor;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $cabecalhos['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        $corpo = [];
        if (in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $bruto = (string) file_get_contents('php://input', false, null, 0, self::LIMITE_CORPO + 1);
            if (strlen($bruto) > self::LIMITE_CORPO) {
                throw new HttpException(413, 'Corpo da requisição grande demais.', [], 'corpo_grande');
            }
            if ($bruto !== '') {
                if (!str_contains(strtolower($cabecalhos['content-type'] ?? ''), 'application/json')) {
                    throw new HttpException(415, 'Use Content-Type: application/json.', [], 'tipo_nao_suportado');
                }
                $decodificado = json_decode($bruto, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodificado)) {
                    throw new HttpException(400, 'JSON inválido.', [], 'json_invalido');
                }
                $corpo = $decodificado;
            }
        }

        return new self($metodo, $caminho, $_GET, $corpo, $cabecalhos, (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    }

    public function cabecalho(string $nome): ?string
    {
        return $this->cabecalhos[strtolower($nome)] ?? null;
    }

    /** Converte um parâmetro de rota em inteiro positivo; qualquer outra coisa é "não encontrado". */
    public static function id(array $params, string $nome = 'id'): int
    {
        $valor = $params[$nome] ?? '';
        if (!is_string($valor) || !preg_match('/^[1-9]\d{0,9}$/', $valor)) {
            throw new HttpException(404, 'Recurso não encontrado.', [], 'nao_encontrado');
        }
        return (int) $valor;
    }
}
