<?php

namespace Commands\Utility;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class PollCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'poll',
            'description' => 'Permet de créer un sondage',
        ];
    }

    public static function handle(Interaction $interaction)
    { }
}