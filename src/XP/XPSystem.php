<?php


namespace XP;

use PDO;
use Discord\Parts\Channel\Message;

class XPSystem
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
    }

    public function handleMessage(Message $message)
    {
        $userId = $message->author->id;
        $username = $message->author->username;

        if ($message->author->bot) return;

        $stmt = $this->pdo->prepare("SELECT xp, level, last_message_at FROM users_activity WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = new \DateTime();
        $gainXP = rand(5, 15);

        if ($user) {
            $lastMessage = new \DateTime($user['last_message_at']);
            $diff = $now->getTimestamp() - $lastMessage->getTimestamp();

            if ($diff < 60) return; // cooldown 60s

            $newXP = $user['xp'] + $gainXP;
            $newLevel = $this->calculerNiveau($newXP);

            $this->pdo->prepare("UPDATE users_activity SET xp = ?, level = ?, username = ?, last_message_at = NOW() WHERE user_id = ?")
                ->execute([$newXP, $newLevel, $username, $userId]);
        } else {
            $this->pdo->prepare("INSERT INTO users_activity (user_id, username, xp, level, last_message_at) VALUES (?, ?, ?, ?, NOW())")
                ->execute([$userId, $username, $gainXP, $this->calculerNiveau($gainXP)]);
        }
    }

    private function calculerNiveau(int $xp): int
    {
        return floor(sqrt($xp / 100));
    }
}
