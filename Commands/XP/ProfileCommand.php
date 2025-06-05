<?php

namespace Commands\XP;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class ProfileCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $userOption = new Option($discord, [
            'name' => 'utilisateur',
            'description' => 'Permet de voir l\'XP d\'un utilisateur',
            'type' => 6,
            'required' => true,
        ]);

        return CommandBuilder::new()
            ->setName('profile')
            ->setDescription("Affiche le profil XP d’un utilisateur.")
            ->addOption($userOption);
    }

    public static function handle(Interaction $interaction, Discord $discord)
    {
        $pdo = getPDO();
        $options = $interaction->data->options;
        $targetUser = null;

        foreach ($options as $option) {
            if ($option->name === 'utilisateur') {
                $targetUser = $option->value;
                break;
            }
        }

        $stmt = $pdo->prepare("SELECT xp, level FROM users_activity WHERE user_id = ?");
        $stmt->execute([$targetUser]);
        $data = $stmt->fetch();

        if ($data) {
            $xp = $data['xp'];
            $level = $data['level'];
            $next = pow($level + 1, 2) * 100;
            $percent = round(($xp / $next) * 100);
            $bar = str_repeat('█', (int)($percent / 10)) . str_repeat('░', 10 - (int)($percent / 10));
            $userId = $interaction->user->id;

            $embed = new Embed($discord);
            $embed->setDescription("### Profil de <@{$userId}>")
                ->addFieldValues("Niveau", $level, true)
                ->addFieldValues("XP", "$xp / $next", true)
                ->addFieldValues("Progression", "$percent%  [$bar]", false)
                ->setColor(0x00ffcc)
                ->setTimestamp();

            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed),
                true
            );
        } else {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("<@$targetUser> n’a pas encore de profil."),
                true
            );
        }
    }
}