<?php

namespace Commands\Owner;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class StopCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('stop')
            ->setDescription('Arrête le bot (owner uniquement)');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $ownerId = $_ENV['OWNER_ID'] ?? null;

        if ($interaction->user->id !== $ownerId) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Seul le propriétaire du bot peut exécuter cette commande.")
            );
            return;
        }

        // D'abord, on dit à Discord "je réponds dans un instant"
        $interaction->acknowledge()->then(function () use ($interaction, $discord) {
            // Ensuite, on envoie la vraie réponse
            $interaction->sendFollowUpMessage(
                MessageBuilder::new()->setContent("🛑 Le bot s’arrête dans 1 seconde...")
            );

            // Et on arrête le bot un peu après pour laisser le temps au message d'arriver
            sleep(1);
            $discord->close();
        });
    }
}
