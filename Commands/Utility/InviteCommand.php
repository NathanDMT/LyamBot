<?php

namespace Commands\Utility;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class InviteCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('invite')
            ->setDescription("Envoie le lien pour inviter le bot sur un serveur");
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $clientId = $_ENV['DISCORD_CLIENT_ID'] ?? null;

        if (!$clientId) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent("❌ Erreur : `DISCORD_CLIENT_ID` non défini dans le fichier `.env`."));
            return;
        }

        $inviteUrl = "https://discord.com/oauth2/authorize?client_id=$clientId&scope=bot%20applications.commands&permissions=8";

        $embed = new Embed($discord);
        $embed->setTitle("🤖 Invite le bot sur ton serveur !")
            ->setDescription("[Clique ici pour l'ajouter]($inviteUrl)")
            ->setColor(0x5865F2)
            ->setTimestamp();

        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed));
    }
}
