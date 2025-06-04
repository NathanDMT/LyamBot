<?php

namespace Commands\Astronomy;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use GuzzleHttp\Client;

class IssLocationCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('iss-location')
            ->setDescription("Affiche la position actuelle de la Station Spatiale Internationale");
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $embed = self::generateEmbed($discord);
        $row = ActionRow::new()->addComponent(
            Button::new(Button::STYLE_PRIMARY)
                ->setLabel("🛰️ Rafraîchir")
                ->setCustomId("refresh_iss")
        );

        $interaction->respondWithMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($row)
        );
    }

    public static function editWithNewData(Interaction $interaction, Discord $discord): void
    {
        $embed = self::generateEmbed($discord);
        $row = ActionRow::new()->addComponent(
            Button::new(Button::STYLE_PRIMARY)
                ->setLabel("🛰️ Rafraîchir")
                ->setCustomId("refresh_iss")
        );

        $interaction->updateMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($row)
        );
    }

    private static function generateEmbed(Discord $discord): Embed
    {
        $client = new Client([
            'verify' => __DIR__ . '/../../src/certs/cacert.pem',
            'headers' => ['User-Agent' => 'LyamBot/1.0']
        ]);

        $latitude = $longitude = $locationName = 'N/A';

        try {
            $res = $client->get("http://api.open-notify.org/iss-now.json");
            $data = json_decode($res->getBody(), true);
            $latitude = $data['iss_position']['latitude'];
            $longitude = $data['iss_position']['longitude'];

            // Reverse géocoding
            $locationName = "au-dessus de l’océan ou d'une zone non habitée";
            try {
                $geo = $client->get("https://nominatim.openstreetmap.org/reverse", [
                    'query' => [
                        'format' => 'json',
                        'lat' => $latitude,
                        'lon' => $longitude,
                        'zoom' => 5,
                        'addressdetails' => 1,
                    ]
                ]);
                $geoData = json_decode($geo->getBody(), true);
                if (isset($geoData['address']['country'])) {
                    $locationName = "au-dessus de " . $geoData['address']['country'];
                }
            } catch (\Throwable) {}

        } catch (\Throwable) {}

        $imageUrl = "http://localhost/LyamBot/src/utils/IssLocalisation.php?lat=$latitude&lon=$longitude";  // URL de ton proxy

        $embed = new Embed($discord);
        $embed->setTitle("🛰️ Position actuelle de l'ISS")
            ->addFieldValues("🌍 Latitude", $latitude, true)
            ->addFieldValues("🌐 Longitude", $longitude, true)
            ->addFieldValues("📍 Localisation", $locationName, false)
            ->setImage($imageUrl)  // Utilise l'URL du proxy ici
            ->setColor(0x00bfff)
            ->setTimestamp();


        return $embed;
    }
}
