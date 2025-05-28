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
            ->setDescription('Bannir un membre du serveur');

        $userOption = new Option($discord);
        $userOption
            ->setName('utilisateur')
            ->setDescription('Utilisateur à bannir')
            ->setType(6) // USER
            ->setRequired(true);

        $reasonOption = new Option($discord);
        $reasonOption
            ->setName('raison')
            ->setDescription('Raison du bannissement')
            ->setType(3) // STRING
            ->setRequired(false);

        return $command->addOption($userOption)->addOption($reasonOption);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;
        $reason = 'Aucune raison spécifiée';

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'utilisateur') {
                $userId = $option->value;
            }
            if ($option->name === 'raison') {
                $reason = $option->value;
            }
        }

        if (!$userId) {
            $embed = new Embed($discord);
            $embed->setTitle("Erreur ❌");
            $embed->setDescription("Utilisateur non spécifié.");
            $embed->setColor(0xFF0000);

            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->ban_members) {
            $embed = new Embed($discord);
            $embed->setTitle("Permission refusée 🔒");
            $embed->setDescription("Tu n’as pas la permission de bannir des membres.");
            $embed->setColor(0xFF8800);

            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $guild = $interaction->guild;

        $guild->members->fetch($discord->id)->then(
            function ($botMember) use ($guild, $userId, $interaction, $discord, $reason) {
                if (!$botMember->getPermissions()->ban_members) {
                    $embed = new Embed($discord);
                    $embed->setTitle("Permission manquante pour le bot ⚠️");
                    $embed->setDescription("Le bot n’a pas la permission de bannir.");
                    $embed->setColor(0xFFA500);

                    $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                    return;
                }

                $guild->members->fetch($userId)->then(
                    function ($member) use ($interaction, $discord, $reason) {
                        $member->ban(0)->then(
                            function () use ($interaction, $discord, $member, $reason) {
                                $embed = new Embed($discord);
                                $embed->setTitle("✅ Utilisateur banni");
                                $embed->setDescription("**{$member->user->username}** a été banni.\n✏️ Raison : `$reason`");
                                $embed->setColor(0x00AAFF);

                                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));
                            },
                            function () use ($interaction, $discord) {
                                $embed = new Embed($discord);
                                $embed->setTitle("Erreur ❌");
                                $embed->setDescription("Impossible de bannir l'utilisateur.");
                                $embed->setColor(0xFF0000);

                                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                            }
                        );
                    },
                    function () use ($interaction, $discord) {
                        $embed = new Embed($discord);
                        $embed->setTitle("Erreur ❌");
                        $embed->setDescription("Utilisateur introuvable.");
                        $embed->setColor(0xFF0000);

                        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                    }
                );
            }
        );
    }
}
