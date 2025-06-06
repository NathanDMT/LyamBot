import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('warnlist')
  .setDescription('Liste des avertissements d\'un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur').setRequired(true)
  );

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  await interaction.reply({ content: `Aucun avertissement pour ${user.tag}.`, ephemeral: true });
}
