import { Client, Collection, GatewayIntentBits, Events } from 'discord.js';
import dotenv from 'dotenv';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import xpSystem from './js/xp/xpSystem.js';
import initPollChecker from './js/pollChecker.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const client = new Client({
  intents: [
    GatewayIntentBits.Guilds,
    GatewayIntentBits.GuildMessages,
    GatewayIntentBits.MessageContent,
  ]
});

client.commands = new Collection();
const commandsPath = path.join(__dirname, 'js/commands');

async function loadCommands(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      await loadCommands(path.join(dir, entry.name));
    } else if (entry.name.endsWith('.js')) {
      const rel = path.relative(__dirname, path.join(dir, entry.name)).replace(/\\/g, '/');
      const command = await import(`./${rel}`);
      if (command.data && command.execute) {
        client.commands.set(command.data.name, command);
      }
    }
  }
}

await loadCommands(commandsPath);

client.once(Events.ClientReady, () => {
  console.log(`Connecté en tant que ${client.user.tag}`);
  initPollChecker(client);
});

client.on(Events.MessageCreate, (message) => {
  xpSystem.handleMessage(message);
});

client.on(Events.InteractionCreate, async (interaction) => {
  if (!interaction.isChatInputCommand()) return;
  const command = client.commands.get(interaction.commandName);
  if (!command) return;
  try {
    await command.execute(interaction, client);
  } catch (err) {
    console.error(err);
    if (interaction.replied || interaction.deferred) {
      await interaction.followUp({ content: 'Erreur lors de l\'exécution de la commande.', ephemeral: true });
    } else {
      await interaction.reply({ content: 'Erreur lors de l\'exécution de la commande.', ephemeral: true });
    }
  }
});

client.login(process.env.DISCORD_TOKEN);
