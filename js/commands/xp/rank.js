import { SlashCommandBuilder } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const db = new Database(path.join(__dirname, '../xp.sqlite'));

export const data = new SlashCommandBuilder()
  .setName('rank')
  .setDescription('Affiche votre niveau et votre XP');

export async function execute(interaction) {
  const row = db.prepare(
    'SELECT level, xp FROM users_activity WHERE user_id = ? AND guild_id = ?'
  ).get(interaction.user.id, interaction.guildId);
  if (!row) {
    await interaction.reply({ content: "Vous n'avez pas encore d'XP.", ephemeral: true });
    return;
  }
  await interaction.reply({ content: `Niveau ${row.level} - ${row.xp} XP` });
}
