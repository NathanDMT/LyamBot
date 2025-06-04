<?php

namespace Commands\Owner;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

class ReloadCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('reload')
            ->setDescription('Recharge une commande slash dans Discord (code non mis à jour sans redémarrage)')
            ->addOption(
                (new Option($discord))
                    ->setName('commande')
                    ->setDescription("Nom de la commande à recharger (ex: warnlist)")
                    ->setType(3) // STRING
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $ownerId = $_ENV['OWNER_ID'] ?? null;

        if ($interaction->user->id !== $ownerId) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Tu n'as pas la permission d’utiliser cette commande.")
                    ->setFlags(64)
            );
            return;
        }

        $commandName = null;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'commande') {
                $commandName = strtolower($option->value);
                break;
            }
        }

        if (!$commandName) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Nom de commande invalide.")->setFlags(64)
            );
            return;
        }

        // Chemin vers la classe de commande
        $filePath = __DIR__ . '/../' . ucfirst($commandName) . 'Command.php';
        $className = 'Commands\\' . ucfirst($commandName) . 'Command';

        if (!file_exists($filePath)) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Fichier de commande introuvable.")
                    ->setFlags(64)
            );
            return;
        }

        require_once $filePath;

        if (!class_exists($className) || !method_exists($className, 'register')) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Classe ou méthode `register()` introuvable.")
                    ->setFlags(64)
            );
            return;
        }

        try {
            $builder = $className::register($discord);
            $discord->application->commands->save(
                new \Discord\Parts\Interactions\Command\Command($discord, $builder->toArray())
            )->then(function () use ($interaction, $commandName) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()
                        ->setContent("✅ Commande `/{$commandName}` rechargée côté API.\n🔁 Redémarre le bot pour appliquer les changements de code PHP.")
                        ->setFlags(64)
                );
            }, function ($e) use ($interaction) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()
                        ->setContent("❌ Erreur API : " . $e->getMessage())
                        ->setFlags(64)
                );
            });
        } catch (\Throwable $e) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Erreur : " . $e->getMessage())
                    ->setFlags(64)
            );
        }
    }
}
