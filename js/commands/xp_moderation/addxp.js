import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const db = new Database(path.join(__dirname, '../xp/xp.sqlite'));

export const data = new SlashCommandBuilder()
  .setName('addxp')
  .setDescription('Ajoute de l\'XP à un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur ciblé').setRequired(true)
  )
  .addIntegerOption(o =>
    o.setName('montant').setDescription('Montant d\'XP').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ManageGuild);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const montant = interaction.options.getInteger('montant');
  const row = db.prepare('SELECT xp, level FROM users_activity WHERE user_id = ? AND guild_id = ?')
    .get(user.id, interaction.guildId);
  const xp = (row?.xp ?? 0) + montant;
  const level = Math.floor(Math.sqrt(xp / 100));
  if (row) {
    db.prepare('UPDATE users_activity SET xp = ?, level = ?, username = ? WHERE user_id = ? AND guild_id = ?')
      .run(xp, level, user.username, user.id, interaction.guildId);
  } else {
    db.prepare('INSERT INTO users_activity (user_id, guild_id, username, xp, level) VALUES (?, ?, ?, ?, ?)')
      .run(user.id, interaction.guildId, user.username, xp, level);
  }
  await interaction.reply({ content: `${montant} XP ajouté à ${user.tag}` });
}
