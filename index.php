<?php

require __DIR__ . '/vendor/autoload.php';

use Poll\PollChecker;
use XP\XPSystem;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;


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

$discord->on('init', function (Discord $discord) use ($commandClasses, $xpSystem) {
    echo "Bot connecté en tant que {$discord->user->username}\n";

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

        $discord->application->commands->save(
            new \Discord\Parts\Interactions\Command\Command($discord, $data)
        )->then(
            function () use ($commandName) {
                echo "✅  Commande enregistrée : /$commandName\n";
            },
            function ($e) use ($commandName) {
                echo "❌  Erreur lors de l'enregistrement de /$commandName : " . $e->getMessage() . "\n";
            }
        );

    }

    // 🔥 Ajout du système d'XP à chaque message
    $discord->on(Event::MESSAGE_CREATE, function ($message) use ($xpSystem, $discord) {
        $xpSystem->handleMessage($message, $discord);
    });

    // 🔁 Gestion des commandes
    $discord->on(Event::INTERACTION_CREATE, function ($interaction) use ($commands, $discord) {
        $name = $interaction->data->name;

        if (isset($commands[$name])) {
            $commands[$name]::handle($interaction, $discord);
        } else {
            $interaction->respondWithMessage("Commande inconnue : `$name`", true);
        }
    });
});


$discord->run();
