import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('apod')
  .setDescription('Image astronomique du jour (fictif)');

export async function execute(interaction) {
  await interaction.reply({ content: '🌌 Fonctionnalité indisponible hors-ligne.', ephemeral: true });
}
