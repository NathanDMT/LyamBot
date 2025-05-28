<?php

namespace Commands\Moderation;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\InteractionResponse;
use PDO;

class WarnlistCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('warnlist')
            ->setDescription('Affiche la liste des avertissements d’un utilisateur')
            ->addOption(
                (new Option($discord))
                    ->setName('userid')
                    ->setDescription("ID de l'utilisateur à consulter")
                    ->setType(3)
                    ->setRequired(true)
            );
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $userId = null;
        foreach ($interaction->data->options as $option) {
            if ($option->name === 'userid') {
                $userId = $option->value;
            }
        }

        $guild = $interaction->guild;
        $member = $interaction->member;

        if (!$member->getPermissions()->kick_members) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Tu n’as pas la permission de voir les warns.")->setFlags(64)
            );
            return;
        }

        $pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
        $stmt = $pdo->prepare("SELECT reason, warned_by, created_at FROM warnings WHERE user_id = ? AND server_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $guild->id]);
        $warns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($warns)) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("ℹ️ Aucun avertissement trouvé pour <@$userId>.")->setFlags(64)
            );
            return;
        }

        $perPage = 5;
        $totalPages = ceil(count($warns) / $perPage);
        $currentPage = 0;

        $buildEmbed = function ($page) use ($warns, $discord, $userId, $perPage, $totalPages): Embed {
            $embed = new Embed($discord);
            $embed->addFieldValues("⚠️ Avertissements de", "<@$userId>");
            $embed->setColor(0xFFA500);
            $embed->setFooter("Page " . ($page + 1) . " / $totalPages");

            $start = $page * $perPage;
            $slice = array_slice($warns, $start, $perPage);

            foreach ($slice as $index => $warn) {
                $date = date('d/m/Y H:i', strtotime($warn['created_at']));
                $embed->addFieldValues(
                    "Warn #" . ($start + $index + 1),
                    "🕒 `$date`\n✏️ {$warn['reason']}\n👮 Donné par : <@{$warn['warned_by']}>"
                );
            }

            return $embed;
        };

        $buildButtons = function ($page) use ($totalPages): ActionRow {
            return ActionRow::new()
                ->addComponent(
                    Button::new(Button::STYLE_SECONDARY)
                        ->setLabel('⬅️ Précédent')
                        ->setCustomId('prev')
                        ->setDisabled($page === 0)
                )
                ->addComponent(
                    Button::new(Button::STYLE_SECONDARY)
                        ->setLabel('Suivant ➡️')
                        ->setCustomId('next')
                        ->setDisabled($page >= $totalPages - 1)
                );
        };

        $interaction->respondWithMessage(
            MessageBuilder::new()
                ->addEmbed($buildEmbed($currentPage))
                ->addComponent($buildButtons($currentPage))
        )->then(function (InteractionResponse $response) use (
            $discord, $interaction, &$currentPage, $buildEmbed, $buildButtons, $totalPages
        ) {
            $messageId = $interaction->id;

            $discord->on('interactionCreate', function (Interaction $buttonInteraction) use (
                &$currentPage, $interaction, $buildEmbed, $buildButtons, $totalPages, $messageId
            ) {
                if (!$buttonInteraction->isComponent()) return;
                if ($buttonInteraction->message?->interaction?->id !== $messageId) return;

                $customId = $buttonInteraction->data->custom_id;

                if ($customId === 'next' && $currentPage < $totalPages - 1) {
                    $currentPage++;
                } elseif ($customId === 'prev' && $currentPage > 0) {
                    $currentPage--;
                } else {
                    return;
                }

                $buttonInteraction->updateMessage(
                    MessageBuilder::new()
                        ->addEmbed($buildEmbed($currentPage))
                        ->addComponent($buildButtons($currentPage))
                );
            });
        });
    }
}
