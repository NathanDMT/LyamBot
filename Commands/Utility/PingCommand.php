<?php

namespace Commands\Utility;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class PingCommand
{
    public static function register(Discord $discord)
    {
        return [
            'name' => 'ping',
            'description' => 'Répond pong',
        ];
    }

    public static function handle(Interaction $interaction)
    {
        $interaction->respondWithMessage(
            MessageBuilder::new()->setContent('🏓 Pong !')
        );
    }
}
