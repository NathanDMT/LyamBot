import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('iss-location')
  .setDescription("Position actuelle de l'ISS (fictif)");

export async function execute(interaction) {
  await interaction.reply({ content: '🛰️ Fonctionnalité indisponible hors-ligne.', ephemeral: true });
}
