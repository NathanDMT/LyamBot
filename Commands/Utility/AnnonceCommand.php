<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

class AnnonceCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $option = new Option($discord, [
            'name' => 'message',
            'description' => 'Contenu de l’annonce',
            'type' => Option::STRING,
            'required' => true,
        ]);

        return CommandBuilder::new()
            ->setName('annonce')
            ->setDescription('Créer une annonce')
            ->addOption($option);
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        try {
            $contenu = null;

            foreach ($interaction->data->options as $option) {
                if ($option->name === 'message') {
                    $contenu = $option->value;
                    break;
                }
            }

            if ($contenu === null) {
                throw new \Exception("Le message est vide ou manquant.");
            }

            $embed = new Embed($discord);
            $embed->setTitle("📢 Annonce")
                ->setDescription($contenu)
                ->setColor(0x3498db)
                ->setFooter("Annonce par {$interaction->user->username}")
                ->setTimestamp();

            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed),
                false
            );
        } catch (\Throwable $e) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Erreur : " . $e->getMessage()),
                true
            );
        }
    }
}
