<?php
declare(strict_types=1);

namespace GameShelf\Repositories;

use PDO;
use PDOStatement;

/** Acesso a dados de jogos. Valores sempre vão por parâmetros; nomes de coluna vêm de whitelist. */
final class JogoRepository implements JogoRepositoryInterface
{
    private const COLUNAS_ORDEM = [
        'titulo' => 'j.titulo',
        'ano' => 'j.ano',
        'nota' => 'j.metacritic',
        'duracao' => 'j.duracao_horas',
    ];

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function buscar(array $filtro, int $limite, int $offset): array
    {
        [$where, $params] = $this->condicoes($filtro);
        $coluna = self::COLUNAS_ORDEM[$filtro['ordem'] ?? 'titulo'] ?? 'j.titulo';
        $direcao = ($filtro['direcao'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
        $uid = $filtro['uid'] ?? null;

        $sql = 'SELECT j.id, j.titulo, j.estudio, j.ano, g.nome AS genero, j.console,
                       j.duracao_horas AS duracao, j.metacritic, j.comunidade, j.resenha, j.capa, j.dono_id,
                       l.estado AS minha_lista
                FROM jogos j
                JOIN generos g ON g.id = j.genero_id
                LEFT JOIN lista_usuario_jogo l ON l.jogo_id = j.id AND l.usuario_id = :lista_uid
                WHERE ' . $where . "
                ORDER BY ({$coluna} IS NULL), {$coluna} {$direcao}, j.id ASC
                LIMIT :limite OFFSET :offset";

        $st = $this->pdo->prepare($sql);
        $this->vincular($st, $params + [':lista_uid' => $uid]);
        $st->bindValue(':limite', $limite, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $this->completar($st->fetchAll());
    }

    public function contar(array $filtro): int
    {
        [$where, $params] = $this->condicoes($filtro);
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM jogos j JOIN generos g ON g.id = j.genero_id WHERE ' . $where);
        $this->vincular($st, $params);
        $st->execute();
        return (int) $st->fetchColumn();
    }

    public function porId(int $id, ?int $uid = null): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT j.id, j.titulo, j.estudio, j.ano, g.nome AS genero, j.console,
                    j.duracao_horas AS duracao, j.metacritic, j.comunidade, j.resenha, j.capa, j.dono_id,
                    l.estado AS minha_lista
             FROM jogos j
             JOIN generos g ON g.id = j.genero_id
             LEFT JOIN lista_usuario_jogo l ON l.jogo_id = j.id AND l.usuario_id = :uid
             WHERE j.id = :id'
        );
        $this->vincular($st, [':uid' => $uid, ':id' => $id]);
        $st->execute();
        $linha = $st->fetch();
        return $linha === false ? null : $this->completar([$linha])[0];
    }

    public function criar(array $dados): int
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare(
                'INSERT INTO jogos (slug, titulo, estudio, ano, genero_id, console, duracao_horas, metacritic, comunidade, resenha, capa, dono_id)
                 VALUES (:slug, :titulo, :estudio, :ano, (SELECT id FROM generos WHERE nome = :genero), :console,
                         :duracao, :metacritic, :comunidade, :resenha, :capa, :dono_id)'
            );
            $this->vincular($st, [
                ':slug' => $dados['slug'], ':titulo' => $dados['titulo'], ':estudio' => $dados['estudio'],
                ':ano' => $dados['ano'], ':genero' => $dados['genero'], ':console' => $dados['console'],
                ':duracao' => $dados['duracao'], ':metacritic' => $dados['metacritic'],
                ':comunidade' => $dados['comunidade'], ':resenha' => $dados['resenha'],
                ':capa' => $dados['capa'] ?? null, ':dono_id' => $dados['dono_id'],
            ]);
            $st->execute();
            $id = (int) $this->pdo->lastInsertId();
            $this->gravarPlataformas($id, $dados['plataformas']);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function atualizar(int $id, array $dados): void
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare(
                'UPDATE jogos SET titulo = :titulo, estudio = :estudio, ano = :ano,
                        genero_id = (SELECT id FROM generos WHERE nome = :genero), console = :console,
                        duracao_horas = :duracao, metacritic = :metacritic, comunidade = :comunidade, resenha = :resenha
                 WHERE id = :id'
            );
            $this->vincular($st, [
                ':titulo' => $dados['titulo'], ':estudio' => $dados['estudio'], ':ano' => $dados['ano'],
                ':genero' => $dados['genero'], ':console' => $dados['console'], ':duracao' => $dados['duracao'],
                ':metacritic' => $dados['metacritic'], ':comunidade' => $dados['comunidade'],
                ':resenha' => $dados['resenha'], ':id' => $id,
            ]);
            $st->execute();
            $this->pdo->prepare('DELETE FROM jogo_plataforma WHERE jogo_id = :id')->execute([':id' => $id]);
            $this->gravarPlataformas($id, $dados['plataformas']);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): void
    {
        $this->pdo->prepare('DELETE FROM jogos WHERE id = :id')->execute([':id' => $id]);
    }

    public function existeTitulo(string $titulo, ?int $donoId, ?int $ignorarId): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM jogos WHERE titulo = :titulo AND dono_id <=> :dono AND id <> :ignorar LIMIT 1');
        $this->vincular($st, [':titulo' => $titulo, ':dono' => $donoId, ':ignorar' => $ignorarId ?? 0]);
        $st->execute();
        return $st->fetchColumn() !== false;
    }

    public function nomesGeneros(): array
    {
        return array_map('strval', $this->pdo->query('SELECT nome FROM generos ORDER BY nome')->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param array<string,mixed> $f
     * @return array{0:string,1:array<string,mixed>}
     */
    private function condicoes(array $f): array
    {
        $onde = ['1 = 1'];
        $p = [];
        $uid = $f['uid'] ?? null;
        $escopo = $f['escopo'] ?? 'todos';

        if (!($f['admin'] ?? false)) {
            if ($uid !== null) {
                $onde[] = '(j.dono_id IS NULL OR j.dono_id = :vis_uid)';
                $p[':vis_uid'] = $uid;
            } else {
                $onde[] = 'j.dono_id IS NULL';
            }
        }
        if ($escopo === 'oficiais') {
            $onde[] = 'j.dono_id IS NULL';
        } elseif ($escopo === 'meus') {
            $onde[] = 'j.dono_id = :esc_uid';
            $p[':esc_uid'] = $uid ?? 0;
        }
        if (!empty($f['busca'])) {
            $padrao = '%' . addcslashes((string) $f['busca'], '%_\\') . '%';
            $onde[] = '(j.titulo LIKE :busca_t OR j.estudio LIKE :busca_e)';
            $p[':busca_t'] = $padrao;
            $p[':busca_e'] = $padrao;
        }
        if (!empty($f['genero'])) {
            $onde[] = 'g.nome = :genero';
            $p[':genero'] = $f['genero'];
        }
        if (!empty($f['plataforma'])) {
            $onde[] = 'EXISTS (SELECT 1 FROM jogo_plataforma jp JOIN plataformas pl ON pl.id = jp.plataforma_id
                               WHERE jp.jogo_id = j.id AND pl.chave = :plataforma)';
            $p[':plataforma'] = $f['plataforma'];
        }
        return [implode(' AND ', $onde), $p];
    }

    /** @param array<string,mixed> $params */
    private function vincular(PDOStatement $st, array $params): void
    {
        foreach ($params as $nome => $valor) {
            $tipo = match (true) {
                $valor === null => PDO::PARAM_NULL,
                is_int($valor) => PDO::PARAM_INT,
                is_bool($valor) => PDO::PARAM_BOOL,
                default => PDO::PARAM_STR,
            };
            $st->bindValue($nome, $valor, $tipo);
        }
    }

    /** @param list<string> $chaves */
    private function gravarPlataformas(int $jogoId, array $chaves): void
    {
        $st = $this->pdo->prepare('INSERT INTO jogo_plataforma (jogo_id, plataforma_id) SELECT :jogo, id FROM plataformas WHERE chave = :chave');
        foreach ($chaves as $chave) {
            $st->execute([':jogo' => $jogoId, ':chave' => $chave]);
        }
    }

    /**
     * @param list<array<string,mixed>> $linhas
     * @return list<array<string,mixed>>
     */
    private function completar(array $linhas): array
    {
        if ($linhas === []) {
            return [];
        }
        $ids = array_map(fn (array $l) => (int) $l['id'], $linhas);
        // Só a quantidade de "?" é montada dinamicamente; os valores vão como parâmetros.
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->pdo->prepare(
            "SELECT jp.jogo_id, pl.chave FROM jogo_plataforma jp JOIN plataformas pl ON pl.id = jp.plataforma_id
             WHERE jp.jogo_id IN ({$marcas}) ORDER BY pl.id"
        );
        $st->execute($ids);
        $mapa = [];
        foreach ($st->fetchAll() as $r) {
            $mapa[(int) $r['jogo_id']][] = $r['chave'];
        }
        foreach ($linhas as &$l) {
            $l['id'] = (int) $l['id'];
            $l['ano'] = $l['ano'] === null ? null : (int) $l['ano'];
            $l['duracao'] = $l['duracao'] === null ? null : (int) $l['duracao'];
            $l['metacritic'] = $l['metacritic'] === null ? null : (int) $l['metacritic'];
            $l['comunidade'] = $l['comunidade'] === null ? null : (float) $l['comunidade'];
            $l['dono_id'] = $l['dono_id'] === null ? null : (int) $l['dono_id'];
            $l['plataformas'] = $mapa[$l['id']] ?? [];
        }
        return $linhas;
    }
}
