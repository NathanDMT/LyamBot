<?php


namespace Commands\Events;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class SetAnnonceChannelCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $eventTypeOption = new Option($discord);
        $eventTypeOption
            ->setName('event_type')
            ->setDescription('Type d’événement')
            ->setType(3) // STRING
            ->setRequired(true)
            ->addChoice((new Choice($discord))->setName('join')->setValue('join'))
            ->addChoice((new Choice($discord))->setName('leave')->setValue('leave'))
            ->addChoice((new Choice($discord))->setName('boost')->setValue('boost'))
            ->addChoice((new Choice($discord))->setName('all')->setValue('all'));

        $channelOption = new Option($discord);
        $channelOption
            ->setName('channel')
            ->setDescription('Salon à utiliser pour cet événement')
            ->setType(7) // CHANNEL
            ->setRequired(true);

        $etatOption = new Option($discord);
        $etatOption
            ->setName('etat')
            ->setDescription("Activer ou désactiver l'annonce de cet événement")
            ->setType(3)
            ->setRequired(true)
            ->addChoice((new Choice($discord))->setName('activer')->setValue('1'))
            ->addChoice((new Choice($discord))->setName('désactiver')->setValue('0'));

        return CommandBuilder::new()
            ->setName('setannoncechannel')
            ->setDescription('Définit le salon pour un ou plusieurs événements et active/désactive l’annonce')
            ->addOption($eventTypeOption)
            ->addOption($channelOption)
            ->addOption($etatOption);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $pdo = getPDO();
        $eventType = null;
        $channelId = null;
        $enabled = '1';

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'event_type') {
                $eventType = $option->value;
            } elseif ($option->name === 'channel') {
                $channelId = $option->value;
            } elseif ($option->name === 'etat') {
                $enabled = $option->value;
            }
        }

        if (!$eventType || !$channelId) {
            $embed = (new Embed($discord))
                ->setTitle("Erreur ❌")
                ->setDescription("Arguments manquants.")
                ->setColor(0xFF0000);

            $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed])->setFlags(64));
            return;
        }

        try {
            $eventTypes = $eventType === 'all'
                ? ['join', 'leave', 'boost']
                : [$eventType];

            $stmt = $pdo->prepare("
                INSERT INTO event_config (server_id, event_type, channel_id, enabled)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    channel_id = VALUES(channel_id),
                    enabled = VALUES(enabled)
            ");

            foreach ($eventTypes as $type) {
                $stmt->execute([$interaction->guild_id, $type, $channelId, $enabled]);
            }

            $typesText = $eventType === 'all' ? '`join`, `leave`, `boost`' : "`{$eventType}`";
            $etatText = $enabled === '1' ? 'activés' : 'désactivés';

            $embed = (new Embed($discord))
                ->setTitle("✅ Annonces mises à jour")
                ->setDescription("Les événements {$typesText} seront envoyés dans <#{$channelId}> et sont **{$etatText}**.")
                ->setColor(0x00FF00);

            $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed]));

        } catch (\PDOException $e) {
            $embed = (new Embed($discord))
                ->setTitle("Erreur BDD ❌")
                ->setDescription("Erreur : " . $e->getMessage())
                ->setColor(0xFF0000);

            $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed])->setFlags(64));
        }
    }
}
