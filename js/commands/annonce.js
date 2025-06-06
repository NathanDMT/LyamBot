import { SlashCommandBuilder } from 'discord.js';

export const data = new SlashCommandBuilder()
  .setName('annonce')
  .setDescription('Créer une annonce')
  .addStringOption(o =>
    o.setName('message')
      .setDescription("Contenu de l'annonce")
      .setRequired(true)
  );

export async function execute(interaction) {
  const content = interaction.options.getString('message');
  await interaction.reply({
    embeds: [{
      title: '📢 Annonce',
      description: content,
      color: 0x3498db,
      footer: { text: `Annonce par ${interaction.user.username}` },
      timestamp: new Date()
    }]
  });
}
