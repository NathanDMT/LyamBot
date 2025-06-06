import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('invite')
  .setDescription("Envoie le lien d'invitation du bot");

export async function execute(interaction) {
  const clientId = process.env.DISCORD_CLIENT_ID;
  if (!clientId) {
    await interaction.reply({ content: '❌ DISCORD_CLIENT_ID manquant.', ephemeral: true });
    return;
  }
  const url = `https://discord.com/oauth2/authorize?client_id=${clientId}&scope=bot%20applications.commands&permissions=8`;
  await interaction.reply({ content: `[Clique ici pour inviter le bot](${url})` });
}
