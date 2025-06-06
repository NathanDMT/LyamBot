import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('meteor-showers')
  .setDescription('Infos sur les pluies de météores (fictif)');

export async function execute(interaction) {
  await interaction.reply({ content: '☄️ Fonctionnalité indisponible hors-ligne.', ephemeral: true });
}
