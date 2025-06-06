import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('xpconfig')
  .setDescription('Configure le système XP (placeholder)');

export async function execute(interaction) {
  await interaction.reply({ content: 'Configuration XP non implémentée.', ephemeral: true });
}
