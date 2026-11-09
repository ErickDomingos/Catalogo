<?php
declare(strict_types=1);

namespace GameShelf\Core;

/**
 * Validação (tipo, tamanho, faixa, lista fechada) e sanitização (controle, tags, espaços).
 * Acumula erros por campo e, em lancar(), responde 422 com mensagens estruturadas.
 */
final class Validator
{
    /** @var array<string,string> */
    private array $erros = [];

    /** @param array<string,mixed> $entrada */
    public function __construct(private readonly array $entrada)
    {
    }

    /** Remove caracteres de controle e tags HTML/PHP; não altera acentos nem pontuação comum. */
    public static function limpar(string $valor): string
    {
        $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor) ?? '';
        return trim(strip_tags($valor));
    }

    public function texto(string $campo, string $rotulo, int $min, int $max, bool $obrigatorio = true, bool $multilinha = false): ?string
    {
        $bruto = $this->entrada[$campo] ?? null;
        if ($bruto === null || $bruto === '') {
            return $this->ausente($campo, $rotulo, $obrigatorio);
        }
        if (!is_string($bruto) || !mb_check_encoding($bruto, 'UTF-8')) {
            $this->erros[$campo] = "{$rotulo} deve ser um texto válido.";
            return null;
        }
        $valor = self::limpar($bruto);
        if (!$multilinha) {
            $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');
        }
        if ($valor === '') {
            return $this->ausente($campo, $rotulo, $obrigatorio);
        }
        $tamanho = mb_strlen($valor);
        if ($tamanho < $min) {
            $this->erros[$campo] = "{$rotulo} deve ter ao menos {$min} caractere(s).";
            return null;
        }
        if ($tamanho > $max) {
            $this->erros[$campo] = "{$rotulo} deve ter no máximo {$max} caracteres.";
            return null;
        }
        return $valor;
    }

    public function inteiro(string $campo, string $rotulo, int $min, int $max, bool $obrigatorio = false): ?int
    {
        $valor = $this->entrada[$campo] ?? null;
        if ($valor === null || $valor === '') {
            return $this->ausente($campo, $rotulo, $obrigatorio);
        }
        if (is_string($valor) && preg_match('/^-?\d{1,9}$/', $valor)) {
            $valor = (int) $valor;
        }
        if (!is_int($valor)) {
            $this->erros[$campo] = "{$rotulo} deve ser um número inteiro.";
            return null;
        }
        if ($valor < $min || $valor > $max) {
            $this->erros[$campo] = "{$rotulo} deve estar entre {$min} e {$max}.";
            return null;
        }
        return $valor;
    }

    public function decimal(string $campo, string $rotulo, float $min, float $max, float $passo, bool $obrigatorio = false): ?float
    {
        $valor = $this->entrada[$campo] ?? null;
        if ($valor === null || $valor === '') {
            return $this->ausente($campo, $rotulo, $obrigatorio);
        }
        if (is_string($valor) && preg_match('/^-?\d{1,6}(\.\d{1,6})?$/', $valor)) {
            $valor = (float) $valor;
        }
        if (is_int($valor)) {
            $valor = (float) $valor;
        }
        if (!is_float($valor) || !is_finite($valor)) {
            $this->erros[$campo] = "{$rotulo} deve ser um número.";
            return null;
        }
        $passos = $valor / $passo;
        if ($valor < $min || $valor > $max || abs($passos - round($passos)) > 0.000001) {
            $this->erros[$campo] = "{$rotulo} deve estar entre {$min} e {$max}, em passos de {$passo}.";
            return null;
        }
        return $valor;
    }

    /** @param list<string> $permitidos */
    public function enum(string $campo, string $rotulo, array $permitidos, bool $obrigatorio = true): ?string
    {
        $valor = $this->entrada[$campo] ?? null;
        if ($valor === null || $valor === '') {
            return $this->ausente($campo, $rotulo, $obrigatorio);
        }
        if (!is_string($valor) || !in_array($valor, $permitidos, true)) {
            $this->erros[$campo] = "{$rotulo} inválido(a).";
            return null;
        }
        return $valor;
    }

    /**
     * @param list<string> $permitidos
     * @return list<string>
     */
    public function listaEnum(string $campo, string $rotulo, array $permitidos): array
    {
        $valor = $this->entrada[$campo] ?? [];
        if ($valor === null || $valor === '') {
            return [];
        }
        if (!is_array($valor) || !array_is_list($valor) || count($valor) > count($permitidos)) {
            $this->erros[$campo] = "{$rotulo} deve ser uma lista de valores válidos.";
            return [];
        }
        foreach ($valor as $item) {
            if (!is_string($item) || !in_array($item, $permitidos, true)) {
                $this->erros[$campo] = "{$rotulo} contém valor inválido.";
                return [];
            }
        }
        return array_values(array_unique($valor));
    }

    public function email(string $campo, string $rotulo): ?string
    {
        $valor = $this->entrada[$campo] ?? null;
        if (!is_string($valor) || trim($valor) === '') {
            $this->erros[$campo] = "{$rotulo} é obrigatório.";
            return null;
        }
        $valor = strtolower(trim($valor));
        if (strlen($valor) > 120 || filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            $this->erros[$campo] = "{$rotulo} inválido.";
            return null;
        }
        return $valor;
    }

    /** Política de senha: 8 a 72 bytes (limite do bcrypt), com ao menos uma letra e um número. */
    public function senha(string $campo, string $rotulo): ?string
    {
        $valor = $this->entrada[$campo] ?? null;
        if (!is_string($valor) || $valor === '') {
            $this->erros[$campo] = "{$rotulo} é obrigatória.";
            return null;
        }
        if (strlen($valor) < 8 || strlen($valor) > 72) {
            $this->erros[$campo] = "{$rotulo} deve ter de 8 a 72 caracteres.";
            return null;
        }
        if (!preg_match('/\p{L}/u', $valor) || !preg_match('/\d/', $valor)) {
            $this->erros[$campo] = "{$rotulo} deve conter ao menos uma letra e um número.";
            return null;
        }
        return $valor;
    }

    public function adicionarErro(string $campo, string $mensagem): void
    {
        $this->erros[$campo] = $mensagem;
    }

    /** @return array<string,string> */
    public function erros(): array
    {
        return $this->erros;
    }

    public function falhou(): bool
    {
        return $this->erros !== [];
    }

    public function lancar(): void
    {
        if ($this->erros !== []) {
            throw new HttpException(422, 'Dados inválidos.', $this->erros, 'validacao');
        }
    }

    private function ausente(string $campo, string $rotulo, bool $obrigatorio): ?string
    {
        if ($obrigatorio) {
            $this->erros[$campo] = "{$rotulo} é obrigatório(a).";
        }
        return null;
    }
}
