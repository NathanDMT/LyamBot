import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('serverinfo')
  .setDescription('Affiche des informations sur le serveur');

export async function execute(interaction) {
  const guild = interaction.guild;
  if (!guild) {
    await interaction.reply({ content: '❌ Impossible de récupérer les informations du serveur.', ephemeral: true });
    return;
  }

  const creation = Math.floor(guild.createdTimestamp / 1000);
  const owner = await guild.fetchOwner();

  await interaction.reply({
    embeds: [{
      title: '📊 Informations du serveur',
      description: `Voici les informations de **${guild.name}**`,
      thumbnail: { url: guild.iconURL() },
      color: 0x00AAFF,
      fields: [
        { name: '🆔 ID', value: guild.id, inline: true },
        { name: '👑 Propriétaire', value: `<@${owner.id}>`, inline: true },
        { name: '👥 Membres', value: guild.memberCount.toString(), inline: true },
        { name: '📅 Création', value: `<t:${creation}:F>`, inline: true }
      ]
    }]
  });
}
