<?php

require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;
use Commands\Astronomy\ApodCommand;
use Commands\Astronomy\IssLocationCommand;
use Commands\Astronomy\MeteorShowersCommand;
use Commands\Moderation\HistoryCommand;
use Commands\Moderation\WarnlistCommand;
use Commands\Owner\UptimeCommand;
use Commands\Utility\HelpCommand;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\InteractionType;
use Discord\Parts\User\Activity;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Events\AnnonceGuildBoost;
use Events\AnnonceGuildMemberAdd;
use Events\AnnonceGuildMemberRemove;
use Events\LoggerBoost;
use Events\LoggerJoin;
use Events\LoggerLeave;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Poll\PollChecker;
use XP\XPSystem;

$RELOAD_COMMANDS = true;

// CALCUL TEMPS DE LANCEMENT
$startTime = microtime(true);

// DESACTIVE LES LOGS INTEMPESSTIVES
$logger = new Logger('discord');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));

// MODIFIE LA LANGUE DES INFOS D'EMBEDS DE JOIN/LEAVES...
Carbon::setLocale('fr');

// LOAD LE .ENV POUR LA CONNEXION AU BOT ET A LA BDD
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
require_once __DIR__ . '/src/utils/database.php';
$token = $_ENV['DISCORD_TOKEN'];

$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$name, $value] = explode('=', $line, 2);
    putenv(trim($name) . '=' . trim($value));
}

function getCommandClasses(string $namespace = 'Commands\\', string $directory = __DIR__ . '/commands'): array
{
    $classes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/^([A-Z][A-Za-z0-9]*)Command\\.php$/', $file->getFilename())) {
            $relativePath = str_replace(array($directory . DIRECTORY_SEPARATOR, '.php', DIRECTORY_SEPARATOR), array('', '', '\\'), $file->getPathname());
            $class = $namespace . $relativePath;
            require_once $file->getPathname();
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }
    }
    return $classes;
}

echo "Lancement du bot...\n";

try {
    $discord = new Discord([
        'token' => $token,
        'logger' => $logger,
        'loadAllMembers' => true,
        'intents' => Intents::getDefaultIntents()
            | Intents::GUILD_MESSAGES
            | Intents::GUILDS
            | Intents::GUILD_MEMBERS,
    ]);
} catch (IntentException $e) { }

$commandClasses = getCommandClasses();
$xpSystem = new XPSystem();

$discord->on('ready', function (Discord $discord) use ($commandClasses, $xpSystem, $startTime, $RELOAD_COMMANDS) {
    global $pdo;

    echo "✅ Connecté en tant que {$discord->user->username}\n";
    printf("🚀 Démarrage en %.2f sec\n", microtime(true) - $startTime);

    $activity = $discord->factory(Activity::class, [
        'name' => '/help pour obtenir de l\'aide',
        'type' => Activity::TYPE_GAME
    ]);
    $discord->updatePresence($activity);

    // Enregistrement des events UNE FOIS au démarrage :
    UptimeCommand::setStartTime();
    LoggerJoin::register($discord);
    LoggerLeave::register($discord);
    LoggerBoost::register($discord);

    AnnonceGuildBoost::register($discord);
    AnnonceGuildMemberAdd::register($discord);
    AnnonceGuildMemberRemove::register($discord);

    $pollChecker = new PollChecker($discord);
    $pollChecker->start();

    $commands = [];

    foreach ($commandClasses as $class) {
        $builder = $class::register($discord);

        if (!is_object($builder) || !method_exists($builder, 'toArray')) {
            echo "❌  {$class}::register() ne retourne pas un objet valide CommandBuilder\n";
            continue;
        }

        $data = $builder->toArray();
        $commandName = $data['name'];
        $commands[$commandName] = $class;

        if ($RELOAD_COMMANDS) {
            $command = new \Discord\Parts\Interactions\Command\Command($discord, $data);

            if (empty($command->application_id)) {
                $command->application_id = $discord->application->id;
            }

            $discord->application->commands->save($command)->then(
                fn() => print "✅  $commandName enregistrée\n",
                fn($e) => print "❌  Erreur sur $commandName : {$e->getMessage()}\n"
            );
        }
    }

        if (isset($commands['help'])) {
        HelpCommand::setLoadedCommands($commands);
    }

    $discord->on(Event::MESSAGE_CREATE, function ($message) use ($xpSystem, $discord) {
        $xpSystem->handleMessage($message, $discord);
    });

    $discord->on(Event::INTERACTION_CREATE, function ($interaction) use ($pdo, $commands, $discord) {
        if ($interaction->type === InteractionType::APPLICATION_COMMAND) {
            $name = $interaction->data->name;
            // echo "➡ Commande slash : $name\n";

            if (isset($commands[$name])) {
                $commands[$name]::handle($interaction, $discord);
            } else {
                $interaction->respondWithMessage("Commande inconnue : `$name`", true);
            }
        } elseif ($interaction->type === InteractionType::MESSAGE_COMPONENT) {
            $customId = $interaction->data->custom_id ?? '';
            // echo "➡ Interaction bouton : $customId\n";

            if (str_starts_with($customId, 'warnlist_')) {
                WarnlistCommand::handleButton($interaction, $discord);
            } elseif (str_starts_with($customId, 'history_')) {
                HistoryCommand::handleButton($interaction, $discord);
            } elseif (str_starts_with($customId, 'apod_show_image_')) {
                ApodCommand::handleButton($interaction, $discord);
            } elseif (str_starts_with($interaction->data->custom_id, 'prev:') || str_starts_with($interaction->data->custom_id, 'next:')) {
                MeteorShowersCommand::handleButton($interaction, $discord);
            } elseif ($interaction->data->name === 'uptime') {
                UptimeCommand::handle($interaction, $discord);
            }
            if ($interaction->type === InteractionType::MESSAGE_COMPONENT && $interaction->data->custom_id === 'refresh_iss') {
                IssLocationCommand::editWithNewData($interaction, $discord);
            }
        }
    });
});

$discord->run();