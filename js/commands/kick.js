import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('kick')
  .setDescription('Expulse un membre du serveur')
  .addUserOption(o =>
    o.setName('utilisateur')
      .setDescription('Membre à expulser')
      .setRequired(true)
  )
  .addStringOption(o =>
    o.setName('raison')
      .setDescription('Raison du kick')
      .setRequired(false)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.KickMembers);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const reason = interaction.options.getString('raison') ?? 'Aucune raison spécifiée';
  const member = await interaction.guild.members.fetch(user.id).catch(() => null);
  if (!member) {
    await interaction.reply({ content: 'Utilisateur introuvable.', ephemeral: true });
    return;
  }
  try {
    await member.kick(reason);
    await interaction.reply({ content: `✅ ${user.tag} expulsé. Raison : ${reason}` });
  } catch {
    await interaction.reply({ content: `❌ Impossible d'expulser ${user.tag}.`, ephemeral: true });
  }
}
