<?php
declare(strict_types=1);

namespace Tests\Fakes;

use GameShelf\Repositories\JogoRepositoryInterface;

final class FakeJogoRepository implements JogoRepositoryInterface
{
    /** @var array<int,array<string,mixed>> */
    public array $linhas = [];
    private int $seq = 0;

    public function buscar(array $filtro, int $limite, int $offset): array
    {
        return array_slice(array_values($this->visiveis($filtro)), $offset, $limite);
    }

    public function contar(array $filtro): int
    {
        return count($this->visiveis($filtro));
    }

    public function porId(int $id, ?int $uid = null): ?array
    {
        return $this->linhas[$id] ?? null;
    }

    public function criar(array $dados): int
    {
        $id = ++$this->seq;
        $this->linhas[$id] = $dados + ['id' => $id, 'minha_lista' => null];
        return $id;
    }

    public function atualizar(int $id, array $dados): void
    {
        $this->linhas[$id] = $dados + $this->linhas[$id];
    }

    public function excluir(int $id): void
    {
        unset($this->linhas[$id]);
    }

    public function existeTitulo(string $titulo, ?int $donoId, ?int $ignorarId): bool
    {
        foreach ($this->linhas as $l) {
            if (strcasecmp($l['titulo'], $titulo) === 0 && $l['dono_id'] === $donoId && $l['id'] !== $ignorarId) {
                return true;
            }
        }
        return false;
    }

    public function nomesGeneros(): array
    {
        return ['Ação', 'Aventura', 'RPG', 'Tiro'];
    }

    /** @param array<string,mixed> $f */
    private function visiveis(array $f): array
    {
        $uid = $f['uid'] ?? null;
        return array_filter(
            $this->linhas,
            fn ($l) => (($f['admin'] ?? false) || $l['dono_id'] === null || $l['dono_id'] === $uid)
                && (!isset($f['dono']) || ($l['dono_id'] !== null && (int) $l['dono_id'] === (int) $f['dono']))
        );
    }
}
