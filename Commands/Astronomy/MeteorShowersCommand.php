<?php

namespace Commands\Astronomy;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\InteractionCallback;
use Utils\MeteorParser;

class MeteorShowersCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('meteorshowers')
            ->setDescription("Affiche les prochaines pluies de météores automatiquement depuis le site AMS");
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $showers = MeteorParser::getShowers();

        if (empty($showers)) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Impossible de récupérer les pluies de météores depuis AMS.")
            );
            return;
        }

        $index = self::getNextShowerIndex($showers);

        if (!isset($showers[$index])) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Aucune pluie de météores future détectée.")
            );
            return;
        }

        $embed = self::buildShowerEmbed($discord, $showers[$index]);
        $buttons = self::buildNavigationButtons($index);

        $interaction->respondWithMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($buttons)
        );
    }


    public static function handleButton(Interaction $interaction, Discord $discord): void
    {
        $customId = $interaction->data->custom_id;
        $parts = explode(':', $customId);
        $action = $parts[0];
        $index = intval($parts[1]);

        $showers = MeteorParser::getShowers();
        $count = count($showers);

        if ($action === 'prev' && $index > 0) {
            $index--;
        } elseif ($action === 'next' && $index < $count - 1) {
            $index++;
        }

        $embed = self::buildShowerEmbed($discord, $showers[$index]);
        $buttons = self::buildNavigationButtons($index);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($buttons)
        );
    }

    private static function buildShowerEmbed(Discord $discord, array $shower): Embed
    {
        $zhr = $shower['zhr'] ?? 'Inconnu';
        $period = $shower['period'] ?? 'Non spécifiée';
        $peak = $shower['peak'] ?? 'Non précisé';
        $visibility = $shower['visibility'] ?? 'Non précisée';
        $name = $shower['name'] ?? 'Inconnue';

        return (new Embed($discord))
            ->setTitle("🌠 Pluie de météores : **{$name}**")
            ->setDescription("✨ Voici les informations disponibles pour cette pluie de météores.")
            ->addFieldValues("📅 **Période d'activité**", $period, true)
            ->addFieldValues("📍 **Pic d'activité**", $peak, true)
            ->addFieldValues("💥 **ZHR (taux horaire)**", is_numeric($zhr) ? $zhr . ' météores/h' : $zhr, true)
            ->addFieldValues("🔭 **Conditions d'observation**", $visibility, false)
            ->setFooter("Source : amsmeteors.org")
            ->setColor(0x9b59b6);
    }

    private static function buildNavigationButtons(int $index): ActionRow
    {
        $row = ActionRow::new();

        $row->addComponent(
            Button::new(Button::STYLE_SECONDARY)
                ->setLabel('⬅️ Précédent')
                ->setCustomId("prev:$index")
                ->setDisabled($index === 0)
        );

        $row->addComponent(
            Button::new(Button::STYLE_SECONDARY)
                ->setLabel('Suivant ➡️')
                ->setCustomId("next:$index")
                ->setDisabled(false)
        );

        return $row;
    }


    private static function getNextShowerIndex(array $showers): int
    {
        $today = new \DateTime();

        foreach ($showers as $i => $shower) {
            $peakDate = \DateTime::createFromFormat('Y-m-d', $shower['peak']);
            if ($peakDate && $peakDate >= $today) {
                return $i;
            }
        }

        return 0; // fallback
    }
}
