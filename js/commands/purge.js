import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('purge')
  .setDescription('Supprime un certain nombre de messages')
  .addIntegerOption(o =>
    o.setName('nombre')
      .setDescription('Nombre de messages à supprimer (1-100)')
      .setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ManageMessages);

export async function execute(interaction) {
  const count = interaction.options.getInteger('nombre');
  if (count < 1 || count > 100) {
    await interaction.reply({ content: 'Le nombre doit être entre 1 et 100.', ephemeral: true });
    return;
  }
  try {
    const deleted = await interaction.channel.bulkDelete(count, true);
    await interaction.reply({ content: `🧹 ${deleted.size} message(s) supprimé(s).`, ephemeral: true });
  } catch {
    await interaction.reply({ content: '❌ Impossible de supprimer les messages.', ephemeral: true });
  }
}
