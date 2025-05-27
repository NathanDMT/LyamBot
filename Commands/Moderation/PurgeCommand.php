<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class PurgeCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'purge',
            'description' => 'Permet de supprimer un certains nombre de message',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}