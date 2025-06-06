import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('coinflip')
  .setDescription('Lance une pièce et retourne pile ou face');

export async function execute(interaction) {
  const result = Math.random() < 0.5 ? '🪙 Pile !' : '🪙 Face !';
  await interaction.reply({ content: `Résultat : **${result}**` });
}
