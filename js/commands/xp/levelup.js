import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('levelup-message')
  .setDescription('Active ou désactive les notifications de level-up')
  .addStringOption(o =>
    o.setName('etat')
      .setDescription('on ou off')
      .setRequired(true)
      .addChoices(
        { name: 'on', value: 'on' },
        { name: 'off', value: 'off' }
      )
  );

export async function execute(interaction) {
  const etat = interaction.options.getString('etat');
  await interaction.reply({ content: `Notifications de level-up ${etat === 'on' ? 'activées' : 'désactivées'}.`, ephemeral: true });
}
