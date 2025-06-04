<?php

namespace Commands\XP;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class LevelupCommand
{
    public static function register(\Discord\Discord $discord): CommandBuilder
    {
        $option = new Option($discord, [
            'name' => 'etat',
            'description' => 'Activer ou désactiver les notifications',
            'type' => 3, // STRING
            'required' => true,
            'choices' => [
                ['name' => 'on', 'value' => 'on'],
                ['name' => 'off', 'value' => 'off'],
            ],
        ]);

        return CommandBuilder::new()
            ->setName('levelup-message')
            ->setDescription('Active ou désactive les messages de level-up.')
            ->addOption($option);
    }

    public static function handle(Interaction $interaction)
    {
        $pdo = getPDO();
        $options = $interaction->data->options;
        $etat = $options['etat']->value ?? 'off';
        $userId = $interaction->user->id;

        $pdo->prepare("UPDATE users_activity SET levelup_notify = ? WHERE user_id = ?")
            ->execute([$etat === 'on' ? 1 : 0, $userId]);

        $message = $etat === 'on'
            ? "🔔 Notifications de level-up activées !"
            : "🔕 Notifications de level-up désactivées.";

        $interaction->respondWithMessage(MessageBuilder::new()->setContent($message), true);
    }
}
