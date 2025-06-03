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

class KickCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('kick')
            ->setDescription('Expulse un membre du serveur')
            ->addOption(
                (new Option($discord))
                    ->setName('user')
                    ->setDescription('ID de l’utilisateur à expulser')
                    ->setType(6)
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('reason')
                    ->setDescription('Raison du kick')
                    ->setType(3)
                    ->setRequired(false)
            );
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
            $embed->setTitle("Accès refusé ❌")
                ->setDescription("Tu n’as pas la permission d’expulser des membres.")
                ->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $staffUser = $interaction->member?->user;
        $staffTag = $staffUser?->username ?? 'Inconnu';
        $staffId = $staffUser?->id ?? '0';

        $guild = $interaction->guild;

        $guild->members->fetch($userId)->then(
            function ($target) use ($interaction, $discord, $guild, $userId, $reason, $staffTag, $staffId) {
                $target->kick($reason)->then(
                    function () use ($interaction, $discord, $target, $reason, $guild, $staffTag, $staffId) {
                        $embed = new Embed($discord);
                        $embed->setTitle("✅ Membre expulsé")
                            ->setDescription("**{$target->user->username}** a été expulsé.\n✏️ Raison : `$reason`")
                            ->setColor(0x00AAFF);
                        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));

                        // Log
                        ModLogger::logAction(
                            $discord,
                            $guild->id,
                            'Kick',
                            $target->user->id,
                            $staffId,
                            "Expulsion de **{$target->user->username}**\n✏️ `$reason`",
                            LogColors::get('Kick')
                        );
                    },
                    function () use ($interaction, $discord, $userId) {
                        $embed = new Embed($discord);
                        $embed->setTitle("Erreur ❌")
                            ->setDescription("Impossible d’expulser l’utilisateur `$userId`.")
                            ->setColor(0xFF0000);
                        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                    }
                );
            },
            function () use ($interaction, $discord, $userId) {
                $embed = new Embed($discord);
                $embed->setTitle("Erreur ❌")
                    ->setDescription("Utilisateur introuvable dans le serveur (`$userId`).")
                    ->setColor(0xFF0000);
                $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            }
        );
    }
}
