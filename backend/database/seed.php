<?php
declare(strict_types=1);

/**
 * Carrega os 128 jogos do Trabalho 1 (database/jogos.json) no catálogo oficial.
 * Idempotente: pode ser executado várias vezes.   Uso:  php database/seed.php
 */

use GameShelf\Core\{Database, Env};

require __DIR__ . '/../src/autoload.php';
Env::carregar(__DIR__ . '/../.env');

$jogos = json_decode((string) file_get_contents(__DIR__ . '/jogos.json'), true, 512, JSON_THROW_ON_ERROR);
$pdo = Database::conexao();

$existe = $pdo->prepare('SELECT 1 FROM jogos WHERE slug = :slug');
$insere = $pdo->prepare(
    'INSERT INTO jogos (slug, titulo, estudio, ano, genero_id, duracao_horas, metacritic, resenha, capa, dono_id)
     VALUES (:slug, :titulo, :estudio, :ano, (SELECT id FROM generos WHERE nome = :genero),
             :duracao, :metacritic, :resenha, :capa, NULL)'
);
$liga = $pdo->prepare('INSERT INTO jogo_plataforma (jogo_id, plataforma_id) SELECT :jogo, id FROM plataformas WHERE chave = :chave');

$novos = 0;
$pdo->beginTransaction();
try {
    foreach ($jogos as $j) {
        $existe->execute([':slug' => $j['slug']]);
        if ($existe->fetchColumn() !== false) {
            continue;
        }
        $insere->execute([
            ':slug' => $j['slug'],
            ':titulo' => $j['title'],
            ':estudio' => $j['developer'] ?? null,
            ':ano' => $j['year'] ?? null,
            ':genero' => $j['genre'],
            ':duracao' => $j['hours'] ?? null,
            ':metacritic' => $j['score'] ?? null,
            ':resenha' => $j['description'] ?? null,
            ':capa' => 'img/capas/' . $j['slug'] . '.jpg',
        ]);
        $id = (int) $pdo->lastInsertId();
        foreach ($j['platformKeys'] as $chave) {
            $liga->execute([':jogo' => $id, ':chave' => $chave]);
        }
        $novos++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Falha no seed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo "Seed concluído: {$novos} jogo(s) novo(s), " . (count($jogos) - $novos) . " já existiam.\n";
