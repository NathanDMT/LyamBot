<?php

namespace Commands\Astronomy;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use GuzzleHttp\Client;

class ApodCommand
{
    private static array $imageCache = [];

    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('apod')
            ->setDescription("Image astronomique du jour ou d'une date précise (NASA APOD)")
            ->addOption(
                (new Option($discord))
                    ->setName('date')
                    ->setDescription("Date au format AAAA-MM-JJ (optionnel, par défaut : aujourd’hui)")
                    ->setType(3)
                    ->setRequired(false)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $client = new Client();
        $apiKey = $_ENV['NASA_API_KEY'] ?? 'DEMO_KEY';

        $date = date('Y-m-d');
        if (isset($interaction->data->options['date'])) {
            $inputDate = $interaction->data->options['date']->value;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputDate)) {
                $date = $inputDate;
            }
        }

        try {
            $client = new Client([
                'verify' => __DIR__ . '/../../src/certs/cacert.pem',
            ]);

            $response = $client->get("https://api.nasa.gov/planetary/apod", [
                'query' => [
                    'api_key' => $apiKey,
                    'date' => $date
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            $embed = new Embed($discord);
            $embed->setTitle("📷 {$data['title']}")
                ->setDescription($data['explanation'])
                ->setUrl($data['url'])
                ->setColor(0x005288)
                ->setTimestamp()
                ->setFooter("Astronomy Picture of the Day • {$data['date']}");

            $imageUrl = $data['hdurl'] ?? $data['url'] ?? null;

            if ($data['media_type'] === 'image') {
                if ($imageUrl && preg_match('/\.(jpg|jpeg|png|gif)$/i', $imageUrl)) {
                    $embed->setImage($imageUrl);
                }
            }

            if ($data['media_type'] === 'video') {
                $embed->addFieldValues("🎬 Vidéo", $data['url']);

                if (preg_match('/youtube\.com.*?[?&]v=([^&]+)/', $data['url'], $match)
                    || preg_match('/youtu\.be\/([^?]+)/', $data['url'], $match)) {
                    $videoId = $match[1];
                    $thumbnail = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
                    $embed->setImage($thumbnail);
                }
            }

            $uniqueId = uniqid('apod_', true);
            self::$imageCache[$uniqueId] = $imageUrl;

            $buttonRow = ActionRow::new()->addComponent(
                Button::new(Button::STYLE_PRIMARY)
                    ->setLabel("📥 Afficher l’image")
                    ->setCustomId("apod_show_image_$uniqueId")
            );

            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->addEmbed($embed)
                    ->addComponent($buttonRow)
            );
        } catch (\Throwable $e) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Erreur APOD : " . $e->getMessage())
                    ->setFlags(64)
            );
        }
    }

    public static function handleButton(Interaction $interaction, Discord $discord): void
    {
        $customId = $interaction->data->custom_id;

        if (!str_starts_with($customId, 'apod_show_image_')) {
            return;
        }

        $id = str_replace('apod_show_image_', '', $customId);
        $imageUrl = self::$imageCache[$id] ?? null;

        if (!$imageUrl) {
            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->setContent("❌ Image expirée ou non disponible.")
                    ->setFlags(64)
            );
            return;
        }

        $interaction->respondWithMessage(
            MessageBuilder::new()
                ->setContent("🖼️ Voici l’image du jour : $imageUrl")
        );
    }
}
