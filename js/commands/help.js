import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('help')
  .setDescription('Affiche la liste des commandes disponibles');

export async function execute(interaction, client) {
  const commands = [...client.commands.values()]
    .map(c => `\`/${c.data.name}\` - ${c.data.description}`)
    .join('\n');
  await interaction.reply({ content: commands, ephemeral: true });
}
