import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('warn')
  .setDescription('Avertit un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur à avertir').setRequired(true)
  )
  .addStringOption(o =>
    o.setName('raison').setDescription('Raison').setRequired(false)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ModerateMembers);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const raison = interaction.options.getString('raison') ?? 'Aucune raison';
  await interaction.reply({ content: `${user.tag} a été averti : ${raison}` });
}
