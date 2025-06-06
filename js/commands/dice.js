import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('dice')
  .setDescription('Lance un dé à 6 faces');

export async function execute(interaction) {
  const roll = Math.floor(Math.random() * 6) + 1;
  await interaction.reply({ content: `🎲 Tu as lancé un **${roll}** !` });
}
