<?php
declare(strict_types=1);

namespace GameShelf\Repositories;

interface JogoRepositoryInterface
{
    /**
     * Filtro: busca, genero, plataforma, escopo (todos|meus|oficiais), ordem, direcao,
     * uid (?int, quem consulta) e admin (bool, enxerga tudo).
     *
     * @param array<string,mixed> $filtro
     * @return list<array<string,mixed>>
     */
    public function buscar(array $filtro, int $limite, int $offset): array;

    /** @param array<string,mixed> $filtro */
    public function contar(array $filtro): int;

    /** @return array<string,mixed>|null  uid permite preencher minha_lista */
    public function porId(int $id, ?int $uid = null): ?array;

    /** @param array<string,mixed> $dados inclui slug e dono_id (null = catálogo oficial) */
    public function criar(array $dados): int;

    /** @param array<string,mixed> $dados */
    public function atualizar(int $id, array $dados): void;

    public function excluir(int $id): void;

    public function existeTitulo(string $titulo, ?int $donoId, ?int $ignorarId): bool;

    /** @return list<string> */
    public function nomesGeneros(): array;
}
