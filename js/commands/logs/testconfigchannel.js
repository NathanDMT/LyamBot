import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('testconfigchannel')
  .setDescription('Vérifie la configuration du channel de logs');

export async function execute(interaction) {
  await interaction.reply({ content: 'Configuration non implémentée.', ephemeral: true });
}
