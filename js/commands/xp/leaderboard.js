import { SlashCommandBuilder } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const db = new Database(path.join(__dirname, '../xp.sqlite'));

export const data = new SlashCommandBuilder()
  .setName('leaderboard')
  .setDescription('Affiche le classement des utilisateurs');

export async function execute(interaction) {
  const rows = db.prepare(
    'SELECT user_id, username, level, xp FROM users_activity WHERE guild_id = ? ORDER BY level DESC, xp DESC LIMIT 10'
  ).all(interaction.guildId);
  if (!rows.length) {
    await interaction.reply({ content: 'Aucun utilisateur trouvé.', ephemeral: true });
    return;
  }
  const desc = rows.map((r, i) => `${i + 1}. <@${r.user_id}> - Niveau ${r.level} (${r.xp} XP)`).join('\n');
  await interaction.reply({ embeds: [{ title: 'Classement XP', description: desc, color: 0xf1c40f }] });
}
