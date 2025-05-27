<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class UserinfoCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'userinfo',
            'description' => 'Permet d\'afficher les informations de l\'utilisateur',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}