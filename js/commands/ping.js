import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('ping')
  .setDescription('Affiche la latence du bot');

export async function execute(interaction) {
  const sent = await interaction.reply({ content: 'Pong ?', fetchReply: true });
  await interaction.editReply(`Pong ! Latence : ${sent.createdTimestamp - interaction.createdTimestamp} ms`);
}
