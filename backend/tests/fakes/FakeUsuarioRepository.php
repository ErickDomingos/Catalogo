<?php
declare(strict_types=1);

namespace Tests\Fakes;

use GameShelf\Repositories\UsuarioRepositoryInterface;

/** Repositório em memória para testar regras de negócio sem banco. */
final class FakeUsuarioRepository implements UsuarioRepositoryInterface
{
    /** @var array<int,array<string,mixed>> */
    public array $linhas = [];
    /** @var list<array{email:string,ip:string}> */
    public array $falhas = [];
    private int $seq = 0;

    public function porId(int $id): ?array
    {
        return $this->linhas[$id] ?? null;
    }

    public function porEmail(string $email): ?array
    {
        foreach ($this->linhas as $l) {
            if ($l['email'] === $email) {
                return $l;
            }
        }
        return null;
    }

    public function criar(string $nome, string $email, string $senhaHash, string $perfil): int
    {
        $id = ++$this->seq;
        $this->linhas[$id] = ['id' => $id, 'nome' => $nome, 'email' => $email, 'senha_hash' => $senhaHash, 'perfil' => $perfil, 'criado_em' => 'agora'];
        return $id;
    }

    public function atualizarHash(int $id, string $senhaHash): void
    {
        $this->linhas[$id]['senha_hash'] = $senhaHash;
    }

    public function listar(int $limite, int $offset): array
    {
        return array_slice(array_values($this->linhas), $offset, $limite);
    }

    public function contar(): int
    {
        return count($this->linhas);
    }

    public function contarAdmins(): int
    {
        return count(array_filter($this->linhas, fn ($l) => $l['perfil'] === 'admin'));
    }

    public function atualizarPerfil(int $id, string $perfil): void
    {
        $this->linhas[$id]['perfil'] = $perfil;
    }

    public function excluir(int $id): void
    {
        unset($this->linhas[$id]);
    }

    public function registrarFalha(string $email, string $ip): void
    {
        $this->falhas[] = ['email' => $email, 'ip' => $ip];
    }

    public function contarFalhas(string $email, string $ip, int $minutos): int
    {
        return count(array_filter($this->falhas, fn ($f) => $f['email'] === $email && $f['ip'] === $ip));
    }

    public function limparFalhas(string $email, string $ip): void
    {
        $this->falhas = array_values(array_filter($this->falhas, fn ($f) => !($f['email'] === $email && $f['ip'] === $ip)));
    }
}
