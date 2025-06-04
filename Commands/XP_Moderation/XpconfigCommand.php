<?php
namespace Commands\XP_Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;
use Discord\Builders\MessageBuilder;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class XpconfigCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $actionOption = new Option($discord, [
            'name' => 'action',
            'description' => 'view ou set',
            'type' => 3, // STRING
            'required' => true,
            'choices' => [
                ['name' => 'view', 'value' => 'view'],
                ['name' => 'set', 'value' => 'set'],
            ]
        ]);

        $keyOption = new Option($discord, [
            'name' => 'key',
            'description' => 'Clé à modifier (si action = set)',
            'type' => 3, // STRING
            'required' => false,
        ]);

        $valueOption = new Option($discord, [
            'name' => 'value',
            'description' => 'Valeur à attribuer (si action = set)',
            'type' => 3, // STRING
            'required' => false,
        ]);

        return CommandBuilder::new()
            ->setName('xpconfig')
            ->setDescription("Affiche ou modifie les paramètres XP")
            ->addOption($actionOption)
            ->addOption($keyOption)
            ->addOption($valueOption);
    }

    public static function handle(Interaction $interaction)
    {
        $pdo = getPDO();
        $action = $interaction->data->options['action']->value;
        $key = $interaction->data->options['key']->value ?? null;
        $value = $interaction->data->options['value']->value ?? null;

        if ($action === 'view') {
            $stmt = $pdo->query("SELECT `key`, `value` FROM xp_settings");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$data) {
                $interaction->respondWithMessage(MessageBuilder::new()
                    ->setContent("⚠️ Aucune configuration XP trouvée."), true);
                return;
            }

            $content = "**🛠️ Configuration XP actuelle :**\n";
            foreach ($data as $row) {
                $content .= "• `{$row['key']}` = `{$row['value']}`\n";
            }

            $interaction->respondWithMessage(MessageBuilder::new()->setContent($content), true);
        }

        elseif ($action === 'set') {
            if (!$key || !$value) {
                $interaction->respondWithMessage(MessageBuilder::new()
                    ->setContent("❌ Utilisation : `/xpconfig set <key> <value>`"), true);
                return;
            }

            // Update or insert
            $stmt = $pdo->prepare("INSERT INTO xp_settings (`key`, `value`) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$key, $value]);

            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("✅ Paramètre `{$key}` mis à jour avec la valeur `{$value}`"), true);
        }

        else {
            $interaction->respondWithMessage(MessageBuilder::new()
                ->setContent("❌ Action invalide. Utilise `view` ou `set`."), true);
        }
    }
}
