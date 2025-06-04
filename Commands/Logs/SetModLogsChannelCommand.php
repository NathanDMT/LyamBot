<?php

namespace Commands\Logs;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class SetModLogsChannelCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('setmodlogchannel')
            ->setDescription('Définit le salon et les événements à logger')
            ->addOption(
                (new Option($discord))
                    ->setName('channel')
                    ->setDescription('Salon de log modération')
                    ->setType(7) // CHANNEL
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('events')
                    ->setDescription("Événements à suivre (join,leave,boost,sanction) ou 'all'")
                    ->setType(3) // STRING
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $pdo = getPDO();
        $channelId = null;
        $eventsRaw = '';

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'channel') {
                $channelId = $option->value;
            } elseif ($option->name === 'events') {
                $eventsRaw = $option->value;
            }
        }

        $validEvents = ['join', 'leave', 'boost', 'sanction'];
        $events = [];

        if (strtolower(trim($eventsRaw)) === 'all') {
            $events = $validEvents;
        } else {
            $events = array_map('trim', explode(',', strtolower($eventsRaw)));
            $invalid = array_diff($events, $validEvents);

            if (!empty($invalid)) {
                $embed = (new Embed($discord))
                    ->setTitle("Événements invalides ❌")
                    ->setDescription("Les événements suivants sont invalides : `" . implode(', ', $invalid) . "`\nÉvénements valides : join, leave, boost, sanction.")
                    ->setColor(0xFF0000);

                $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed])->setFlags(64));
                return;
            }
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->administrator) {
            $embed = (new Embed($discord))
                ->setTitle("Accès refusé 🔒")
                ->setDescription("Tu dois être administrateur pour définir le salon de log.")
                ->setColor(0xFF0000);

            $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed])->setFlags(64));
            return;
        }

        $serverId = $interaction->guild_id;

        try {
            $stmt = $pdo->prepare("REPLACE INTO modlog_config (server_id, event_type, channel_id) VALUES (?, ?, ?)");
            foreach ($events as $eventType) {
                $stmt->execute([$serverId, $eventType, $channelId]);
            }
        } catch (\PDOException $e) {
            $embed = (new Embed($discord))
                ->setTitle("Erreur ❌")
                ->setDescription("Impossible d'enregistrer la configuration. Erreur : " . $e->getMessage())
                ->setColor(0xFF0000);

            $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed])->setFlags(64));
            return;
        }

        $channelMention = "<#$channelId>";
        $embed = (new Embed($discord))
            ->setTitle("✅ Configuration enregistrée")
            ->setDescription("Les événements `" . implode(', ', $events) . "` seront loggés dans $channelMention.")
            ->setColor(0x00FF00);

        $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed]));
    }
}
