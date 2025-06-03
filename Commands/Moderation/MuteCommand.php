<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;
use Events\LogColors;
use Events\ModLogger;

class MuteCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('mute')
            ->setDescription('Mute un membre pour une durée donnée')
            ->addOption(
                (new Option($discord))
                    ->setName('user')
                    ->setDescription('Utilisateur à mute')
                    ->setType(6) // USER
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('duration')
                    ->setDescription('Durée en minutes')
                    ->setType(4) // INTEGER
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('reason')
                    ->setDescription('Raison du mute')
                    ->setType(3)
                    ->setRequired(false)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;
        $reason = 'Aucune raison fournie';
        $durationMinutes = 0;

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'user') {
                $userId = $option->value;
            } elseif ($option->name === 'duration') {
                $durationMinutes = (int)$option->value;
            } elseif ($option->name === 'reason') {
                $reason = $option->value;
            }
        }

        if ($durationMinutes <= 0 || $durationMinutes > 40320) {
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed(
                (new Embed($discord))
                    ->setTitle("Erreur ❌")
                    ->setDescription("La durée doit être comprise entre 1 et 40320 minutes (28 jours).")
                    ->setColor(0xFF0000)
            )->setFlags(64));
            return;
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->moderate_members) {
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed(
                (new Embed($discord))
                    ->setTitle("Accès refusé")
                    ->setDescription("Permission `MODERATE_MEMBERS` manquante.")
                    ->setColor(0xFF0000)
            )->setFlags(64));
            return;
        }

        $guild = $interaction->guild;

        $guild->members->fetch($userId)->then(function ($target) use ($interaction, $discord, $reason, $userId, $durationMinutes, $guild) {

            $timeoutUntil = (new \DateTime())->add(new \DateInterval("PT{$durationMinutes}M"))->format(\DateTime::ATOM);

            $discord->getHttpClient()->patch("guilds/{$guild->id}/members/{$userId}", [
                'communication_disabled_until' => $timeoutUntil
            ], [
                'X-Audit-Log-Reason' => $reason
            ])->then(function () use ($interaction, $discord, $target, $reason, $userId, $durationMinutes, $guild) {
                // ➕ Enregistrement en BDD
                try {
                    $pdo = new \PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
                    $stmt = $pdo->prepare("INSERT INTO sanctions (user_id, type, reason, date, moderator_id, server_id) VALUES (?, 'mute', ?, NOW(), ?, ?)");
                    $stmt->execute([
                        $userId,
                        $reason,
                        $interaction->member->user->id,
                        $guild->id
                    ]);
                } catch (\PDOException $e) {
                    echo "Erreur BDD : " . $e->getMessage() . "\n";
                }

                // ✅ Embed confirmation
                $embed = new Embed($discord);
                $embed->setTitle("🔇 Membre muté");
                $embed->setDescription("**{$target->user->username}** a été muté pour `$durationMinutes` minute(s).\n✏️ Raison : `$reason`");
                $embed->setColor(0x9999FF);

                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));

                // 📋 Logger mod
                $user = $interaction->member?->user;
                $staffId = $user?->id ?? '0';

                ModLogger::logAction(
                    $discord,
                    $guild->id,
                    'Mute',
                    $userId,
                    $staffId,
                    $reason,
                    LogColors::get('Mute')
                );
            });
        });
    }
}
