<?php
declare(strict_types=1);

namespace GameShelf\Repositories;

use PDO;

final class ListaRepository implements ListaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function definir(int $uid, int $jogoId, string $estado): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO lista_usuario_jogo (usuario_id, jogo_id, estado) VALUES (:uid, :jogo, :estado)
             ON DUPLICATE KEY UPDATE estado = :estado_novo'
        );
        $st->execute([':uid' => $uid, ':jogo' => $jogoId, ':estado' => $estado, ':estado_novo' => $estado]);
    }

    public function remover(int $uid, int $jogoId): void
    {
        $this->pdo->prepare('DELETE FROM lista_usuario_jogo WHERE usuario_id = :uid AND jogo_id = :jogo')
            ->execute([':uid' => $uid, ':jogo' => $jogoId]);
    }

    public function doUsuario(int $uid): array
    {
        $st = $this->pdo->prepare(
            'SELECT l.jogo_id, j.titulo, l.estado, l.atualizado_em
             FROM lista_usuario_jogo l JOIN jogos j ON j.id = l.jogo_id
             WHERE l.usuario_id = :uid AND (j.dono_id IS NULL OR j.dono_id = :dono)
             ORDER BY l.atualizado_em DESC, j.titulo'
        );
        $st->execute([':uid' => $uid, ':dono' => $uid]);
        return array_map(static function (array $l): array {
            $l['jogo_id'] = (int) $l['jogo_id'];
            return $l;
        }, $st->fetchAll());
    }

    public function contagens(int $uid): array
    {
        $st = $this->pdo->prepare('SELECT estado, COUNT(*) AS total FROM lista_usuario_jogo WHERE usuario_id = :uid GROUP BY estado');
        $st->execute([':uid' => $uid]);
        $saida = ['quero' => 0, 'jogando' => 0, 'joguei' => 0, 'zerado' => 0];
        foreach ($st->fetchAll() as $l) {
            $saida[$l['estado']] = (int) $l['total'];
        }
        return $saida;
    }
}
