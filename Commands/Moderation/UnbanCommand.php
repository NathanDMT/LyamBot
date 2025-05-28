<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

class UnbanCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName('unban')
            ->setDescription('Débannir un utilisateur via son ID');

        $userOption = new Option($discord);
        $userOption
            ->setName('userid')
            ->setDescription('ID de l’utilisateur à débannir')
            ->setType(3) // STRING
            ->setRequired(true);

        return $command->addOption($userOption);
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
            $embed->setTitle("Erreur ❌");
            $embed->setDescription("ID utilisateur non fourni.");
            $embed->setColor(0xFF0000);

            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed)->setFlags(64)
            );
            return;
        }

        $member = $interaction->member;

        if (!$member->getPermissions()->ban_members) {
            $embed = new Embed($discord);
            $embed->setTitle("Accès refusé 🔒");
            $embed->setDescription("Tu n’as pas la permission de débannir des membres.");
            $embed->setColor(0xFF8800);

            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed)->setFlags(64)
            );
            return;
        }

        $guild = $interaction->guild;

        // 🟡 On récupère l'utilisateur banni
        $guild->bans->fetch($userId)->then(
            function ($ban) use ($interaction, $discord, $guild, $userId) {
                $username = $ban->user->username;

                // 🔓 Suppression du bannissement
                $guild->bans->delete($userId)->then(
                    function () use ($interaction, $discord, $username) {
                        $embed = new Embed($discord);
                        $embed->setTitle("✅ Utilisateur débanni");
                        $embed->setDescription("**{$username}** a été débanni.");
                        $embed->setColor(0x00FF00);

                        $interaction->respondWithMessage(
                            MessageBuilder::new()->addEmbed($embed)
                        );
                    },
                    function () use ($interaction, $discord, $username) {
                        $embed = new Embed($discord);
                        $embed->setTitle("Erreur ❌");
                        $embed->setDescription("Impossible de débannir **{$username}**. Vérifie les permissions du bot.");
                        $embed->setColor(0xFF0000);

                        $interaction->respondWithMessage(
                            MessageBuilder::new()->addEmbed($embed)->setFlags(64)
                        );
                    }
                );
            },
            function () use ($interaction, $discord, $userId) {
                $embed = new Embed($discord);
                $embed->setTitle("Erreur ❌");
                $embed->setDescription("Aucun utilisateur banni avec l’ID `$userId`.");
                $embed->setColor(0xFF0000);

                $interaction->respondWithMessage(
                    MessageBuilder::new()->addEmbed($embed)->setFlags(64)
                );
            }
        );
    }
}