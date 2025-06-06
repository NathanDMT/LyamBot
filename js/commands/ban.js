import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('ban')
  .setDescription('Bannir un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur')
      .setDescription('Utilisateur à bannir')
      .setRequired(true)
  )
  .addStringOption(o =>
    o.setName('raison')
      .setDescription('Raison du bannissement')
      .setRequired(false)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.BanMembers);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const reason = interaction.options.getString('raison') ?? 'Aucune raison spécifiée';
  try {
    await interaction.guild.members.ban(user.id, { reason });
    await interaction.reply({ content: `✅ ${user.tag} banni. Raison : ${reason}` });
  } catch {
    await interaction.reply({ content: `❌ Impossible de bannir ${user.tag}.`, ephemeral: true });
  }
}
