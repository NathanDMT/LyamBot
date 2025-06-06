import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('restart')
  .setDescription('Redémarre le bot');

export async function execute(interaction, client) {
  await interaction.reply('Redémarrage...');
  await client.destroy();
  process.exit(0);
}
