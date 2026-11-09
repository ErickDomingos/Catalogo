<?php
declare(strict_types=1);

namespace GameShelf\Core;

/** Tratamento centralizado de erros: formato único; detalhes técnicos só em desenvolvimento. */
final class ErroHandler
{
    public static function responder(\Throwable $e, Logger $log, bool $desenvolvimento): Response
    {
        if ($e instanceof HttpException) {
            $erro = ['status' => $e->status, 'codigo' => $e->codigo, 'mensagem' => $e->getMessage()];
            if ($e->campos !== []) {
                $erro['campos'] = $e->campos;
            }
            return new Response($e->status, ['erro' => $erro], $e->cabecalhos);
        }

        $log->error('erro_interno', [
            'tipo' => get_class($e),
            'mensagem' => $e->getMessage(),
            'local' => basename($e->getFile()) . ':' . $e->getLine(),
        ]);

        $erro = [
            'status' => 500,
            'codigo' => 'erro_interno',
            'mensagem' => 'Ocorreu um erro interno. Tente novamente mais tarde.',
        ];
        if ($desenvolvimento) {
            $erro['debug'] = ['tipo' => get_class($e), 'mensagem' => $e->getMessage(), 'local' => $e->getFile() . ':' . $e->getLine()];
        }
        return new Response(500, ['erro' => $erro]);
    }
}
