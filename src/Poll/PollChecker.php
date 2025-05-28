<?php

namespace Poll;

use PDO;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
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
        Loop::addPeriodicTimer(30, function () {
            $this->checkExpiredPolls();
        });
    }

    private function checkExpiredPolls()
    {
        $stmt = $this->pdo->query("SELECT * FROM polls WHERE fin_at <= NOW() AND is_closed = 0");
        $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($polls as $poll) {
            $channel = $this->discord->getChannel($poll['channel_id']);
            if (!$channel) continue;

            $channel->messages->fetch($poll['message_id'])->then(function ($message) use ($poll, $channel) {
                if (!$message) {
                    echo "Message introuvable pour le sondage ID {$poll['id']}\n";
                    $this->pdo->prepare("UPDATE polls SET is_closed = 1 WHERE id = ?")->execute([$poll['id']]);
                    return;
                }

                $message->reactions->fetch('✅')->then(function ($yesReaction) use ($message, $poll, $channel) {
                    $yesVotes = max(0, ($yesReaction->count ?? 1) - 1);

                    $message->reactions->fetch('❌')->then(function ($noReaction) use ($yesVotes, $message, $poll, $channel) {
                        $noVotes = max(0, ($noReaction->count ?? 1) - 1);

                        $embed = new Embed($this->discord);
                        $embed->setTitle("Résultat du sondage :")
                            ->setDescription("{$poll['question']}")
                            ->addFieldValues("✅", "{$yesVotes} vote(s)", true)
                            ->addFieldValues("❌", "{$noVotes} vote(s)", true)
                            ->setColor(0xffcc00)
                            ->setTimestamp();

                        $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
                        $message->delete();
                        $this->pdo->prepare("UPDATE polls SET is_closed = 1 WHERE id = ?")->execute([$poll['id']]);
                    });
                });
            });
        }
    }
}
