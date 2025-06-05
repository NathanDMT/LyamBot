<?php

namespace Utils;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class MeteorParser
{
    public static function getShowers(): array
    {
        $client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; LyamBot/1.0; +https://discordapp.com)',
            ],
            'verify' => __DIR__ . '/../certs/cacert.pem',
        ]);

        try {
            $res = $client->get('https://amsmeteors.org/meteor-showers/meteor-shower-calendar/');
            $html = $res->getBody()->getContents();
        } catch (\Throwable $e) {
            file_put_contents(__DIR__ . '/ams_error.log', $e->getMessage());
            return [];
        }

        $crawler = new Crawler($html);
        $results = [];

        $crawler->filter('.shower.media')->each(function (Crawler $row) use (&$results) {
            try {
                $title = $row->filter('h3')->text();
                $peak = $row->filter('.shower_peak')->text();
                $period = $row->filter('.shower_acti')->text();

                // Détails (ZHR)
                $details = $row->filter('p')->each(fn($p) => $p->text());
                $zhr = 'Non communiqué';

                foreach ($details as $text) {
                    if (preg_match('/ZHR.*?(\d+)/i', $text, $matches)) {
                        $zhr = $matches[1];
                        break;
                    }
                }

                $results[] = [
                    'name' => trim($title),
                    'peak' => self::translatePeakDate($peak),
                    'period' => self::translatePeriod($period),
                    'zhr' => $zhr,
                    'visibility' => 'Non précisée',
                ];
            } catch (\Exception $e) {
                // ignorer le bloc si erreur
            }
        });

        return $results;
    }

    private static function translatePeakDate(string $text): string
    {
        // Exemple : "Next Peak night Jul 29-30, 2025"
        if (preg_match('/Peak.*?([A-Za-z]+)\s(\d{1,2})(?:-(\d{1,2}))?,\s?(\d{4})/i', $text, $m)) {
            $mois = [
                'Jan' => 'janvier', 'Feb' => 'février', 'Mar' => 'mars',
                'Apr' => 'avril', 'May' => 'mai', 'Jun' => 'juin',
                'Jul' => 'juillet', 'Aug' => 'août', 'Sep' => 'septembre',
                'Oct' => 'octobre', 'Nov' => 'novembre', 'Dec' => 'décembre'
            ];

            $moisFr = $mois[$m[1]] ?? $m[1];
            $jour = $m[2];
            $jour2 = $m[3] ?? null;
            $annee = $m[4];

            if ($jour2) {
                return "$jour-$jour2 $moisFr $annee";
            }

            return "$jour $moisFr $annee";
        }

        return 'Non précisé';
    }

    private static function translatePeriod(string $text): string
    {
        // Exemple : "Next period of activity: July 18th, 2025 to August 12th, 2025"
        if (preg_match('/([A-Za-z]+)\s(\d{1,2})(?:st|nd|rd|th)?,\s(\d{4}).*?([A-Za-z]+)\s(\d{1,2})(?:st|nd|rd|th)?,\s(\d{4})/', $text, $m)) {
            $mois = [
                'January' => 'janvier', 'February' => 'février', 'March' => 'mars',
                'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
                'July' => 'juillet', 'August' => 'août', 'September' => 'septembre',
                'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'
            ];

            $start = "{$m[2]} {$mois[$m[1]]} {$m[3]}";
            $end = "{$m[5]} {$mois[$m[4]]} {$m[6]}";

            return "Du $start au $end";
        }

        return 'Non spécifiée';
    }

    private static function parsePeakDate(string $text): string
    {
        // Exemple de texte : "Jul 29-30, 2025" ou "Aug 12-13, 2025"
        if (preg_match('/([A-Za-z]+)\s+(\d{1,2})-(\d{1,2}),\s*(\d{4})/', $text, $matches)) {
            $month = $matches[1];
            $day = $matches[2]; // premier jour du pic
            $year = $matches[4];

            $dateStr = "$month $day $year";
            $date = \DateTime::createFromFormat('M j Y', $dateStr);

            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return '9999-12-31'; // valeur fallback
    }
}
