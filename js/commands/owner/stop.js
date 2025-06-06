import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('stop')
  .setDescription('Arrête le bot');

export async function execute(interaction, client) {
  await interaction.reply('Arrêt...');
  await client.destroy();
  process.exit(0);
}
