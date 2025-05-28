<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class PingCommand
{
    public static function register(Discord $discord)
    {
        return CommandBuilder::new()
            ->setName('ping')
            ->setDescription('Affiche le ping de la connexion du bot.');
    }

    public static function handle(Interaction $interaction, Discord $discord)
    {
        $start = microtime(true);

        $interaction->respondWithMessage(
            MessageBuilder::new()->setContent('⏳ Calcul du ping...')
        )->then(function () use ($interaction, $discord, $start) {
            $latence = round((microtime(true) - $start) * 1000);

            $embed = new Embed($discord);
            $embed->setTitle("🏓 Pong !")
                ->addFieldValues("Latence HTTP", "{$latence}ms", true)
                ->setColor(0x00ffcc)
                ->setTimestamp();

            // Modifier la réponse initiale
            $interaction->getOriginalResponse()->then(function ($response) use ($embed) {
                $response->edit(MessageBuilder::new()->addEmbed($embed));
            });
        });
    }
}
