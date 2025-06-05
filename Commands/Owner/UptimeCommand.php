<?php

namespace Commands\Owner;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;

class UptimeCommand
{
    private static \DateTime $startTime;

    public static function setStartTime(): void
    {
        self::$startTime = new \DateTime();
    }

    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('uptime')
            ->setDescription("Affiche depuis combien de temps le bot est en ligne");
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $ownerId = $_ENV['OWNER_ID'] ?? null;

        if ($interaction->user->id !== $ownerId) {
            $embed = new Embed($discord);
            $embed->setTitle("🚫 Accès refusé")
                ->setDescription("Tu n'es pas autorisé à utiliser cette commande.")
                ->setColor(0xFF5555)
                ->setTimestamp();

            $interaction->respondWithMessage(
                MessageBuilder::new()
                    ->addEmbed($embed)
                    ->setFlags(64)
            );
            return;
        }

        $now = new \DateTime();
        $interval = self::$startTime->diff($now);

        $uptime = sprintf(
            "%d jours, %d heures, %d minutes, %d secondes",
            $interval->d,
            $interval->h,
            $interval->i,
            $interval->s
        );

        $embed = new Embed($discord);
        $embed->setTitle("🟢 Uptime du bot")
            ->setDescription("En ligne depuis :\n`$uptime`")
            ->setColor(0x00FF88)
            ->setTimestamp();

        $interaction->respondWithMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->setFlags(64)
        );
    }
}
