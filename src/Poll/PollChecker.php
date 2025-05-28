<?php

namespace Poll;

use PDO;
use Discord\Discord;
use React\EventLoop\Loop;

class PollChecker
{
    private PDO $pdo;
    private Discord $discord;

    public function __construct(PDO $pdo, Discord $discord)
    {
        $this->pdo = $pdo;
        $this->discord = $discord;
    }

    public function start()
    {
        // Vérifie toutes les 60 secondes
        Loop::addPeriodicTimer(60, function () {
            $this->checkExpiredPolls();
        });
    }

    private function checkExpiredPolls()
    {
        $stmt = $this->pdo->query("SELECT * FROM polls WHERE fin_at <= NOW() AND is_closed = 0");
        $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($polls as $poll) {
            $channelId = $poll['channel_id'];
            $messageId = $poll['message_id'];

            $channel = $this->discord->getChannel($channelId);
            if (!$channel) continue;

            $channel->messages->fetch($messageId)->then(function ($message) use ($poll) {
                $yesVotes = 0;
                $noVotes = 0;

                foreach ($message->reactions as $reaction) {
                    if ($reaction->emoji->name === '✅') {
                        $yesVotes = $reaction->count - 1; // -1 pour le bot lui-même
                    } elseif ($reaction->emoji->name === '❌') {
                        $noVotes = $reaction->count - 1;
                    }
                }

                $resultMessage = "🛑 Le sondage est terminé !\n"
                    . "✅ Oui : **{$yesVotes}**\n"
                    . "❌ Non : **{$noVotes}**";

                $message->reply($resultMessage);
                $message->react('🔒');

                // Marque comme clôturé
                $this->pdo->prepare("UPDATE polls SET is_closed = 1 WHERE id = ?")->execute([$poll['id']]);
            });
        }
    }
}
