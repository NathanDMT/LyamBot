<?php

namespace Commands\XP;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use PDO;

class RankCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('rank')
            ->setDescription('Affiche ton niveau et ton XP actuels !');
    }

    public static function handle(Interaction $interaction)
    {
        // Connexion à ta base de données
        $pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');

        $userId = $interaction->user->id;
        $username = $interaction->user->username;

        // Récupère les données de l'utilisateur
        $stmt = $pdo->prepare("SELECT level, xp FROM users_activity WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $message = "<@{$userId}> tu es **niveau {$data['level']}** avec **{$data['xp']}<a:XP_gif:1376904888121823312>**.";
        } else {
            $message = "<@{$userId}> tu n’as pas encore de niveau. Commence à interagir pour gagner de l’XP <a:XP_gif:1376904888121823312>";
        }

        $builder = MessageBuilder::new()->setContent($message);
        $interaction->respondWithMessage($builder);
    }
}
