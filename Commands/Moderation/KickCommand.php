<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class KickCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'kick',
            'description' => 'Permet de kick une personne',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}