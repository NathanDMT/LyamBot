<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Events\LogColors;
use Events\ModLogger;

class UserinfoCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('userinfo')
            ->setDescription("Affiche les infos d'un utilisateur")
            ->addOption(
                (new Option($discord))
                    ->setName('userid')
                    ->setDescription("ID de l'utilisateur à inspecter (facultatif)")
                    ->setType(3) // STRING
                    ->setRequired(false)
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

        $guild = $interaction->guild;
        $staffUser = $interaction->member?->user;
        $staffTag = $staffUser?->username ?? 'Inconnu';
        $staffId = $staffUser?->id ?? '0';

        if ($userId) {
            $guild->members->fetch($userId)->then(
                function ($fetchedMember) use ($interaction, $discord, $userId, $staffTag, $staffId) {
                    self::sendUserInfo($interaction, $discord, $fetchedMember);

                    ModLogger::logAction(
                        $discord,
                        $interaction->guild_id,
                        'Consultation utilisateur',
                        $fetchedMember->user->id,
                        $staffId,
                        "Consultation de <@{$fetchedMember->user->id}> via /userinfo",
                        LogColors::get('Userinfo')
                    );
                },
                function () use ($interaction, $discord, $userId) {
                    $embed = new Embed($discord);
                    $embed->setTitle("Erreur ❌")
                        ->setDescription("Impossible de trouver l'utilisateur avec l'ID `$userId`.")
                        ->setColor(0xFF0000);
                    $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
                }
            );
        } else {
            self::sendUserInfo($interaction, $discord, $interaction->member);

            ModLogger::logAction(
                $discord,
                $interaction->guild_id,
                'Consultation utilisateur',
                $interaction->member->user->id,
                $staffId,
                "Consultation de soi-même via /userinfo",
                LogColors::get('Consultation utilisateur')
            );
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
        $embed->setTitle("🔎 Informations de l'utilisateur");
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
