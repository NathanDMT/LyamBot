import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('unmute')
  .setDescription('Rend la parole à un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur à unmute').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ModerateMembers);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  await interaction.reply({ content: `${user.tag} peut de nouveau parler.` });
}
