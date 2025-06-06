# 🤖 LyamBot

LyamBot est maintenant un bot Discord développé en **Node.js** avec la bibliothèque [`discord.js`](https://discord.js.org). Il utilise **SQLite** (ou **MySQL**) pour le stockage des données via les librairies `better-sqlite3` et `mysql2`. Le bot propose toujours un système de modération, d’XP, des sondages et d’autres fonctionnalités.
Toutes les anciennes commandes PHP ont été réécrites en JavaScript dans le dossier `js/commands`.

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

### 2. Installer les dépendances Node
```bash
npm install
```

### 3. Lancer le bot
```bash
npm start
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
│   └── Utils/             # Fonctions utilitaires (connexion PDO, etc.)
├── index.js               # Point d’entrée du bot
├── package.json           # Dépendances Node
├── .env                   # Configuration (non versionnée)
└── README.md              # Ce fichier
```

## ✅ Exemples de commandes
```bash
/ping

/coinflip

/dice

/poll question:"Préférez-vous PHP ou JS ?" options:"PHP,JS" duration:"1h"

/serverinfo

/serverstats

/userinfo utilisateur:@Nathan

/help

/invite

/annonce message:"Ceci est une annonce"

/kick utilisateur:@Membre raison:"trop bruyant"

/ban utilisateur:@Membre raison:"trop bruyant"

/purge nombre:10

/rank

/leaderboard
```

## 💡 Dépendances principales
```bash
discord.js

dotenv

better-sqlite3

mysql2
```

## 📄 Licence
Ce projet est sous licence MIT.
Tu peux l’utiliser, le modifier et le redistribuer librement.

## 👤 Auteur
Nathan DMT
GitHub : @NathanDMT
