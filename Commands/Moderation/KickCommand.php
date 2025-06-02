<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

class KickCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName('kick')
            ->setDescription('Expulse un membre du serveur');

        $userOption = new Option($discord);
        $userOption
            ->setName('user')
            ->setDescription('ID de l’utilisateur à expulser')
            ->setType(6)
            ->setRequired(true);

        $reasonOption = new Option($discord);
        $reasonOption
            ->setName('reason')
            ->setDescription('Raison du kick')
            ->setType(3)
            ->setRequired(false);

        return $command->addOption($userOption)->addOption($reasonOption);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;
        $reason = 'Aucune raison spécifiée';

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'user') {
                $userId = $option->value;
            }
            if ($option->name === 'reason') {
                $reason = $option->value;
            }
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->kick_members) {
            $embed = new Embed($discord);
            $embed->setTitle("Accès refusé ❌");
            $embed->setDescription("Tu n’as pas la permission d’expulser des membres.");
            $embed->setColor(0xFF0000);

            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed)->setFlags(64)
            );
            return;
        }

        $guild = $interaction->guild;
        $guild->members->fetch($userId)->then(
            function ($target) use ($interaction, $discord, $userId, $reason) {
                $target->kick($reason)->then(
                    function () use ($interaction, $discord, $target, $reason) {
                        $embed = new Embed($discord);
                        $embed->setTitle("✅ Membre expulsé");
                        $embed->setDescription("**{$target->user->username}** a été expulsé.\n✏️ Raison : `$reason`");
                        $embed->setColor(0x00AAFF);

                        $interaction->respondWithMessage(
                            MessageBuilder::new()->addEmbed($embed)
                        );
                    },
                    function () use ($interaction, $discord, $userId) {
                        $embed = new Embed($discord);
                        $embed->setTitle("Erreur ❌");
                        $embed->setDescription("Impossible d’expulser l’utilisateur `$userId`.");
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
                $embed->setDescription("Utilisateur introuvable dans le serveur (`$userId`).");
                $embed->setColor(0xFF0000);

                $interaction->respondWithMessage(
                    MessageBuilder::new()->addEmbed($embed)->setFlags(64)
                );
            }
        );
    }
}
