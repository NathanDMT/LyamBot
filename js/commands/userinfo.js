import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('userinfo')
  .setDescription("Affiche les infos d'un utilisateur")
  .addUserOption(o =>
    o.setName('utilisateur')
      .setDescription("Utilisateur à inspecter")
      .setRequired(false)
  );

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur') || interaction.user;
  const member = await interaction.guild.members.fetch(user.id);
  const created = Math.floor(user.createdTimestamp / 1000);
  const joined = Math.floor(member.joinedTimestamp / 1000);

  await interaction.reply({
    embeds: [{
      title: '🔎 Informations de l\'utilisateur',
      thumbnail: { url: user.displayAvatarURL() },
      color: 0x5865F2,
      fields: [
        { name: '👥 Utilisateur', value: `<@${user.id}>`, inline: true },
        { name: '🆔 ID', value: user.id, inline: true },
        { name: '📅 Créé le', value: `<t:${created}:F>`, inline: false },
        { name: '📅 Rejoint le', value: `<t:${joined}:F>`, inline: false },
        { name: '🤖 Bot', value: user.bot ? 'Oui' : 'Non', inline: true }
      ]
    }]
  });
}
