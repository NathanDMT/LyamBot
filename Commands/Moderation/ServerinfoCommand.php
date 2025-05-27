<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class ServerinfoCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'serverinfo',
            'description' => 'Permet d\'afficher les informations du server',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}