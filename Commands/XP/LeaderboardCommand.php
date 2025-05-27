<?php

namespace Commands\XP;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use PDO;

class LeaderboardCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('leaderboard')
            ->setDescription('Affiche le classement des utilisateurs les plus actifs!');
    }

    public static function handle(Interaction $interaction, Discord $discord)
    {
        $pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');

        $stmt = $pdo->query("SELECT username, level, xp FROM users_activity ORDER BY level DESC, xp DESC LIMIT 10");
        $top = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $userId = $interaction->user->id;

        if (!$top) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Aucun utilisateur n’a encore d’XP."),
                true
            );
            return;
        }

        $classement = "";
        foreach ($top as $index => $user) {
            $classement .= ($index + 1) . " - " .
                "<@{$userId}>" .
                " • Niveau " . $user['level'] . " • " .
                $user['xp'] . " <a:XP_gif:1376904888121823312>\n";
        }

        $embed = new Embed($discord);
        $embed->setTitle("🏆 Classement XP")
            ->addFieldValues("Top 10", $classement)
            ->setColor(0xf1c40f)
            ->setTimestamp()
            ->setFooter("Système XP Discord");

        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed), true);
    }
}
