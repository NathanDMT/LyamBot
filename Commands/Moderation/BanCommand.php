<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class BanCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'ban',
            'description' => 'Permet de ban une personne',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}