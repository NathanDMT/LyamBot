<?php

namespace Commands\Astronomy;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Channel\File;
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
        $interaction->acknowledge()->then(function () use ($interaction, $discord) {
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
                $imageUrl = $data['url'] ?? $data['hdurl'] ?? null;

                $embed = new Embed($discord);
                $embed->setTitle("{$data['title']}")
                    ->setURL($data['url'])
                    ->setDescription(substr($data['explanation'], 0, 4000)) // Discord limit
                    ->setColor(0x005288)
                    ->setFooter("Astronomy Picture of the Day • {$data['date']}");

                if ($data['media_type'] === 'image' && $imageUrl && preg_match('/\.(jpg|jpeg|png|gif)$/i', $imageUrl)) {
                    $tempPath = sys_get_temp_dir() . '/apod_' . uniqid() . '.jpg';
                    file_put_contents($tempPath, file_get_contents($imageUrl));

                    $embed->setImage("attachment://" . basename($tempPath));

                    $interaction->sendFollowUpMessage(
                        MessageBuilder::new()
                            ->addEmbed($embed)
                            ->addFile($tempPath)
                    );

                    // Nettoyage du fichier après un court délai
                    \React\EventLoop\Loop::addTimer(5, function () use ($tempPath) {
                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }
                    });
                } elseif ($data['media_type'] === 'video') {
                    $embed->addFieldValues("🎬 Vidéo", $data['url']);

                    if (preg_match('/youtube\.com.*?[?&]v=([^&]+)/', $data['url'], $match)
                        || preg_match('/youtu\.be\/([^?]+)/', $data['url'], $match)) {
                        $videoId = $match[1];
                        $thumbnail = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
                        $embed->setImage($thumbnail);
                    }

                    $interaction->sendFollowUpMessage(
                        MessageBuilder::new()
                            ->addEmbed($embed)
                    );
                } else {
                    $interaction->sendFollowUpMessage(
                        MessageBuilder::new()
                            ->setContent("❌ Média non supporté : {$data['media_type']}")
                    );
                }

            } catch (\Throwable $e) {
                $interaction->sendFollowUpMessage(
                    MessageBuilder::new()
                        ->setContent("❌ Erreur APOD : " . $e->getMessage())
                        ->setFlags(64)
                );
            }
        });
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
