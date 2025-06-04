<?php
namespace Commands\XP_Moderation;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class AddxpCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $userOption = new Option($discord, [
            'name' => 'utilisateur',
            'description' => 'Utilisateur à modifier',
            'type' => 6,
            'required' => true,
        ]);

        $intOption = new Option($discord, [
            'name' => 'valeur',
            'description' => "XP à ajouter",
            'type' => 4,
            'required' => true,
        ]);

        return CommandBuilder::new()
            ->setName('addxp')
            ->setDescription("Ajoute de l'XP à un utilisateur.")
            ->addOption($userOption)
            ->addOption($intOption);
    }

    public static function handle(Interaction $interaction)
    {
        $pdo = getPDO();
        $options = $interaction->data->options;

        $userId = null;
        $xpAjout = 0;

        foreach ($options as $option) {
            if ($option->name === 'utilisateur') {
                $userId = $option->value;
            } elseif ($option->name === 'valeur') {
                $xpAjout = (int)$option->value;
            }
        }

        if (!$userId || !$xpAjout) {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("❌ Paramètres manquants."), true);
            return;
        }

        $stmt = $pdo->prepare("SELECT xp FROM users_activity WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch();

        if ($data) {
            $newXp = $data['xp'] + $xpAjout;
            $newLevel = floor(sqrt($newXp / 100));
            $pdo->prepare("UPDATE users_activity SET xp = ?, level = ? WHERE user_id = ?")
                ->execute([$newXp, $newLevel, $userId]);

            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("✅ <@$userId> a reçu $xpAjout XP (total : $newXp, niveau $newLevel)"), true);
        } else {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("❌ Utilisateur introuvable dans la base."), true);
        }
    }
}