import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('planets')
  .setDescription('Informations sur les planètes (fictif)');

export async function execute(interaction) {
  await interaction.reply({ content: '🪐 Fonctionnalité indisponible hors-ligne.', ephemeral: true });
}
