<?php

namespace Events;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class AnnonceGuildMemberRemove
{
    public static function register(Discord $discord): void
    {
        $pdo = getPDO();

        $discord->on(Event::GUILD_MEMBER_REMOVE, function (Member $member, Discord $discord) use ($pdo) {
            $guildId = $member->guild_id;

            // 🔌 Connexion à la BDD pour récupérer le salon d'événements
            try {
                $stmt = $pdo->prepare("SELECT channel_id FROM event_config WHERE server_id = ? AND event_type = 'leave'");
                $stmt->execute([$guildId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $channelId = $row['channel_id'] ?? null;
            } catch (\PDOException $e) {
                echo "❌ Erreur BDD leave : " . $e->getMessage() . "\n";
                return;
            }

            if (!$channelId) return;

            $guild = $discord->guilds->get('id', $guildId);
            $channel = $guild->channels->get('id', $channelId);
            if (!$channel) return;

            $channel->sendMessage(
                MessageBuilder::new()->setContent("👋 <@{$member->user->id}> a quitté **{$guild->name}**.")
            );
        });
    }
}
