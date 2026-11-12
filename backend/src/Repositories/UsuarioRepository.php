<?php
declare(strict_types=1);

namespace GameShelf\Repositories;

use GameShelf\Core\HttpException;
use PDO;
use PDOException;

/** Acesso a dados de usuários: somente consultas parametrizadas. */
final class UsuarioRepository implements UsuarioRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function porId(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT id, nome, email, senha_hash, perfil, criado_em FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        return $this->normalizar($st->fetch());
    }

    public function porEmail(string $email): ?array
    {
        $st = $this->pdo->prepare('SELECT id, nome, email, senha_hash, perfil, criado_em FROM usuarios WHERE email = :email');
        $st->execute([':email' => $email]);
        return $this->normalizar($st->fetch());
    }

    public function criar(string $nome, string $email, string $senhaHash, string $perfil): int
    {
        try {
            $st = $this->pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, :perfil)');
            $st->execute([':nome' => $nome, ':email' => $email, ':hash' => $senhaHash, ':perfil' => $perfil]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // violação de UNIQUE (corrida entre dois cadastros)
                throw new HttpException(409, 'Este e-mail já está cadastrado.', ['email' => 'E-mail já cadastrado.'], 'conflito');
            }
            throw $e;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizarHash(int $id, string $senhaHash): void
    {
        $this->pdo->prepare('UPDATE usuarios SET senha_hash = :hash WHERE id = :id')->execute([':hash' => $senhaHash, ':id' => $id]);
    }

    public function listar(int $limite, int $offset): array
    {
        $st = $this->pdo->prepare('SELECT u.id, u.nome, u.email, u.perfil, u.criado_em,
                    (SELECT COUNT(*) FROM jogos j WHERE j.dono_id = u.id) AS total_jogos
             FROM usuarios u ORDER BY u.id LIMIT :limite OFFSET :offset');
        $st->bindValue(':limite', $limite, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return array_map(fn (array $l) => $this->normalizar($l), $st->fetchAll());
    }

    public function contar(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public function contarAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'")->fetchColumn();
    }

    public function atualizarPerfil(int $id, string $perfil): void
    {
        $this->pdo->prepare('UPDATE usuarios SET perfil = :perfil WHERE id = :id')->execute([':perfil' => $perfil, ':id' => $id]);
    }

    public function excluir(int $id): void
    {
        $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute([':id' => $id]);
    }

    public function registrarFalha(string $email, string $ip): void
    {
        $this->pdo->prepare('INSERT INTO login_falhas (email, ip) VALUES (:email, :ip)')->execute([':email' => $email, ':ip' => $ip]);
    }

    public function contarFalhas(string $email, string $ip, int $minutos): int
    {
        $st = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_falhas WHERE email = :email AND ip = :ip AND criado_em > (NOW() - INTERVAL :min MINUTE)'
        );
        $st->bindValue(':email', $email);
        $st->bindValue(':ip', $ip);
        $st->bindValue(':min', $minutos, PDO::PARAM_INT);
        $st->execute();
        return (int) $st->fetchColumn();
    }

    public function limparFalhas(string $email, string $ip): void
    {
        $this->pdo->prepare('DELETE FROM login_falhas WHERE email = :email AND ip = :ip')->execute([':email' => $email, ':ip' => $ip]);
    }

    /** @param array<string,mixed>|false $linha */
    private function normalizar(array|false $linha): ?array
    {
        if ($linha === false) {
            return null;
        }
        $linha['id'] = (int) $linha['id'];
        if (isset($linha['total_jogos'])) {
            $linha['total_jogos'] = (int) $linha['total_jogos'];
        }
        return $linha;
    }
}
