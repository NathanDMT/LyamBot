<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class ServerinfoCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('serverinfo')
            ->setDescription('Affiche des informations sur le serveur');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $guild = $interaction->guild;

        if (!$guild) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Impossible de récupérer les informations du serveur.")->setFlags(64)
            );
            return;
        }

        // Conversion manuelle de l'ID Discord en timestamp (Discord Snowflake)
        $discordEpoch = 1420070400000;
        $snowflakeTimeMs = (int) (bcdiv((string) $guild->id, '4194304')); // en millisecondes
        $timestamp = (int) (($snowflakeTimeMs + $discordEpoch) / 1000); // convertir en secondes

        $formattedCreation = "<t:$timestamp:F>";


        $locales = [
            'en-US' => '🇺🇸 Etats-Unis',
            'en-GB' => '🇬🇧 Royaume-Uni',
            'fr'    => '🇫🇷 France',
            'de'    => '🇩🇪 Allemagne',
            'es-ES' => '🇪🇸 Espagne',
            'it'    => '🇮🇹 Italie',
            'ja'    => '🇯🇵 Japon',
            'ko'    => '🇰🇷 Corée',
            'pt-BR' => '🇧🇷 Portugal (Brésil)',
            'pt-PT' => 'Portugal',
            'ru'    => '🇷🇺 Russie',
            'zh-CN' => '🇨🇳 Chine',
        ];

        $locale = $guild->preferred_locale ?? 'unknown';
        $regionDisplay = $locales[$locale] ?? "🌍 Inconnue";

        $embed = new Embed($discord);
        $embed->setTitle("📊 Informations du serveur");
        $embed->setDescription("Voici les informations de **{$guild->name}**");
        $embed->setThumbnail($guild->icon);
        $embed->setColor(0x00AAFF);
        $embed->addFieldValues("🆔 ID", $guild->id, true);
        $embed->addFieldValues("👑 Propriétaire", "<@{$guild->owner_id}>", true);
        $embed->addFieldValues("👥 Membres", (string) ($guild->member_count ?? 'Chargement...'), true);
        $embed->addFieldValues("📅 Création", $formattedCreation, true);
        $embed->addFieldValues("🌍 Pays", $regionDisplay, true);

        $interaction->respondWithMessage(
            MessageBuilder::new()->addEmbed($embed)
        );
    }
}
