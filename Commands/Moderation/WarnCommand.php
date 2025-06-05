<?php

namespace Commands\Moderation;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Events\LogColors;
use Events\ModLogger;

class WarnCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('warn')
            ->setDescription('Permet de mettre un avertissement à un utilisateur')
            ->addOption(
                (new Option($discord))
                    ->setName('userid')
                    ->setDescription("L'ID de l'utilisateur à avertir")
                    ->setType(6) // USER
                    ->setRequired(true)
            )
            ->addOption(
                (new Option($discord))
                    ->setName('raison')
                    ->setDescription("Raison de l'avertissement")
                    ->setType(3) // STRING
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $pdo = getPDO();
        $userId = null;
        $reason = null;

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'userid') {
                $userId = $option->value;
            }
            if ($option->name === 'raison') {
                $reason = $option->value;
            }
        }

        if (!$userId || !$reason) {
            $embed = new Embed($discord);
            $embed->setTitle("Erreur ❌")
                ->setDescription("Les champs `userid` et `raison` sont obligatoires.")
                ->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        $member = $interaction->member;
        $guild = $interaction->guild;

        if (!$member->getPermissions()->kick_members) {
            $embed = new Embed($discord);
            $embed->setTitle("Accès refusé 🔒")
                ->setDescription("Tu n’as pas la permission de warn les membres.")
                ->setColor(0xFF0000);
            $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
            return;
        }

        // Enregistrement en BDD
        $stmt = $pdo->prepare("INSERT INTO warnings (user_id, warned_by, reason, server_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $member->user->id, $reason, $guild->id]);

        // Log modération
        $staffUser = $interaction->member?->user;
        $staffTag = $staffUser?->username ?? 'Inconnu';
        $staffId = $staffUser?->id ?? '0';

        ModLogger::logAction(
            $discord,
            $interaction->guild_id,
            'Avertissement',
            $userId,
            $staffId,
            $reason,
            LogColors::get('Warn')
        );

        // Compte les warns
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM warnings WHERE user_id = ? AND server_id = ?");
        $countStmt->execute([$userId, $guild->id]);
        $totalWarns = (int) $countStmt->fetchColumn();

        // Tente d'envoyer un MP
        $discord->users->fetch($userId)->then(function ($user) use ($reason, $guild, $totalWarns, $discord) {
            $user->getPrivateChannel()->then(function ($channel) use ($user, $reason, $guild, $totalWarns, $discord) {
                $embed = new Embed($discord);
                $embed->setTitle("⚠️ Tu as reçu un avertissement")
                    ->setDescription("Serveur : **{$guild->name}**\nRaison : `$reason`\nTu as désormais **{$totalWarns}** avertissement(s).")
                    ->setColor(0xFFA500);
                $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
            });
        });

        // Réponse publique
        $embed = new Embed($discord);
        $embed->setTitle("⚠️ Avertissement donné")
            ->setDescription("L'utilisateur <@$userId> a été averti.\n✏️ Raison : `$reason`")
            ->setColor(0xFFA500);

        $interaction->respondWithMessage(MessageBuilder::new()->addEmbed($embed)->setFlags(64));
    }
}
