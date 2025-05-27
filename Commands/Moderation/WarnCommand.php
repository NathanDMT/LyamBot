<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class WarnCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'warn',
            'description' => 'Permet de mettre un warn à un utilisateur',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}