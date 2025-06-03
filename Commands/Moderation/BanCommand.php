<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;
use Events\ModLogger;
use Events\LogColors;

class BanCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('ban')
            ->setDescription('Bannir un utilisateur même s’il n’est pas dans le serveur')
            ->addOption(
                (new Option($discord))
                    ->setName('user_id')
                    ->setDescription('ID de l’utilisateur à bannir')
                    ->setType(3)
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('raison')
                    ->setDescription('Raison du bannissement')
                    ->setType(3)
                    ->setRequired(false)
            );
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
            $embed->setTitle("Erreur ❌")
                ->setDescription("ID utilisateur non fourni.")
                ->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->ban_members) {
            $embed = new Embed($discord);
            $embed->setTitle("Accès refusé 🔒")
                ->setDescription("Tu n’as pas la permission de bannir des membres.")
                ->setColor(0xFF8800);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $guild = $interaction->guild;
        $staffUser = $interaction->member?->user;
        $staffTag = $staffUser?->username ?? 'Inconnu';
        $staffId = $staffUser?->id ?? '0';

        $discord->getHttpClient()->put("guilds/{$guild->id}/bans/{$userId}", [
            'delete_message_days' => 0,
            'reason' => $reason,
        ])->then(
            function () use ($interaction, $discord, $userId, $reason, $guild, $staffId, $staffTag) {
                try {
                    $pdo = new \PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
                    $stmt = $pdo->prepare("INSERT INTO sanctions (user_id, type, reason, date, moderator_id, server_id) VALUES (?, 'ban', ?, NOW(), ?, ?)");
                    $stmt->execute([$userId, $reason, $staffId, $guild->id]);
                } catch (\PDOException $e) {
                    echo "Erreur BDD : " . $e->getMessage() . "\n";
                }

                $embed = new Embed($discord);
                $embed->setTitle("✅ Utilisateur banni")
                    ->setDescription("L'utilisateur <@$userId> a été banni.\n✏️ Raison : `$reason`")
                    ->setColor(0xFF0000);

                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));

                ModLogger::logAction(
                    $discord,
                    $guild->id,
                    'Ban',
                    $userId,
                    $staffId,
                    "Bannissement de <@$userId>\n✏️ `$reason`",
                    LogColors::get('Ban')
                );
            },
            function ($e) use ($interaction, $discord, $userId) {
                $embed = new Embed($discord);
                $embed->setTitle("Erreur ❌")
                    ->setDescription("Impossible de bannir `<@$userId>`.\nRaison : " . $e->getMessage())
                    ->setColor(0xFF0000);
                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            }
        );
    }
}
