<?php
declare(strict_types=1);

namespace GameShelf\Services;

use GameShelf\Core\HttpException;
use GameShelf\Core\Validator;
use GameShelf\Dominio;
use GameShelf\Repositories\JogoRepositoryInterface;
use GameShelf\Repositories\ListaRepositoryInterface;

/** Lista pessoal (Quero jogar, Jogando, Já joguei, Zerado): um estado por jogo e por usuário. */
final class ListaService
{
    public function __construct(
        private readonly ListaRepositoryInterface $listas,
        private readonly JogoRepositoryInterface $jogos,
    ) {
    }

    /** @return array{itens:list<array<string,mixed>>,contagens:array<string,int>} */
    public function listar(int $uid): array
    {
        return ['itens' => $this->listas->doUsuario($uid), 'contagens' => $this->listas->contagens($uid)];
    }

    /** @param array<string,mixed> $entrada */
    public function definir(int $uid, int $jogoId, array $entrada): void
    {
        $v = new Validator($entrada);
        $estado = $v->enum('estado', 'Estado', Dominio::ESTADOS_LISTA);
        $v->lancar();
        $this->exigirJogoVisivel($uid, $jogoId);
        $this->listas->definir($uid, $jogoId, (string) $estado);
    }

    public function remover(int $uid, int $jogoId): void
    {
        $this->listas->remover($uid, $jogoId);
    }

    private function exigirJogoVisivel(int $uid, int $jogoId): void
    {
        $jogo = $this->jogos->porId($jogoId, $uid);
        if ($jogo === null || ($jogo['dono_id'] !== null && $jogo['dono_id'] !== $uid)) {
            throw new HttpException(404, 'Jogo não encontrado.', [], 'nao_encontrado');
        }
    }
}
