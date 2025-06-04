<?php

namespace Events;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Builders\MessageBuilder;
use PDO;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class ModLogger
{
    public static function logAction(
        Discord $discord,
        string $guildId,
        string $action,
        string $targetId,
        string $staffId,
        string $reason,
        int $color = 0xFF0000
    ): void {
        // 🔌 Connexion à la BDD pour récupérer le salon configuré
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT channel_id FROM modlog_config WHERE server_id = ?");
            $stmt->execute([$guildId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $channelId = $result['channel_id'] ?? null;
        } catch (\PDOException $e) {
            echo "❌ Erreur BDD ModLogger : " . $e->getMessage() . "\n";
            return;
        }

        // 🔎 Si aucun salon n’est défini
        if (!$channelId) {
            echo "ℹ️ Aucun salon mod-log défini pour le serveur $guildId. Action '$action' non loggée.\n";
            return;
        }

        $guild = $discord->guilds->get('id', $guildId);
        if (!$guild) {
            echo "❌ Serveur $guildId introuvable.\n";
            return;
        }

        $channel = $guild->channels->get('id', $channelId);
        if (!$channel) {
            echo "❌ Salon mod-log $channelId introuvable dans le serveur $guildId.\n";
            return;
        }

        // 📝 Création de l’embed
        $embed = new Embed($discord);
        $embed->setTitle("🔔 $action")
            ->setColor($color)
            ->addField(['name' => 'Utilisateur', 'value' => "<@$targetId>", 'inline' => false])
            ->addField(['name' => 'Modérateur', 'value' => "<@$staffId>", 'inline' => false])
            ->addField(['name' => 'Motif', 'value' => $reason, 'inline' => false])
            ->setTimestamp();

        // ✅ Envoi du message
        $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
    }
}
