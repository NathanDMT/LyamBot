<?php
namespace Commands\XP_Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\MessageBuilder;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class SetxpCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        // Option utilisateur (mention)
        $userOption = new Option($discord, [
            'name' => 'utilisateur',
            'description' => 'Utilisateur à modifier',
            'type' => 6, // USER
            'required' => true,
        ]);

        // Option valeur numérique
        $xpOption = new Option($discord, [
            'name' => 'valeur',
            'description' => "XP à attribuer",
            'type' => 4, // INTEGER
            'required' => true,
        ]);
        return CommandBuilder::new()
            ->setName('setxp')
            ->setDescription("Définit l'XP d’un utilisateur.")
            ->addOption($userOption)
            ->addOption($xpOption);
    }

    public static function handle(Interaction $interaction)
    {
        $pdo = getPDO();
        $target = $interaction->data->options['utilisateur']->value;
        $xp = $interaction->data->options['valeur']->value;
        $level = floor(sqrt($xp / 100));

        $pdo->prepare("UPDATE users_activity SET xp = ?, level = ? WHERE user_id = ?")
            ->execute([$xp, $level, $target]);

        $interaction->respondWithMessage(MessageBuilder::new()
            ->setContent("✅ XP de <@$target> défini à $xp (niveau $level)"), true);
    }
}
