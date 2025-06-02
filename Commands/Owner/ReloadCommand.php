<?php

namespace Commands\Owner;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

class ReloadCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('reload')
            ->setDescription('Recharge une commande spécifique sans redémarrer le bot')
            ->addOption(
                (new Option($discord))
                    ->setName('commande')
                    ->setDescription("Nom de la commande à recharger (ex: warnlist)")
                    ->setType(3)
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $ownerId = $_ENV['OWNER_ID'] ?? null;
        if ($interaction->user->id !== $ownerId) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Tu n'as pas la permission de faire ça.")->setFlags(64)
            );
            return;
        }

        $commandName = null;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'commande') {
                $commandName = strtolower($option->value);
            }
        }

        if (!$commandName) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Nom de commande invalide.")->setFlags(64)
            );
            return;
        }

        $commandPath = self::findCommandPath(__DIR__ . '/..', $commandName);
        if (!$commandPath) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Fichier de commande introuvable.")->setFlags(64)
            );
            return;
        }

        // Déduit le namespace
        $relativePath = str_replace([realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '.php'], '', realpath($commandPath));
        $commandClass = 'Commands\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        require_once $commandPath;

        if (class_exists($commandClass) && method_exists($commandClass, 'register')) {
            $builder = $commandClass::register($discord);

            $discord->application->commands->save(
                new \Discord\Parts\Interactions\Command\Command($discord, $builder->toArray())
            )->then(function () use ($interaction, $commandName) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()->setContent("✅ Commande `/{$commandName}` rechargée avec succès.")->setFlags(64)
                );
            }, function ($e) use ($interaction) {
                $interaction->respondWithMessage(
                    MessageBuilder::new()->setContent("❌ Erreur API : " . $e->getMessage())->setFlags(64)
                );
            });
        } else {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Classe ou méthode `register()` introuvable.")->setFlags(64)
            );
        }
    }

    private static function findCommandPath(string $baseDir, string $command): ?string
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir));
        foreach ($iterator as $file) {
            if (
                $file->isFile() &&
                strtolower($file->getBasename('.php')) === strtolower($command) . 'command'
            ) {
                return $file->getPathname();
            }
        }
        return null;
    }
}
