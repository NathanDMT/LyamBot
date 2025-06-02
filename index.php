<?php

require __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Poll\PollChecker;
use XP\XPSystem;

$RELOAD_COMMANDS = true;

$startTime = microtime(true);

// AFFICHAGE DES LOGS DE DIFF NIVEAUX
$logger = new Logger('discord');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));


// MASQUAGE DU TOKEN
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$token = $_ENV['DISCORD_TOKEN'];


// FORCE .ENV A SE CHARGER
$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$name, $value] = explode('=', $line, 2);
    putenv(trim($name) . '=' . trim($value));
}


// Chargement récursif de toutes les commandes (y compris sous-dossiers)
function getCommandClasses(string $namespace = 'Commands\\', string $directory = __DIR__ . '/commands'): array
{
    $classes = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/^([A-Z][A-Za-z0-9]*)Command\.php$/', $file->getFilename())) {
            $relativePath = str_replace([$directory . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
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

$discord = new Discord([
    'token' => $token,
    'logger' => $logger,
    'loadAllMembers' => true,
    'intents' => Intents::getDefaultIntents()
        | Intents::GUILD_MESSAGES
        | Intents::GUILDS
        | Intents::GUILD_MEMBERS,
]);

$commandClasses = getCommandClasses();
$xpSystem = new XPSystem();

$discord->on('init', function (Discord $discord) use ($commandClasses, $xpSystem, $startTime, $RELOAD_COMMANDS) {
    echo "✅ Connecté en tant que {$discord->user->username}\n";
    printf("🚀 Démarrage en %.2f sec\n", microtime(true) - $startTime);

    $pdo = new PDO('mysql:host=localhost;dbname=lyam;charset=utf8mb4', 'root', 'root');

    // Lancer le poll checker
    $pollChecker = new PollChecker($pdo, $discord);
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
            $discord->application->commands->save(
                new \Discord\Parts\Interactions\Command\Command($discord, $data)
            )->then(
                fn() => print "✅  $commandName enregistrée\n",
                fn($e) => print "❌  Erreur sur $commandName : {$e->getMessage()}\n"
            );
        } else {
            echo "⏩  $commandName chargée sans mise à jour API\n";
        }

    }

    if (isset($commands['help'])) {
        \Commands\Utility\HelpCommand::setLoadedCommands($commands);
    }


    // 🔥 Ajout du système d'XP à chaque message
    $discord->on(Event::MESSAGE_CREATE, function ($message) use ($xpSystem, $discord) {
        $xpSystem->handleMessage($message, $discord);
    });

    // 🔁 Gestion des commandes
    $discord->on(Event::INTERACTION_CREATE, function ($interaction) use ($commands, $discord) {
        if ($interaction->type === \Discord\InteractionType::APPLICATION_COMMAND) {
            $name = $interaction->data->name;
            echo "➡ Commande slash : $name\n";

            if (isset($commands[$name])) {
                $commands[$name]::handle($interaction, $discord);
            } else {
                $interaction->respondWithMessage("Commande inconnue : `$name`", true);
            }

        } elseif ($interaction->type === \Discord\InteractionType::MESSAGE_COMPONENT) {
            $customId = $interaction->data->custom_id ?? '';
            echo "➡ Interaction bouton : $customId\n";

            if (str_starts_with($customId, 'warnlist_')) {
                echo "➡ Appel de handleButton()\n";
                \Commands\Moderation\WarnlistCommand::handleButton($interaction, $discord);
            }
        }
    });
});

$discord->run();
