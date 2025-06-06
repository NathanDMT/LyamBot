import { SlashCommandBuilder, ChannelType } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('serverstats')
  .setDescription('Affiche les statistiques du serveur');

export async function execute(interaction) {
  const guild = interaction.guild;
  const bots = guild.members.cache.filter(m => m.user.bot).size;
  const text = guild.channels.cache.filter(c => c.type === ChannelType.GuildText).size;
  const voice = guild.channels.cache.filter(c => c.type === ChannelType.GuildVoice).size;
  const creation = Math.floor(guild.createdTimestamp / 1000);

  await interaction.reply({
    embeds: [{
      title: '📊 Statistiques du serveur',
      color: 0x5865F2,
      fields: [
        { name: '👥 Membres totaux', value: `${guild.memberCount}`, inline: true },
        { name: '🤖 Bots', value: `${bots}`, inline: true },
        { name: '🙋 Humains', value: `${guild.memberCount - bots}`, inline: true },
        { name: '📛 Rôles', value: `${guild.roles.cache.size}`, inline: true },
        { name: '💬 Textuels', value: `${text}`, inline: true },
        { name: '🔊 Vocaux', value: `${voice}`, inline: true },
        { name: '📆 Créé le', value: `<t:${creation}:F>`, inline: false }
      ]
    }]
  });
}
