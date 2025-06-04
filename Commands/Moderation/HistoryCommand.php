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
use PDO;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class HistoryCommand
{
    private static array $paginationData = [];

    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('history')
            ->setDescription("Affiche l'historique des sanctions d’un membre")
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
        $userId = null;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'user') {
                $userId = $option->value;
            }
        }

        if (!$userId) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ ID utilisateur manquant.")->setFlags(64)
            );
            return;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM sanctions WHERE user_id = ? ORDER BY date DESC");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("Erreur BDD : " . $e->getMessage())->setFlags(64)
            );
            return;
        }

        if (empty($rows)) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("🔍 Aucun historique trouvé pour cet utilisateur.")->setFlags(64)
            );
            return;
        }

        // ✅ On acknowledge d'abord pour débloquer Discord
        $interaction->acknowledge()->then(function () use ($interaction, $discord, $rows, $userId) {
            $embed = self::buildEmbed($discord, $rows, $userId, 0);
            $buttons = self::buildButtons(0, ceil(count($rows) / 5));

            $interaction->sendFollowUpMessage(
                MessageBuilder::new()
                    ->addEmbed($embed)
                    ->addComponent($buttons)
            )->then(function ($msg) use ($rows, $userId) {
                $messageId = $msg->id;
                self::$paginationData[$messageId] = [
                    'userId' => $userId,
                    'history' => $rows,
                    'page' => 0
                ];
                echo "📦 Pagination enregistrée pour message $messageId (history)\n";
            });
        });
    }

    public static function handleButton(Interaction $interaction, Discord $discord): void
    {
        $messageId = $interaction->message?->id;
        $customId = $interaction->data->custom_id;

        if (!isset(self::$paginationData[$messageId])) {
            echo "❌ Aucune pagination trouvée pour le message $messageId (history)\n";
            $interaction->acknowledge(); // évite l’erreur de non-réponse
            return;
        }

        $data = &self::$paginationData[$messageId];
        $totalPages = ceil(count($data['history']) / 5);

        if (str_starts_with($customId, 'history_next') && $data['page'] < $totalPages - 1) {
            $data['page']++;
        } elseif (str_starts_with($customId, 'history_prev') && $data['page'] > 0) {
            $data['page']--;
        } else {
            $interaction->acknowledge();
            return;
        }

        $embed = self::buildEmbed($discord, $data['history'], $data['userId'], $data['page']);
        $buttons = self::buildButtons($data['page'], $totalPages);

        $interaction->updateMessage(
            MessageBuilder::new()
                ->addEmbed($embed)
                ->addComponent($buttons)
        )->then(
            fn() => print "✅ Historique mis à jour à la page {$data['page']} (message $messageId)\n",
            fn($e) => print "❌ Erreur updateMessage (history) : {$e->getMessage()}\n"
        );
    }

    private static function buildEmbed(Discord $discord, array $history, string $userId, int $page): Embed
    {
        $perPage = 5;
        $start = $page * $perPage;
        $slice = array_slice($history, $start, $perPage);
        $totalPages = ceil(count($history) / $perPage);

        $embed = new Embed($discord);
        $embed->setTitle("📄 Historique des sanctions de <@$userId>");
        $embed->setColor(0xFFA500);
        $embed->setFooter("Page " . ($page + 1) . " / $totalPages");

        foreach ($slice as $entry) {
            $embed->addField([
                'name' => strtoupper($entry['type']) . " - " . date('d/m/Y H:i', strtotime($entry['date'])),
                'value' => "👤 Modérateur: <@{$entry['moderator_id']}>\n✏️ Raison: `{$entry['reason']}`",
                'inline' => false
            ]);
        }

        return $embed;
    }

    private static function buildButtons(int $page, int $totalPages): ActionRow
    {
        return ActionRow::new()
            ->addComponent(
                Button::new(Button::STYLE_SECONDARY)
                    ->setLabel("⬅️ Précédent")
                    ->setCustomId("history_prev")
                    ->setDisabled($page === 0)
            )
            ->addComponent(
                Button::new(Button::STYLE_SECONDARY)
                    ->setLabel("Suivant ➡️")
                    ->setCustomId("history_next")
                    ->setDisabled($page >= $totalPages - 1)
            );
    }
}
