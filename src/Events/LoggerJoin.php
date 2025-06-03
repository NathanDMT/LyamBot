<?php

namespace Events;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Builders\MessageBuilder;
use PDO;
use Carbon\Carbon;

class LoggerJoin
{
    public static function register(Discord $discord): void
    {
        $discord->on(Event::GUILD_MEMBER_ADD, function (Member $member) use ($discord) {
            try {
                $pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');
                $stmt = $pdo->prepare("SELECT channel_id FROM modlog_config WHERE server_id = ? AND event_type = 'join'");
                $stmt->execute([$member->guild_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $channelId = $result['channel_id'] ?? null;
            } catch (\PDOException $e) {
                echo "❌ Erreur BDD JoinLogger : " . $e->getMessage() . "\n";
                return;
            }

            if (!$channelId) return;

            $guild = $discord->guilds->get('id', $member->guild_id);
            if (!$guild) return;

            $channel = $guild->channels->get('id', $channelId);
            if (!$channel) return;

            $user = $member->user;

            // Corriger le calcul de la date de création de compte
            $snowflake = (int) $user->id;
            $discordEpoch = 1420070400000;
            $createdAtTimestamp = (int) ($snowflake >> 22) + $discordEpoch;
            $createdAt = Carbon::createFromTimestampMs($createdAtTimestamp)->startOfSecond();
            Carbon::setLocale('fr');
            $age = $createdAt->diffForHumans(Carbon::now(), ['parts' => 3, 'syntax' => Carbon::DIFF_RELATIVE_TO_NOW]);

            // Rang d’arrivée
            $memberCount = $guild->member_count ?? count($guild->members);

            // Heure actuelle
            $now = Carbon::now();
            $joinedAt = $now->format('d/m/Y H:i');

            $embed = new Embed($discord);
            $embed->setAuthor("{$user->username}", $user->avatar, $user->avatar)
                ->setTitle("Nouveau membre")
                ->setDescription("<@{$user->id}> est le **{$memberCount}e** membre à rejoindre.\nCompte créé $age.")
                ->setFooter("ID : {$user->id} • Arrivé le $joinedAt")
                ->setColor(0x00FF00);

            $channel->sendMessage(MessageBuilder::new()->addEmbed($embed));
        });
    }
}
