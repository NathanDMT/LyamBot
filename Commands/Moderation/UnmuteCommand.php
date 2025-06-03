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

class UnmuteCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('unmute')
            ->setDescription('Retire le mute (timeout) d’un membre')
            ->addOption(
                (new Option($discord))
                    ->setName('user')
                    ->setDescription('Utilisateur à unmute')
                    ->setType(6)
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'user') {
                $userId = $option->value;
            }
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->moderate_members) {
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed(
                (new Embed($discord))
                    ->setTitle("Accès refusé 🔒")
                    ->setDescription("Permission `MODERATE_MEMBERS` requise.")
                    ->setColor(0xFF0000)
            )->setFlags(64));
            return;
        }

        $guild = $interaction->guild;

        $guild->members->fetch($userId)->then(function ($target) use ($interaction, $discord, $guild, $userId) {
            $target->removeTimeout()->then(function () use ($interaction, $discord, $target, $guild, $userId) {
                // ➕ Enregistrement en BDD
                try {
                    $pdo = new \PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
                    $stmt = $pdo->prepare("INSERT INTO sanctions (user_id, type, reason, date, moderator_id, server_id) VALUES (?, 'unmute', ?, NOW(), ?, ?)");
                    $stmt->execute([
                        $userId,
                        'Unmute manuel',
                        $interaction->member->user->id,
                        $guild->id
                    ]);
                } catch (\PDOException $e) {
                    echo "Erreur BDD : " . $e->getMessage() . "\n";
                }

                // ✅ Embed confirmation
                $embed = new Embed($discord);
                $embed->setTitle("🔊 Membre unmute");
                $embed->setDescription("**{$target->user->username}** n’est plus muté.");
                $embed->setColor(0x33CC33);

                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));

                // 📋 Logger mod
                $user = $interaction->member?->user;
                $staffId = $user?->id ?? '0';

                ModLogger::logAction(
                    $discord,
                    $guild->id,
                    'Unmute',
                    $userId,
                    $staffId,
                    'Unmute manuel',
                    LogColors::get('Unmute')
                );
            });
        }, function () use ($interaction, $discord, $userId) {
            $embed = new Embed($discord);
            $embed->setTitle("Erreur ❌");
            $embed->setDescription("Utilisateur `$userId` introuvable.");
            $embed->setColor(0xFF0000);

            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed)->setFlags(64)
            );
        });
    }
}
