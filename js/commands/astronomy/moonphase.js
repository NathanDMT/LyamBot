import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('moon-phase')
  .setDescription('Phase lunaire actuelle (fictif)');

export async function execute(interaction) {
  await interaction.reply({ content: '🌙 Fonctionnalité indisponible hors-ligne.', ephemeral: true });
}
