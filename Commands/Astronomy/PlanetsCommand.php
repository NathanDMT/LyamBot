<?php

namespace Commands\Astronomy;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Interaction;
use GuzzleHttp\Client;

class PlanetsCommand
{
    private static array $planetMap = [
        'mercure' => 'mercury',
        'vénus'   => 'venus',
        'venus'   => 'venus',
        'terre'   => 'earth',
        'mars'    => 'mars',
        'jupiter' => 'jupiter',
        'saturne' => 'saturn',
        'uranus'  => 'uranus',
        'neptune' => 'neptune',
    ];

    public static function register(Discord $discord): CommandBuilder
    {
        $option = new Option($discord);
        $option->setName('nom')
            ->setDescription("Choisis une planète")
            ->setType(3) // STRING
            ->setRequired(true)
            ->addChoice(new Choice($discord, ['name' => 'Mercure', 'value' => 'mercure']))
            ->addChoice(new Choice($discord, ['name' => 'Vénus', 'value' => 'venus']))
            ->addChoice(new Choice($discord, ['name' => 'Terre', 'value' => 'terre']))
            ->addChoice(new Choice($discord, ['name' => 'Mars', 'value' => 'mars']))
            ->addChoice(new Choice($discord, ['name' => 'Jupiter', 'value' => 'jupiter']))
            ->addChoice(new Choice($discord, ['name' => 'Saturne', 'value' => 'saturne']))
            ->addChoice(new Choice($discord, ['name' => 'Uranus', 'value' => 'uranus']))
            ->addChoice(new Choice($discord, ['name' => 'Neptune', 'value' => 'neptune']));

        return CommandBuilder::new()
            ->setName('planets')
            ->setDescription("Affiche les infos d'une planète")
            ->addOption($option);
    }


    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $nom = strtolower($interaction->data->options['nom']->value);
        $id = self::$planetMap[$nom] ?? $nom;

        $client = new Client([
            'verify' => '/../../src/cert/cacert.pem'
        ]);

        $interaction->acknowledge();

        try {
            $res = $client->get("https://api.le-systeme-solaire.net/rest/bodies/{$id}");
            $data = json_decode($res->getBody(), true);

            if (!$data || !$data['isPlanet']) {
                $interaction->sendFollowUpMessage(
                    MessageBuilder::new()->setContent("❌ Ce corps céleste n'est pas une planète.")
                );
                return;
            }

            $embed = new Embed($discord);
            $embed->setTitle("Infos sur " . ucfirst($data['englishName']))
                ->addFieldValues("⚖️ Masse", self::formatMass($data), true)
                ->addFieldValues("☀️ Distance au Soleil", ($data['semimajorAxis'] ?? 'Inconnue') . " km", true)
                ->addFieldValues("🕓 Durée d’un jour", ($data['sideralRotation'] ?? 'Inconnue') . " h", true)
                ->addFieldValues("🌙 Lunes", count($data['moons'] ?? []), true)
                ->setFooter("Source : api.le-systeme-solaire.net")
                ->setColor(0x1abc9c);

            $interaction->sendFollowUpMessage(
                MessageBuilder::new()->addEmbed($embed)
            );

        } catch (\Exception $e) {
            $interaction->sendFollowUpMessage(
                MessageBuilder::new()->setContent("❌ Erreur API : `" . $e->getMessage() . "`")
            );
        }
    }

    private static function formatMass(array $data): string
    {
        if (!isset($data['mass']['massValue']) || !isset($data['mass']['massExponent'])) {
            return "Inconnue";
        }
        return $data['mass']['massValue'] . " × 10^" . $data['mass']['massExponent'] . " kg";
    }
}
