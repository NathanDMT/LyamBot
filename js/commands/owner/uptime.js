import { SlashCommandBuilder } from 'discord.js';

const startTime = Date.now();

export const data = new SlashCommandBuilder()
  .setName('uptime')
  .setDescription("Affiche l'uptime du bot");

export async function execute(interaction) {
  const diff = Date.now() - startTime;
  const seconds = Math.floor(diff / 1000);
  await interaction.reply({ content: `Uptime : ${seconds} secondes` });
}
