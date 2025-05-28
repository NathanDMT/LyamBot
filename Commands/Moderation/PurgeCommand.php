<?php

namespace Commands\Moderation;

use Discord\Discord;
use function React\Promise\all;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Command\Option;

class PurgeCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName('purge')
            ->setDescription('Supprime un certain nombre de messages récents');

        $countOption = new Option($discord);
        $countOption
            ->setName('nombre')
            ->setDescription('Nombre de messages à supprimer (1 à 100)')
            ->setType(4)
            ->setRequired(true);

        return $command->addOption($countOption);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $nombre = 0;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'nombre') {
                $nombre = (int)$option->value;
            }
        }

        if ($nombre < 1 || $nombre > 100) {
            $embed = new Embed($discord);
            $embed->setTitle("Erreur ❌");
            $embed->setDescription("Le nombre doit être entre 1 et 100.");
            $embed->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $member = $interaction->member;
        if (!$member->getPermissions()->manage_messages) {
            $embed = new Embed($discord);
            $embed->setTitle("Accès refusé 🔒");
            $embed->setDescription("Tu n’as pas la permission de gérer les messages.");
            $embed->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $channel = $interaction->channel;

        $channel->getMessageHistory([
            'limit' => $nombre
        ])->then(function ($messages) use ($interaction, $discord, $nombre) {
            $promises = [];

            foreach ($messages as $message) {
                if (time() - $message->timestamp->getTimestamp() < 60 * 60 * 24 * 14) {
                    $promises[] = $message->delete();
                }
            }

            all($promises)->then(function ($results) use ($interaction, $discord) {
                $count = count($results);

                $embed = new Embed($discord);
                $embed->setTitle("🧹 Purge terminée");
                $embed->setDescription("**$count** message(s) supprimé(s).");
                $embed->setColor(0x00FF00);

                $interaction->respondWithMessage(
                    MessageBuilder::new()->addEmbed($embed)->setFlags(64)
                );
            });
        });
    }
}