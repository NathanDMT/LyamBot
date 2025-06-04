<?php

namespace Events;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class AnnonceGuildMemberAdd
{
    public static function register(Discord $discord): void
    {
        $pdo = getPDO();

        $discord->on(Event::GUILD_MEMBER_ADD, function (Member $member, Discord $discord) use ($pdo) {
            $guildId = $member->guild_id;

            try {
                $stmt = $pdo->prepare("SELECT channel_id FROM event_config WHERE server_id = ? AND event_type = 'join' AND enabled = 1");
                $stmt->execute([$guildId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $channelId = $row['channel_id'] ?? null;
            } catch (\PDOException $e) {
                echo "❌ Erreur BDD welcome : " . $e->getMessage() . "\n";
                return;
            }

            if (!$channelId) return;

            $guild = $discord->guilds->get('id', $guildId);
            $channel = $guild?->channels->get('id', $channelId);
            if (!$channel) return;

            $channel->sendMessage(
                MessageBuilder::new()->setContent("🎉 Bienvenue <@{$member->user->id}> sur **{$guild->name}** !")
            );
        });
    }
}
