import { SlashCommandBuilder } from 'discord.js';
import fs from 'fs';
import path from 'path';

export const data = new SlashCommandBuilder()
  .setName('reload')
  .setDescription('Recharge toutes les commandes');

export async function execute(interaction, client) {
  const commandsPath = path.join(path.dirname(new URL(import.meta.url).pathname), '..');
  client.commands.clear();
  for (const folder of fs.readdirSync(commandsPath)) {
    const folderPath = path.join(commandsPath, folder);
    if (fs.statSync(folderPath).isDirectory()) {
      for (const file of fs.readdirSync(folderPath)) {
        if (!file.endsWith('.js')) continue;
        const cmd = await import(`../${folder}/${file}`);
        client.commands.set(cmd.data.name, cmd);
      }
    }
  }
  await interaction.reply({ content: 'Commandes rechargées.' });
}
