<?php
declare(strict_types=1);

namespace GameShelf\Repositories;

interface UsuarioRepositoryInterface
{
    /** @return array<string,mixed>|null inclui senha_hash */
    public function porId(int $id): ?array;

    /** @return array<string,mixed>|null inclui senha_hash */
    public function porEmail(string $email): ?array;

    /** @throws \GameShelf\Core\HttpException 409 se o e-mail já existir */
    public function criar(string $nome, string $email, string $senhaHash, string $perfil): int;

    public function atualizarHash(int $id, string $senhaHash): void;

    /** @return list<array<string,mixed>> */
    public function listar(int $limite, int $offset): array;

    public function contar(): int;

    public function contarAdmins(): int;

    public function atualizarPerfil(int $id, string $perfil): void;

    public function excluir(int $id): void;

    public function registrarFalha(string $email, string $ip): void;

    public function contarFalhas(string $email, string $ip, int $minutos): int;

    public function limparFalhas(string $email, string $ip): void;
}
