<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

class BanCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName('ban')
            ->setDescription('Bannir un utilisateur même s’il n’est pas dans le serveur');

        $userOption = new Option($discord);
        $userOption
            ->setName('user_id')
            ->setDescription('ID de l’utilisateur à bannir')
            ->setType(3) // STRING
            ->setRequired(true);

        $reasonOption = new Option($discord);
        $reasonOption
            ->setName('raison')
            ->setDescription('Raison du bannissement')
            ->setType(3)
            ->setRequired(false);

        return $command->addOption($userOption)->addOption($reasonOption);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;
        $reason = 'Aucune raison spécifiée';

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'user_id') {
                $userId = $option->value;
            }
            if ($option->name === 'raison') {
                $reason = $option->value;
            }
        }

        if (!$userId) {
            $embed = new Embed($discord);
            $embed->setTitle("Erreur ❌");
            $embed->setDescription("ID utilisateur non fourni.");
            $embed->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->ban_members) {
            $embed = new Embed($discord);
            $embed->setTitle("Accès refusé 🔒");
            $embed->setDescription("Tu n’as pas la permission de bannir des membres.");
            $embed->setColor(0xFF8800);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $guild = $interaction->guild;

        $discord->getHttpClient()->put("guilds/{$guild->id}/bans/{$userId}", [
            'delete_message_days' => 0,
            'reason' => $reason,
        ])->then(
            function () use ($interaction, $discord, $userId, $reason, $guild) {
                // ➕ Enregistrement dans la base de données
                try {
                    $pdo = new \PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
                    $stmt = $pdo->prepare("INSERT INTO sanctions (user_id, type, reason, date, moderator_id, server_id) VALUES (?, 'ban', ?, NOW(), ?, ?)");
                    $stmt->execute([
                        $userId,
                        $reason,
                        $interaction->user->id,
                        $guild->id
                    ]);
                } catch (\PDOException $e) {
                    echo "Erreur BDD : " . $e->getMessage() . "\n";
                }

                // ✅ Réponse utilisateur
                $embed = new Embed($discord);
                $embed->setTitle("✅ Utilisateur banni");
                $embed->setDescription("L'utilisateur avec l'ID `<@$userId>` a été banni.\n✏️ Raison : `$reason`");
                $embed->setColor(0x00AAFF);

                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));
            },
            function ($e) use ($interaction, $discord, $userId) {
                $embed = new Embed($discord);
                $embed->setTitle("Erreur ❌");
                $embed->setDescription("Impossible de bannir `$userId`. Raison : " . $e->getMessage());
                $embed->setColor(0xFF0000);

                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            }
        );
    }
}
