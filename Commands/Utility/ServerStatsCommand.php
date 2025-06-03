<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;

class ServerStatsCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('serverstats')
            ->setDescription('Affiche les statistiques du serveur');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $guild = $interaction->guild;

        $memberCount = $guild->member_count;
        $roleCount = count($guild->roles);
        $channels = $guild->channels->toArray();
        $textChannels = count(array_filter($channels, fn($c) => $c->type === 0));
        $voiceChannels = count(array_filter($channels, fn($c) => $c->type === 2));
        $bots = 0;

        foreach ($guild->members as $member) {
            if ($member->user->bot) {
                $bots++;
            }
        }

        $createdAt = $guild->created_at instanceof \DateTimeInterface
            ? $guild->created_at
            : new \DateTime($guild->created_at);

        $embed = new Embed($discord);
        $embed->setTitle("📊 Statistiques du serveur");
        $embed->setColor(0x5865F2);
        $embed->addField([
            'name' => '👥 Membres totaux',
            'value' => (string) $memberCount,
            'inline' => true
        ]);
        $embed->addField([
            'name' => '🤖 Bots',
            'value' => (string) $bots,
            'inline' => true
        ]);
        $embed->addField([
            'name' => '🙋 Humains',
            'value' => (string) ($memberCount - $bots),
            'inline' => true
        ]);
        $embed->addField([
            'name' => '📛 Rôles',
            'value' => (string) $roleCount,
            'inline' => true
        ]);
        $embed->addField([
            'name' => '💬 Textuels',
            'value' => (string) $textChannels,
            'inline' => true
        ]);
        $embed->addField([
            'name' => '🔊 Vocaux',
            'value' => (string) $voiceChannels,
            'inline' => true
        ]);
        $embed->addField([
            'name' => '📆 Créé le',
            'value' => $createdAt->format('d/m/Y H:i'),
            'inline' => false
        ]);

        $interaction->respondWithMessage(
            MessageBuilder::new()->addEmbed($embed)
        );
    }
}
