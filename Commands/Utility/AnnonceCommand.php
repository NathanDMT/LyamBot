<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

class AnnonceCommand
{
    public static function register(Discord $discord)
    {
        // Crée une option manuellement
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

    public static function handle(Interaction $interaction)
    {
        try {
            $contenu = null;

            foreach ($interaction->data->options as $option) {
                if ($option['name'] === 'message') {
                    $contenu = $option['value'];
                    break;
                }
            }

            if ($contenu === null) {
                throw new \Exception("Le message est vide ou manquant.");
            }

            $message = MessageBuilder::new()
                ->setContent("📢 **Annonce :**\n" . $contenu);

            $interaction->respondWithMessage($message);
        } catch (\Throwable $e) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Erreur : " . $e->getMessage()),
                true
            );
        }
    }
}
