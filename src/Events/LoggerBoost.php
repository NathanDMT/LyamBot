<?php

namespace Events;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Builders\MessageBuilder;
use PDO;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class LoggerBoost
{
    public static function register(Discord $discord): void
    {
        $pdo = getPDO();

        $discord->on(Event::GUILD_MEMBER_UPDATE, function (Member $member) use ($discord, $pdo) {
            if ($member->premium_since === null) return;

            try {
                $stmt = $pdo->prepare("SELECT channel_id FROM modlog_config WHERE server_id = ? AND event_type = 'boost'");
                $stmt->execute([$member->guild_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $channelId = $result['channel_id'] ?? null;
            } catch (\PDOException $e) {
                echo "❌ Erreur BDD BoostLogger : " . $e->getMessage() . "\n";
                return;
            }

            if (!$channelId) return;

            $guild = $discord->guilds->get('id', $member->guild_id);
            if (!$guild) return;

            $channel = $guild->channels->get('id', $channelId);
            if (!$channel) return;

            $user = $member->user;

            $embed = new Embed($discord);
            $embed->setAuthor("{$user->username}", $user->avatar, $user->avatar)
                ->setTitle('🚀 Serveur boosté !')
                ->setDescription("**{$user->username}** vient de booster le serveur. Merci à lui/elle ! 💜")
                ->setThumbnail($user->avatar)
                ->setTimestamp()
                ->setColor(0x9b59b6);

            $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
        });
    }
}
