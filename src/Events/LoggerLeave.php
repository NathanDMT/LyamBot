<?php

namespace Events;

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use PDO;

// Charger la connexion PDO
require_once __DIR__ . '/../../src/utils/database.php';

class LoggerLeave
{
    public static function register(Discord $discord): void
    {
        $pdo = getPDO();

        $discord->on(Event::GUILD_MEMBER_REMOVE, function (Member $member) use ($discord, $pdo) {
            try {
                $stmt = $pdo->prepare("SELECT channel_id FROM modlog_config WHERE server_id = ? AND event_type = 'leave'");
                $stmt->execute([$member->guild_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $channelId = $result['channel_id'] ?? null;
            } catch (\PDOException $e) {
                echo "❌ Erreur BDD LeaveLogger : " . $e->getMessage() . "\n";
                return;
            }

            if (!$channelId) return;

            $guild = $discord->guilds->get('id', $member->guild_id);
            if (!$guild) return;

            $channel = $guild->channels->get('id', $channelId);
            if (!$channel) return;

            $user = $member->user;

            // Durée de présence
            $joinedAt = $member->joined_at ? Carbon::parse($member->joined_at) : null;
            $joinedDuration = $joinedAt ? 'Présent depuis ' . $joinedAt->diffForHumans(null, true) : 'Date d’arrivée inconnue';

            // Liste des rôles
            $roles = array_filter($member->roles->toArray(), fn($r) => $r !== $guild->id);
            $rolesStr = empty($roles)
                ? "*Aucun rôle*"
                : implode(', ', array_map(fn($roleId) => "$roleId", $roles));

            // Date de départ
            $leftAt = Carbon::now()->format('d/m/Y H:i');

            $embed = new Embed($discord);
            $embed->setAuthor("{$user->username}", $user->avatar, $user->avatar)
                ->setTitle("Départ d’un membre")
                ->setDescription("<@{$user->id}> a quitté le serveur.\n$joinedDuration\n\n**Rôles :** $rolesStr")
                ->setFooter("ID : {$user->id} • Parti le $leftAt")
                ->setColor(0xFF5555);

            $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
        });
    }
}
