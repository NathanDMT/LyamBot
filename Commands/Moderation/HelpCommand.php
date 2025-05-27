<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class HelpCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'help',
            'description' => 'Permet de lister toutes les commandes disponibles',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}