import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('unban')
  .setDescription('Débannir un utilisateur via son ID')
  .addStringOption(o =>
    o.setName('user_id').setDescription('ID de l\'utilisateur').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.BanMembers);

export async function execute(interaction) {
  const id = interaction.options.getString('user_id');
  try {
    await interaction.guild.members.unban(id);
    await interaction.reply({ content: `Utilisateur ${id} débanni.` });
  } catch {
    await interaction.reply({ content: `Impossible de débannir ${id}.`, ephemeral: true });
  }
}
