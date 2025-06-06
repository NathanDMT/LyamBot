<?php

namespace Commands\Moderation;

use Discord\Builders\CommandBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Events\LogColors;
use Events\ModLogger;
use PDO;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class WarnlistCommand
{
    private static array $paginationData = [];

    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('warnlist')
            ->setDescription('Affiche la liste des avertissements d’un utilisateur')
            ->addOption(
                (new Option($discord))
                    ->setName('user')
                    ->setDescription("Utilisateur ciblé")
                    ->setType(6)
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $pdo = getPDO();
        if (!$interaction->member->getPermissions()->kick_members) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Tu n’as pas la permission d’utiliser cette commande.")->setFlags(64)
            );
            return;
        }

        $userId = null;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'user') {
                $userId = $option->value;
            }
        }
        $guildId = $interaction->guild_id;

        $stmt = $pdo->prepare("SELECT reason, warned_by, created_at FROM warnings WHERE user_id = ? AND server_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $guildId]);
        $warns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($warns)) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("ℹ️ Aucun avertissement trouvé pour <@$userId>.")->setFlags(64)
            );
            return;
        }

        $embed = self::buildEmbed($discord, $warns, $userId, 0);
        $components = self::buildButtons(0, ceil(count($warns) / 5));

        $interaction->respondWithMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($components)
        )->then(function ($response) use ($interaction, $warns, $userId) {
            $messageId = $interaction->channel?->last_message_id;

            if ($messageId) {
                WarnlistCommand::$paginationData[$messageId] = [
                    'warns' => $warns,
                    'userId' => $userId,
                    'page' => 0
                ];
                echo "📦 Pagination enregistrée pour message $messageId\n";
            } else {
                echo "❌ Impossible de déterminer le messageId à partir de la réponse.\n";
            }
        });

        // Récupération du tag de l'utilisateur modérateur
        $staffTag = $interaction->member?->user?->tag ?? 'Inconnu';

        // Log de la consultation dans #mod-logs ou par ID
        $user = $interaction->member?->user;
        $staffId = $user?->id ?? '0';

        ModLogger::logAction(
            $discord,
            $interaction->guild_id,
            'Consultation des warns',
            $userId,
            $staffId,
            'Consultation via /warnlist',
            LogColors::get('Warnlist')
        );
    }

    public static function handleButton(Interaction $interaction, Discord $discord): void
    {
        $messageId = $interaction->message?->id;
        $customId = $interaction->data->custom_id;

        if (!isset(self::$paginationData[$messageId])) {
            echo "❌ Aucune pagination trouvée pour le message $messageId\n";
            // ❌ NE PAS refaire de acknowledge ici
            return;
        }

        $data = &self::$paginationData[$messageId];
        $totalPages = ceil(count($data['warns']) / 5);
        $oldPage = $data['page'];

        if ($customId === 'warnlist_next' && $data['page'] < $totalPages - 1) {
            $data['page']++;
        } elseif ($customId === 'warnlist_prev' && $data['page'] > 0) {
            $data['page']--;
        } else {
            // ❌ Pas de changement → on ne fait rien de plus (déjà acknowledged dans index.php)
            return;
        }

        // ✅ Mise à jour du message
        $embed = self::buildEmbed($discord, $data['warns'], $data['userId'], $data['page']);
        $components = self::buildButtons($data['page'], $totalPages);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($components)
        )->then(
            fn() => print "✅ Page mise à jour vers {$data['page']} pour message $messageId\n",
            fn($e) => print "❌ Erreur updateMessage : {$e->getMessage()}\n"
        );
    }


    private static function buildEmbed(Discord $discord, array $warns, string $userId, int $page): Embed
    {
        $perPage = 5;
        $totalPages = ceil(count($warns) / $perPage);
        $embed = new Embed($discord);
        $embed->setColor(0xffa500);
        $embed->setFooter("Page " . ($page + 1) . " / $totalPages");
        $embed->addField([
            'name' => "📋 Avertissements de",
            'value' => "<@$userId>",
            'inline' => false,
        ]);


        $start = $page * $perPage;
        foreach (array_slice($warns, $start, $perPage) as $i => $warn) {
            $index = $start + $i + 1;
            $date = date('d/m/Y H:i', strtotime($warn['created_at']));
            $embed->addFieldValues("• Warn #$index", "Date : `$date`\nRaison : {$warn['reason']}\nPar : <@{$warn['warned_by']}>");
        }

        return $embed;
    }

    private static function buildButtons(int $page, int $totalPages): ActionRow
    {
        return ActionRow::new()
            ->addComponent(
                Button::new(Button::STYLE_SECONDARY)
                    ->setLabel("⬅️ Précédent")
                    ->setCustomId("warnlist_prev")
                    ->setDisabled($page === 0)
            )
            ->addComponent(
                Button::new(Button::STYLE_SECONDARY)
                    ->setLabel("Suivant ➡️")
                    ->setCustomId("warnlist_next")
                    ->setDisabled($page >= $totalPages - 1)
            );
    }
}
