<?php

namespace Commands\XP_Moderation;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use PDO;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class XpconfigCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $actionOption = new Option($discord, [
            'name' => 'action',
            'description' => 'view ou set',
            'type' => 3,
            'required' => true,
            'choices' => [
                ['name' => 'view', 'value' => 'view'],
                ['name' => 'set', 'value' => 'set'],
            ]
        ]);

        $keyOption = new Option($discord, [
            'name' => 'key',
            'description' => 'Clé à modifier (si action = set)',
            'type' => 3,
            'required' => false,
        ]);

        $valueOption = new Option($discord, [
            'name' => 'value',
            'description' => 'Valeur à attribuer (si action = set)',
            'type' => 3,
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
        $ownerId = $_ENV['OWNER_ID'] ?? null;
        if ($interaction->user->id !== $ownerId) {
            $embed = new Embed($interaction->discord);
            $embed->setTitle("🚫 Accès refusé")
                ->setDescription("Tu n'es pas autorisé à utiliser cette commande.")
                ->setColor(0xFF5555)
                ->setTimestamp();

            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->addEmbed($embed)
                    ->setFlags(64)
            );
            return;
        }

        $pdo = getPDO();
        $action = $interaction->data->options['action']->value;
        $key = $interaction->data->options['key']->value ?? null;
        $value = $interaction->data->options['value']->value ?? null;

        if ($action === 'view') {
            $stmt = $pdo->query("SELECT `key`, `value` FROM xp_settings");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$data) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()->setContent("⚠️ Aucune configuration XP trouvée."),
                    true
                );
                return;
            }

            $content = "**🛠️ Configuration XP actuelle :**\n";
            foreach ($data as $row) {
                $content .= "• `{$row['key']}` = `{$row['value']}`\n";
            }

            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent($content),
                true
            );
        }

        elseif ($action === 'set') {
            if (!$key || !$value) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()->setContent("❌ Utilisation : `/xpconfig set <key> <value>`"),
                    true
                );
                return;
            }

            $stmt = $pdo->prepare("INSERT INTO xp_settings (`key`, `value`) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$key, $value]);

            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("✅ Paramètre `{$key}` mis à jour avec la valeur `{$value}`"),
                true
            );
        }

        else {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Action invalide. Utilise `view` ou `set`."),
                true
            );
        }
    }
}
