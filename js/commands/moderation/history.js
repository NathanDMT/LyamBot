import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('history')
  .setDescription('Historique des sanctions')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ModerateMembers);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  await interaction.reply({ content: `Aucun historique pour ${user.tag}.`, ephemeral: true });
}
