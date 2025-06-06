import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const db = new Database(path.join(__dirname, '../xp/xp.sqlite'));

export const data = new SlashCommandBuilder()
  .setName('resetxp')
  .setDescription('Réinitialise l\'XP d\'un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur ciblé').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ManageGuild);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  db.prepare('DELETE FROM users_activity WHERE user_id = ? AND guild_id = ?')
    .run(user.id, interaction.guildId);
  await interaction.reply({ content: `XP de ${user.tag} réinitialisé.` });
}
