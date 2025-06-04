<?php

namespace Commands\XP_Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;
use Discord\Builders\MessageBuilder;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class ResetxpCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $userOption = new Option($discord, [
            'name' => 'utilisateur',
            'description' => 'Utilisateur à réinitialiser',
            'type' => 6,
            'required' => true,
        ]);

        return CommandBuilder::new()
            ->setName('resetxp')
            ->setDescription("Réinitialise l'XP d’un utilisateur.")
            ->addOption($userOption);
    }

    public static function handle(Interaction $interaction)
    {
        $pdo = getPDO();
        $options = $interaction->data->options;
        $userId = null;

        foreach ($options as $option) {
            if ($option->name === 'utilisateur') {
                $userId = $option->value;
                break;
            }
        }

        if (!$userId) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("❌ Utilisateur non spécifié."), true);
            return;
        }

        $pdo->prepare("UPDATE users_activity SET xp = 0, level = 1 WHERE user_id = ?")
            ->execute([$userId]);

        $interaction->respondWithMessage(MessageBuilder::new()
            ->setContent("🔁 XP de <@$userId> réinitialisé à 0 (niveau 1)"), true);
    }
}
