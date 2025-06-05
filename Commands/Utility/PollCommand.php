<?php

namespace Commands\Utility;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class PollCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        $questionOption = new Option($discord, [
            'name' => 'question',
            'description' => 'La question du sondage',
            'type' => Option::STRING,
            'required' => true,
        ]);

        $durationOption = new Option($discord, [
            'name' => 'duration',
            'description' => 'Durée du sondage (en minutes)',
            'type' => Option::INTEGER,
            'required' => true,
        ]);

        return CommandBuilder::new()
            ->setName('poll')
            ->setDescription('Crée un sondage avec une durée de fin')
            ->addOption($questionOption)
            ->addOption($durationOption);
    }

    public static function handle(Interaction $interaction, Discord $discord)
    {
        $question = null;
        $duration = null;

        foreach ($interaction->data->options as $option) {
            if ($option->name === 'question') {
                $question = $option->value;
            } elseif ($option->name === 'duration') {
                $duration = (int) $option->value;
            }
        }

        if (!$question || !$duration) {
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent("❌ Les options sont manquantes ou invalides."),
                true
            );
            return;
        }

        $embed = new Embed($discord);
        $embed->setTitle('📊 Sondage :')
            ->setDescription('**'.$question.'**')
            ->addFieldValues('Durée', "$duration minute(s)", true)
            ->setColor(0x0099ff)
            ->setTimestamp();

        $interaction->acknowledge();

        $interaction->sendFollowUpMessage(MessageBuilder::new()->addEmbed($embed))->then(function ($message) use ($question, $duration) {
                // Ajoute les réactions
            $pdo = getPDO();
            $message->react('✅');
                $message->react('❌');

            $stmt = $pdo->prepare("INSERT INTO polls (message_id, channel_id, question, fin_at) VALUES (?, ?, ?, ?)");

            $messageId = $message->id;
            $channelId = $message->channel_id;
            $finAt = (new \DateTime())->modify("+{$duration} minutes")->format('Y-m-d H:i:s');

            $stmt->execute([$messageId, $channelId, $question, $finAt]);
        });
    }
}
