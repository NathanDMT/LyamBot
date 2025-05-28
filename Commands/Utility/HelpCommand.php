<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;

class HelpCommand
{
    private static array $loadedCommands = [];

    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('help')
            ->setDescription('Liste toutes les commandes disponibles');
    }

    public static function setLoadedCommands(array $commands): void
    {
        self::$loadedCommands = $commands;
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $message = "📖 **Liste des commandes disponibles :**\n\n";

        foreach (self::$loadedCommands as $name => $class) {
            if (method_exists($class, 'register')) {
                $builder = $class::register($discord); // ✅ PAS interaction->discord !
                $data = $builder->toArray();
                $message .= "- `/" . $data['name'] . "` : " . ($data['description'] ?? 'Pas de description') . "\n";
            }
        }

        $interaction->respondWithMessage(
            \Discord\Builders\MessageBuilder::new()
                ->setContent($message)
                ->setFlags(64)
        );
    }
}
