<?php

namespace Commands\Game;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Discord;

class DiceCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('dice')
            ->setDescription('Lance un dé à 6 faces');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $roll = rand(1, 6);

        $interaction->respondWithMessage(
            MessageBuilder::new()->setContent("🎲 Tu as lancé un **$roll** !")
        );
    }
}
