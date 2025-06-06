import { SlashCommandBuilder } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const db = new Database(path.join(__dirname, '../xp.sqlite'));

export const data = new SlashCommandBuilder()
  .setName('profile')
  .setDescription('Affiche le profil XP d\'un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur ciblé').setRequired(true)
  );

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const row = db.prepare(
    'SELECT level, xp FROM users_activity WHERE user_id = ? AND guild_id = ?'
  ).get(user.id, interaction.guildId);
  if (!row) {
    await interaction.reply({ content: `${user.tag} n\'a pas encore d\'XP.`, ephemeral: true });
    return;
  }
  await interaction.reply({ content: `${user.tag} est niveau ${row.level} avec ${row.xp} XP` });
}
