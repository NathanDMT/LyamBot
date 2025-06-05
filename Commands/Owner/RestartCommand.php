<?php

namespace Commands\Owner;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

class RestartCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('restart')
            ->setDescription('Redémarre proprement le bot (nécessite un processus externe pour relancer)');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $ownerId = $_ENV['OWNER_ID'] ?? null;
        if ($interaction->user->id !== $ownerId) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Tu n’as pas la permission de redémarrer le bot.")->setFlags(64)
            );
            return;
        }

        $interaction->respondWithMessage(
            MessageBuilder::new()->setContent("🔁 Redémarrage du bot...")->setFlags(64)
        )->then(function () {
            echo "🛑 Redémarrage demandé par le propriétaire\n";
            exit(0); // Arrêt du script
        });
    }
}
