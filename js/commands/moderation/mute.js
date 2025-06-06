import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('mute')
  .setDescription('Rend muet un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur à mute').setRequired(true)
  )
  .addIntegerOption(o =>
    o.setName('duree').setDescription('Durée en minutes').setRequired(false)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ModerateMembers);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const duree = interaction.options.getInteger('duree') ?? 0;
  await interaction.reply({ content: `${user.tag} mute ${duree ? `pendant ${duree} min` : 'indéfiniment'}` });
}
