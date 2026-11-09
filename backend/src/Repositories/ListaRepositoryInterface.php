<?php
declare(strict_types=1);

namespace GameShelf\Repositories;

interface ListaRepositoryInterface
{
    public function definir(int $uid, int $jogoId, string $estado): void;

    public function remover(int $uid, int $jogoId): void;

    /** @return list<array<string,mixed>> */
    public function doUsuario(int $uid): array;

    /** @return array<string,int> estado => quantidade */
    public function contagens(int $uid): array;
}
