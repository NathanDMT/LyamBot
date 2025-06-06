import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('setannoncechannel')
  .setDescription("Définit le salon d'annonces")
  .addChannelOption(o =>
    o.setName('salon').setDescription('Salon cible').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ManageGuild);

export async function execute(interaction) {
  const channel = interaction.options.getChannel('salon');
  await interaction.reply({ content: `Salon d'annonces défini sur ${channel}`, ephemeral: true });
}
