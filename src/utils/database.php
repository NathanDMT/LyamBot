<?php
// src/utils/database.php

function getPDO(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $db   = $_ENV['DB_NAME'] ?? 'lyam';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? 'root';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            echo "❌ Erreur de connexion PDO : " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    return $pdo;
}
