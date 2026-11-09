<?php
declare(strict_types=1);

namespace GameShelf;

/** Regras de domínio fixas, compartilhadas por validação, serviços e testes. */
final class Dominio
{
    public const PERFIS = ['usuario', 'admin'];
    public const ESTADOS_LISTA = ['quero', 'jogando', 'joguei', 'zerado'];
    public const PLATAFORMAS = ['pc', 'playstation', 'xbox', 'switch'];

    /** Console da edição => família de plataforma (null quando não há família correspondente). */
    public const CONSOLES = [
        'pc' => 'pc', 'ps2' => 'playstation', 'ps3' => 'playstation', 'ps4' => 'playstation',
        'ps5' => 'playstation', 'xbox' => 'xbox', 'xbox360' => 'xbox', 'xboxone' => 'xbox',
        'xboxseries' => 'xbox', 'switch' => 'switch', 'switch2' => 'switch',
        'wii' => null, 'gamecube' => null, 'arcade' => null,
    ];
}
