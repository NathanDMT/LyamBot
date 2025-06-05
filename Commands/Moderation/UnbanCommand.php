<?php

namespace Commands\Moderation;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Events\LogColors;
use Events\ModLogger;

class UnbanCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('unban')
            ->setDescription('Débannir un utilisateur via son ID')
            ->addOption(
                (new Option($discord))
                    ->setName('userid')
                    ->setDescription("ID de l’utilisateur à débannir")
                    ->setType(3)
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'userid') {
                $userId = $option->value;
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
                ->setDescription("Tu n’as pas la permission de débannir des membres.")
                ->setColor(0xFF8800);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $guild = $interaction->guild;
        $staffUser = $interaction->member?->user;
        $staffTag = $staffUser?->username ?? 'Inconnu';
        $staffId = $staffUser?->id ?? '0';

        // 🔍 Récupération de l'utilisateur banni
        $guild->bans->fetch($userId)->then(
            function ($ban) use ($interaction, $discord, $guild, $userId, $staffTag, $staffId) {
                $username = $ban->user->username;

                // ✅ Suppression du bannissement
                $guild->bans->delete($userId)->then(
                    function () use ($interaction, $discord, $guild, $username, $userId, $staffTag, $staffId) {
                        $embed = new Embed($discord);
                        $embed->setTitle("✅ Utilisateur débanni")
                            ->setDescription("**{$username}** a été débanni.")
                            ->setColor(0x00FF00);
                        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));

                        // 📝 Log
                        ModLogger::logAction(
                            $discord,
                            $guild->id,
                            'Unban',
                            $userId,
                            $staffId,
                            "Débannissement de **{$username}** via /unban",
                            LogColors::get('Unban')
                        );
                    },
                    function () use ($interaction, $discord, $username) {
                        $embed = new Embed($discord);
                        $embed->setTitle("Erreur ❌")
                            ->setDescription("Impossible de débannir **{$username}**. Vérifie les permissions du bot.")
                            ->setColor(0xFF0000);
                        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                    }
                );
            },
            function () use ($interaction, $discord, $userId) {
                $embed = new Embed($discord);
                $embed->setTitle("Erreur ❌")
                    ->setDescription("Aucun utilisateur banni avec l’ID `$userId`.")
                    ->setColor(0xFF0000);
                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            }
        );
    }
}
