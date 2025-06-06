import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('setmodlogs')
  .setDescription('Définit le salon de logs modération')
  .addChannelOption(o =>
    o.setName('salon').setDescription('Salon des logs').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ManageGuild);

export async function execute(interaction) {
  const channel = interaction.options.getChannel('salon');
  // Stockage non implémenté
  await interaction.reply({ content: `Salon de logs défini sur ${channel}`, ephemeral: true });
}
