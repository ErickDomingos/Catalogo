<?php
declare(strict_types=1);

namespace GameShelf\Services;

use GameShelf\Core\HttpException;
use GameShelf\Core\Logger;
use GameShelf\Core\Validator;
use GameShelf\Dominio;
use GameShelf\Repositories\JogoRepositoryInterface;

/** Lógica de negócio do catálogo: visibilidade, permissões, validação e duplicidade. */
final class JogoService
{
    public function __construct(
        private readonly JogoRepositoryInterface $jogos,
        private readonly Logger $log,
    ) {
    }

    /**
     * @param array<string,mixed>|null $usuario
     * @param array<string,mixed> $query
     * @return array{dados:list<array<string,mixed>>,paginacao:array<string,int>}
     */
    public function listar(?array $usuario, array $query): array
    {
        $v = new Validator($query);
        $pagina = $v->inteiro('pagina', 'Página', 1, 100000) ?? 1;
        $porPagina = $v->inteiro('por_pagina', 'Itens por página', 1, 50) ?? 12;
        $filtro = [
            'busca' => $v->texto('busca', 'Busca', 1, 80, false),
            'genero' => $v->enum('genero', 'Gênero', $this->jogos->nomesGeneros(), false),
            'plataforma' => $v->enum('plataforma', 'Plataforma', Dominio::PLATAFORMAS, false),
            'escopo' => $v->enum('escopo', 'Escopo', ['todos', 'meus', 'oficiais'], false) ?? 'todos',
            'ordem' => $v->enum('ordem', 'Ordem', ['titulo', 'ano', 'nota', 'duracao'], false) ?? 'titulo',
            'direcao' => $v->enum('direcao', 'Direção', ['asc', 'desc'], false) ?? 'asc',
        ];
        $v->lancar();

        if ($filtro['escopo'] === 'meus' && $usuario === null) {
            throw new HttpException(401, 'Autenticação necessária.', [], 'nao_autenticado');
        }
        $filtro['uid'] = $usuario === null ? null : (int) $usuario['id'];
        $filtro['admin'] = ($usuario['perfil'] ?? '') === 'admin';

        $total = $this->jogos->contar($filtro);
        $itens = $this->jogos->buscar($filtro, $porPagina, ($pagina - 1) * $porPagina);

        return [
            'dados' => array_map(fn (array $j) => $this->apresentar($j, $usuario), $itens),
            'paginacao' => [
                'pagina' => $pagina,
                'por_pagina' => $porPagina,
                'total' => $total,
                'paginas' => max(1, (int) ceil($total / $porPagina)),
            ],
        ];
    }

    /** @param array<string,mixed>|null $usuario */
    public function obter(?array $usuario, int $id): array
    {
        return $this->apresentar($this->visivelOu404($id, $usuario), $usuario);
    }

    /**
     * Admin cria jogos do catálogo oficial (dono nulo); usuário comum cria jogos só seus.
     *
     * @param array<string,mixed> $usuario
     * @param array<string,mixed> $entrada
     */
    public function criar(array $usuario, array $entrada): array
    {
        $dados = $this->validar($entrada);
        $donoId = $usuario['perfil'] === 'admin' ? null : (int) $usuario['id'];

        if ($this->jogos->existeTitulo($dados['titulo'], $donoId, null)) {
            throw new HttpException(409, 'Já existe um jogo com este título.', ['titulo' => 'Título já cadastrado.'], 'conflito');
        }
        $dados['slug'] = 'jogo-' . bin2hex(random_bytes(6));
        $dados['dono_id'] = $donoId;
        $dados['capa'] = null;

        $id = $this->jogos->criar($dados);
        $this->log->info('jogo_criado', ['jogo_id' => $id, 'usuario_id' => (int) $usuario['id'], 'oficial' => $donoId === null]);

        return $this->apresentar($this->jogos->porId($id, (int) $usuario['id']) ?? [], $usuario);
    }

    /**
     * @param array<string,mixed> $usuario
     * @param array<string,mixed> $entrada
     */
    public function atualizar(array $usuario, int $id, array $entrada): array
    {
        $atual = $this->editavelOu403($id, $usuario);
        $dados = $this->validar($entrada);

        if ($this->jogos->existeTitulo($dados['titulo'], $atual['dono_id'], $id)) {
            throw new HttpException(409, 'Já existe um jogo com este título.', ['titulo' => 'Título já cadastrado.'], 'conflito');
        }
        $this->jogos->atualizar($id, $dados);
        $this->log->info('jogo_atualizado', ['jogo_id' => $id, 'usuario_id' => (int) $usuario['id']]);

        return $this->apresentar($this->jogos->porId($id, (int) $usuario['id']) ?? [], $usuario);
    }

    /** @param array<string,mixed> $usuario */
    public function excluir(array $usuario, int $id): void
    {
        $this->editavelOu403($id, $usuario);
        $this->jogos->excluir($id);
        $this->log->info('jogo_excluido', ['jogo_id' => $id, 'usuario_id' => (int) $usuario['id']]);
    }

    /** @return list<string> */
    public function generos(): array
    {
        return $this->jogos->nomesGeneros();
    }

    /**
     * Valida e sanitiza o corpo (PUT é substituição completa).
     *
     * @param array<string,mixed> $entrada
     * @return array<string,mixed>
     */
    public function validar(array $entrada): array
    {
        $v = new Validator($entrada);
        $dados = [
            'titulo' => $v->texto('titulo', 'Título', 1, 80),
            'estudio' => $v->texto('estudio', 'Desenvolvedora', 1, 100, false),
            'ano' => $v->inteiro('ano', 'Ano', 1950, 2100),
            'genero' => $v->enum('genero', 'Gênero', $this->jogos->nomesGeneros()),
            'console' => $v->enum('console', 'Console', array_keys(Dominio::CONSOLES), false),
            'plataformas' => $v->listaEnum('plataformas', 'Plataformas', Dominio::PLATAFORMAS),
            'duracao' => $v->inteiro('duracao', 'Duração (horas)', 0, 500),
            'metacritic' => $v->inteiro('metacritic', 'Nota da crítica', 0, 100),
            'comunidade' => $v->decimal('comunidade', 'Nota da comunidade', 0, 10, 0.1),
            'resenha' => $v->texto('resenha', 'Resenha', 1, 600, false, true),
        ];
        if (!$v->falhou()) {
            // O console escolhido também conta como plataforma (mesma regra do front do Trabalho 1).
            $familia = $dados['console'] === null ? null : Dominio::CONSOLES[$dados['console']];
            if ($familia !== null && !in_array($familia, $dados['plataformas'], true)) {
                $dados['plataformas'][] = $familia;
            }
            if ($dados['plataformas'] === [] && $dados['console'] === null) {
                $v->adicionarErro('plataformas', 'Selecione ao menos uma plataforma ou informe o console.');
            }
        }
        $v->lancar();
        return $dados;
    }

    /** @param array<string,mixed>|null $usuario */
    private function visivelOu404(int $id, ?array $usuario): array
    {
        $jogo = $this->jogos->porId($id, $usuario === null ? null : (int) $usuario['id']);
        $oficial = $jogo !== null && $jogo['dono_id'] === null;
        $meu = $jogo !== null && $usuario !== null && $jogo['dono_id'] === (int) $usuario['id'];
        $admin = ($usuario['perfil'] ?? '') === 'admin';
        if ($jogo === null || !($oficial || $meu || $admin)) {
            // 404 (e não 403) para não revelar que o jogo privado de outra pessoa existe.
            throw new HttpException(404, 'Jogo não encontrado.', [], 'nao_encontrado');
        }
        return $jogo;
    }

    /** @param array<string,mixed> $usuario */
    private function editavelOu403(int $id, array $usuario): array
    {
        $jogo = $this->visivelOu404($id, $usuario);
        if (!$this->podeEditar($jogo, $usuario)) {
            throw new HttpException(403, 'Você não tem permissão para alterar este jogo.', [], 'sem_permissao');
        }
        return $jogo;
    }

    /**
     * @param array<string,mixed> $jogo
     * @param array<string,mixed>|null $usuario
     */
    private function podeEditar(array $jogo, ?array $usuario): bool
    {
        if ($usuario === null) {
            return false;
        }
        return $usuario['perfil'] === 'admin' || ($jogo['dono_id'] !== null && $jogo['dono_id'] === (int) $usuario['id']);
    }

    /**
     * @param array<string,mixed> $jogo
     * @param array<string,mixed>|null $usuario
     * @return array<string,mixed>
     */
    private function apresentar(array $jogo, ?array $usuario): array
    {
        $jogo['oficial'] = $jogo['dono_id'] === null;
        $jogo['pode_editar'] = $this->podeEditar($jogo, $usuario);
        unset($jogo['dono_id']);
        return $jogo;
    }
}
