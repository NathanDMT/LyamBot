<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

class UserinfoCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName('userinfo')
            ->setDescription("Affiche les infos d'un utilisateur");

        $userIdOption = new Option($discord);
        $userIdOption
            ->setName('userid')
            ->setDescription("ID de l'utilisateur à inspecter (facultatif)")
            ->setType(3) // STRING
            ->setRequired(false);

        return $command->addOption($userIdOption);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'userid') {
                $userId = $option->value;
            }
        }

        $guild = $interaction->guild;
        $target = $interaction->member;
        $user = $target->user;

        if ($userId) {
            $guild->members->fetch($userId)->then(
                function ($fetchedMember) use ($interaction, $discord) {
                    self::sendUserInfo($interaction, $discord, $fetchedMember);
                },
                function () use ($interaction, $discord, $userId) {
                    $embed = new Embed($discord);
                    $embed->setTitle("Erreur ❌");
                    $embed->setDescription("Impossible de trouver l'utilisateur avec l'ID `$userId`.");
                    $embed->setColor(0xFF0000);
                    $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                }
            );
        } else {
            self::sendUserInfo($interaction, $discord, $target);
        }
    }

    private static function sendUserInfo(Interaction $interaction, Discord $discord, $member): void
    {
        $user = $member->user;

        // Calcul du timestamp via Snowflake
        $discordEpoch = 1420070400000;
        $createdAtMs = (int)(bcdiv((string)$user->id, '4194304'));
        $createdAt = (int)(($createdAtMs + $discordEpoch) / 1000);

        $embed = new Embed($discord);
        $embed->addFieldValues("👥 Utilisateur", "<@{$user->id}>", true);
        $embed->setThumbnail($user->avatar);
        $embed->setColor(0x5865F2);
        $embed->addFieldValues("🆔 ID", $user->id, false);
        $embed->addFieldValues("📅 Créé le", "<t:$createdAt:F>", false);
        $embed->addFieldValues("📛 Pseudo sur le serveur", $member->nick ?? 'Aucun', false);
        $embed->addFieldValues("🤖 Bot", $user->bot ? "Oui" : "Non", false);

        $interaction->respondWithMessage(
            MessageBuilder::new()->addEmbed($embed)
        );
    }
}
