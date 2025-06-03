<?php

namespace Commands\Logs;

use Discord\Discord;
use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Interactions\Interaction;
use Events\ModLogger;
use Events\LogColors;
use PDO;

class TestConfigChannelCommand
{
    public static function register(Discord $discord): CommandBuilder
    {
        return CommandBuilder::new()
            ->setName('testchannel')
            ->setDescription('Teste les salons de modlogs et d’annonce configurés');
    }

    public static function handle(Interaction $interaction, Discord $discord): void
    {
        $user = $interaction->member?->user;
        $staffId = $user?->id ?? '0';
        $guildId = $interaction->guild_id;

        // Envoi d’un log test dans le salon mod-log
        ModLogger::logAction(
            $discord,
            $guildId,
            'Test Mod-Log',
            $user->id,
            $staffId,
            'Commande /testmodlog exécutée',
            LogColors::get('Test')
        );

        $announcementList = '';
        $modlogList = '';

        try {
            $pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');

            // Salons d'annonce actifs
            $stmt = $pdo->prepare("SELECT event_type, channel_id FROM event_config WHERE server_id = ? AND enabled = 1");
            $stmt->execute([$guildId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($rows) {
                foreach ($rows as $row) {
                    $announcementList .= "• `{$row['event_type']}` → <#{$row['channel_id']}>\n";
                }
            } else {
                $announcementList = "Aucun salon d'annonce configuré.";
            }

            // Salons de logs mod
            $stmt2 = $pdo->prepare("SELECT event_type, channel_id FROM modlog_config WHERE server_id = ?");
            $stmt2->execute([$guildId]);
            $rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            if ($rows2) {
                foreach ($rows2 as $row) {
                    $modlogList .= "• `{$row['event_type']}` → <#{$row['channel_id']}>\n";
                }
            } else {
                $modlogList = "Aucun salon de log mod configuré.";
            }

        } catch (\PDOException $e) {
            $announcementList = "❌ Erreur BDD : " . $e->getMessage();
            $modlogList = "❌ Erreur BDD : " . $e->getMessage();
        }

        $embed = new Embed($discord);
        $embed->setTitle("✅ Test exécuté avec succès");
        $embed->setDescription("Un message test a été envoyé dans le salon de logs si configuré.");
        $embed->addField(['name' => '📣 Salons d’annonces actifs', 'value' => $announcementList, 'inline' => false]);
        $embed->addField(['name' => '🛡️ Salons de logs mod', 'value' => $modlogList, 'inline' => false]);
        $embed->setColor(0x00FF00);

        $interaction->respondWithMessage(MessageBuilder::new()->setEmbeds([$embed])->setFlags(64));
    }
}
