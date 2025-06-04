# 🤖 LyamBot

LyamBot est un bot Discord développé en **PHP** avec la bibliothèque [`discord-php`](https://github.com/teamreflex/DiscordPHP), utilisant **MySQL** pour stocker les données et **ReactPHP** pour la boucle d’événements. Il propose un système de modération, un système d’XP, des sondages, des mini-jeux, et bien plus.

## 📦 Fonctionnalités principales

- 🎖️ Système de niveaux (XP) avec configuration par serveur
- 📊 Sondages automatiques avec durée d’expiration
- ⚔️ Commandes de modération (`/ban`, `/kick`, `/warn`, `/mute`, etc.)
- 🧾 Historique des sanctions et pagination via boutons
- 📅 Logs d'événements (join/leave/boost)
- 🧩 Système modulaire avec commandes enregistrées dynamiquement
- 🌤️ Intégration possible d’APIs externes (comme météo, giveaway, etc.)

## 🛠️ Installation

### 1. **Clone le dépôt**
```bash
git clone https://github.com/NathanDMT/LyamBot.git
cd LyamBot
```

### 2. Installer les dépendances PHP
```bash
composer install
```

### 3. Lancer le bot
```bash
php index.php
```

## 📁 Arborescence du projet
```bash
LyamBot/
├── commands/              # Dossiers des slash commandes (modération, xp, jeux, etc.)
├── events/                # Événements Discord (join, leave, boost...)
├── src/
│   ├── XP/                # Système d'expérience (XP)
│   ├── Poll/              # Gestion des sondages
│   ├── Logs/              # Logs serveur
│   └── utils/             # Fonctions utilitaires (connexion PDO, etc.)
├── lyam.sql               # Dump de la base de données
├── index.php              # Point d’entrée du bot
├── composer.json          # Dépendances PHP
├── .env                   # Configuration (non versionnée)
└── README.md              # Ce fichier
```

## ✅ Exemples de commandes
```bash
/warn user:@Nathan reason:"Spam"

/warnlist user:@Nathan

/mute user:@Troll duration:"30m"

/poll question:"Préférez-vous PHP ou JS ?" options:"PHP,JS" duration:"1h"

/xpconfig action:view

/setxp user:@Nathan value:4000

/note user:@Modérateur note:"À surveiller"

/history user:@Nathan
```

## 💡 Dépendances principales
```bash
discord-php

vlucas/phpdotenv

react/event-loop

ext-pdo, ext-json, ext-curl, etc.
```

## 📄 Licence
Ce projet est sous licence MIT.
Tu peux l’utiliser, le modifier et le redistribuer librement.

## 👤 Auteur
Nathan DMT
GitHub : @NathanDMT
