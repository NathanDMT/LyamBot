<?php

namespace Commands\Game;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class CoinflipCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('coinflip')
            ->setDescription('Lance une pièce et retourne pile ou face');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $result = rand(0, 1) === 0 ? '🪙 Pile !' : '🪙 Face !';

        $interaction->respondWithMessage(
            MessageBuilder::new()->setContent("Résultat : **$result**")
        );
    }
}
