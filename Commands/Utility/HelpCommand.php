<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Choice;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

class HelpCommand
{
    private static array $loadedCommands = [];

    private static array $categoryStyles = [
        'Moderation'      => ['📛 Modération', 0xFF5555],
        'XP_Moderation'   => ['🛡️ XP Modération', 0xFF8800],
        'XP'              => ['📈 Système XP', 0x55FFAA],
        'Game'            => ['🎮 Mini-jeux', 0xAA55FF],
        'Events'          => ['📅 Événements', 0xFFAA00],
        'Logs'            => ['📝 Logs & Historique', 0xAAAAAA],
        'Utility'         => ['🧰 Utilitaires', 0x55AAFF],
        'Owner'           => ['👑 Commandes Admin', 0xFFD700],
        'Astronomy'         => ['🌠 Astronomie', 0x005288],
        'Default'         => ['📂 Autres', 0xCCCCCC],
    ];

    public static function register(Discord $discord): CommandBuilder
    {
        $categoryOption = new Option($discord);
        $categoryOption->setName('category')
            ->setDescription("Choisis une catégorie ou 'all' pour tout afficher")
            ->setType(3)
            ->setRequired(true);

        $categoryOption->addChoice(
            (new Choice($discord))->setName("📚 Tout afficher")->setValue('all')
        );

        foreach (self::$categoryStyles as $cat => [$label]) {
            $categoryOption->addChoice(
                (new Choice($discord))->setName($label)->setValue($cat)
            );
        }

        return CommandBuilder::new()
            ->setName('help')
            ->setDescription('Affiche les commandes disponibles par catégorie')
            ->addOption($categoryOption);
    }

    public static function setLoadedCommands(array $commands): void
    {
        self::$loadedCommands = $commands;
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $selectedCategory = null;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'category') {
                $selectedCategory = $option->value;
            }
        }

        $grouped = [];

        foreach (self::$loadedCommands as $name => $class) {
            if (!method_exists($class, 'register')) continue;

            $builder = $class::register($discord);
            $data = $builder->toArray();

            $parts = explode('\\', $class);
            $category = $parts[1] ?? 'Default';

            $label = "`/" . $data['name'] . "` : " . ($data['description'] ?? 'Pas de description');

            if ($selectedCategory === 'all') {
                $grouped[$category][] = $label;
            } elseif ($category === $selectedCategory) {
                $grouped[$category][] = $label;
            }
        }

        $embeds = [];

        if ($selectedCategory === 'all') {
            foreach ($grouped as $cat => $commands) {
                $embed = new Embed($discord);
                $embed->setTitle(self::$categoryStyles[$cat][0] ?? ucfirst($cat))
                    ->setDescription(implode("\n", $commands))
                    ->setColor(self::$categoryStyles[$cat][1] ?? 0x5865F2)
                    ->setTimestamp();

                $embeds[] = $embed;
            }

            // Envoi en une seule réponse avec plusieurs embeds (max 10 embeds par message)
            $interaction->respondWithMessage(
                MessageBuilder::new()->setEmbeds($embeds)->setFlags(64)
            );
            return;
        }

        // Cas d'une seule catégorie
        $embed = new Embed($discord);
        $embed->setTitle("📂 Commandes - " . (self::$categoryStyles[$selectedCategory][0] ?? ucfirst($selectedCategory)))
            ->setColor(self::$categoryStyles[$selectedCategory][1] ?? 0x5865F2)
            ->setTimestamp();

        if (empty($grouped)) {
            $embed->setDescription("Aucune commande trouvée pour cette catégorie.");
        } else {
            foreach ($grouped as $cat => $commands) {
                $label = self::$categoryStyles[$cat][0] ?? ucfirst($cat);
                $embed->addField([
                    'name' => $label,
                    'value' => implode("\n", $commands),
                    'inline' => false
                ]);
            }
        }

        $interaction->respondWithMessage(
            MessageBuilder::new()->addEmbed($embed)->setFlags(64)
        );
    }
}
