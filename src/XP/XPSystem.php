<?php

namespace XP;

use PDO;
use Discord\Parts\Channel\Message;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;

class XPSystem
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
    }

    public function handleMessage(Message $message, Discord $discord)
    {
        if ($message->author->bot || !$message->guild_id) return;

        $userId = $message->author->id;
        $username = $message->author->username;
        $guildId = $message->guild_id;

        $stmt = $this->pdo->prepare("SELECT xp, level, last_message_at FROM users_activity WHERE user_id = ? AND guild_id = ?");
        $stmt->execute([$userId, $guildId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = new \DateTime();
        $gainXP = rand(5, 15);

        if ($user) {
            $lastMessage = new \DateTime($user['last_message_at']);
            $diff = $now->getTimestamp() - $lastMessage->getTimestamp();

            if ($diff < 60) return; // cooldown 60s

            $newXP = $user['xp'] + $gainXP;
            $newLevel = $this->calculerNiveau($newXP);

            $this->pdo->prepare("UPDATE users_activity SET xp = ?, level = ?, username = ?, last_message_at = NOW() WHERE user_id = ? AND guild_id = ?")
                ->execute([$newXP, $newLevel, $username, $userId, $guildId]);

            if ($newLevel > $user['level']) {
                $embed = new Embed($discord);
                $embed->setTitle("🎉 Niveau supérieur !")
                    ->setDescription("<@$userId> est maintenant niveau **$newLevel** sur ce serveur !")
                    ->setColor(0x00ff00)
                    ->setTimestamp();

                $message->channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
            }
        } else {
            $this->pdo->prepare("INSERT INTO users_activity (user_id, guild_id, username, xp, level, last_message_at) VALUES (?, ?, ?, ?, ?, NOW())")
                ->execute([$userId, $guildId, $username, $gainXP, $this->calculerNiveau($gainXP)]);
        }
    }

    private function calculerNiveau(int $xp): int
    {
        return floor(sqrt($xp / 100));
    }
}
