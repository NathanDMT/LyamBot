<?php

namespace Commands\Astronomy;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use GuzzleHttp\Client;

class MoonPhaseCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('moonphase')
            ->setDescription("Affiche la phase actuelle de la Lune pour une ville")
            ->addOption(
                (new Option($discord))
                    ->setName('ville')
                    ->setDescription("Nom de la ville (ex: Paris)")
                    ->setType(3)
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $ville = $interaction->data->options['ville']->value;
        $client = new Client([
            'verify' => __DIR__ . '/../../src/certs/cacert.pem',
        ]);
        $apiKey = $_ENV['WEATHER_API_KEY'];

        try {
            $response = $client->get("https://api.weatherapi.com/v1/astronomy.json", [
                'query' => [
                    'key' => $apiKey,
                    'q' => $ville,
                    'dt' => date('Y-m-d')
                ],
                'verify' => false
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['error'])) {
                $message = $data['error']['message'] ?? 'Erreur inconnue.';
                $interaction->respondWithMessage(
                    MessageBuilder::new()
                        ->setContent("❌ Erreur API : $message")
                        ->setFlags(64)
                );
                return;
            }

            $astro = $data['astronomy']['astro'];
            $location = $data['location']['name'];

            $originalPhase = $astro['moon_phase'] ?? 'Inconnue';
            $phase = self::translatePhase($originalPhase);

            $illumination = $astro['moon_illumination'] ?? '?';
            $moonrise = $astro['moonrise'] ?: "Non visible";
            $moonset = $astro['moonset'] ?: "Non visible";

            $emoji = self::getMoonEmoji($phase);

            $embed = new Embed($discord);
            $embed->setTitle("Phase lunaire à {$location}")
                ->setDescription("{$emoji} **{$phase}**\n💡 **Illumination** : {$illumination}%\n🌄 **Lever de Lune** : {$moonrise}\n🌇 **Coucher de Lune** : {$moonset}")
                ->setColor(0xccccff)
                ->setTimestamp();

            // ✅ Envoie le message correctement
            $interaction->respondWithMessage(
                MessageBuilder::new()->addEmbed($embed)
            );
        } catch (\Throwable $e) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Exception levée : " . $e->getMessage())
                    ->setFlags(64)
            );
        }
    }

    private static function translatePhase(string $phase): string
    {
        return match (strtolower($phase)) {
            'new moon' => 'Nouvelle lune',
            'waxing crescent' => 'Premier croissant',
            'first quarter' => 'Premier quartier',
            'waxing gibbous' => 'Lune gibbeuse croissante',
            'full moon' => 'Pleine lune',
            'waning gibbous' => 'Lune gibbeuse décroissante',
            'last quarter' => 'Dernier quartier',
            'waning crescent' => 'Dernier croissant',
            default => $phase,
        };
    }

    private static function getMoonEmoji(string $phase): string
    {
        return match (strtolower($phase)) {
            'new moon' => '🌑',
            'waxing crescent' => '🌒',
            'first quarter' => '🌓',
            'waxing gibbous' => '🌔',
            'full moon' => '🌕',
            'waning gibbous' => '🌖',
            'last quarter' => '🌗',
            'waning crescent' => '🌘',
            default => '🌙'
        };
    }
}
