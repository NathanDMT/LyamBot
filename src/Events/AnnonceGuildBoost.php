<?php

namespace Events;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;

class AnnonceGuildBoost
{
    public static function register(Discord $discord): void
    {
        $discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $newMember, Discord $discord, Member $oldMember) {
            // Vérifie si l'utilisateur vient de booster le serveur
            $wasBoosting = $oldMember->premium_since !== null;
            $isBoosting = $newMember->premium_since !== null;

            if (!$wasBoosting && $isBoosting) {
                $guildId = $newMember->guild_id;

                // 🔌 Connexion à la BDD pour récupérer le salon d'event
                try {
                    $pdo = new \PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
                    $stmt = $pdo->prepare("SELECT channel_id FROM event_config WHERE server_id = ? AND event_type = 'boost'");
                    $stmt->execute([$guildId]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    $channelId = $row['channel_id'] ?? null;
                } catch (\PDOException $e) {
                    echo "❌ Erreur BDD boost : " . $e->getMessage() . "\n";
                    return;
                }

                if (!$channelId) return;

                $guild = $discord->guilds->get('id', $guildId);
                $channel = $guild->channels->get('id', $channelId);
                if (!$channel) return;

                $message = MessageBuilder::new()
                    ->setContent("🚀 <@{$newMember->user->id}> vient de **booster** le serveur ! Merci pour le soutien ! ❤️");

                $channel->sendMessage($message);
            }
        });
    }
}
