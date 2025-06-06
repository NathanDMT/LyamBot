import { SlashCommandBuilder, PermissionFlagsBits } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const db = new Database(path.join(__dirname, '../xp/xp.sqlite'));

export const data = new SlashCommandBuilder()
  .setName('setxp')
  .setDescription('Définit la quantité d\'XP d\'un utilisateur')
  .addUserOption(o =>
    o.setName('utilisateur').setDescription('Utilisateur ciblé').setRequired(true)
  )
  .addIntegerOption(o =>
    o.setName('montant').setDescription('Nouvelle valeur').setRequired(true)
  )
  .setDefaultMemberPermissions(PermissionFlagsBits.ManageGuild);

export async function execute(interaction) {
  const user = interaction.options.getUser('utilisateur');
  const montant = interaction.options.getInteger('montant');
  const level = Math.floor(Math.sqrt(montant / 100));
  const row = db.prepare('SELECT 1 FROM users_activity WHERE user_id = ? AND guild_id = ?')
    .get(user.id, interaction.guildId);
  if (row) {
    db.prepare('UPDATE users_activity SET xp = ?, level = ?, username = ? WHERE user_id = ? AND guild_id = ?')
      .run(montant, level, user.username, user.id, interaction.guildId);
  } else {
    db.prepare('INSERT INTO users_activity (user_id, guild_id, username, xp, level) VALUES (?, ?, ?, ?, ?)')
      .run(user.id, interaction.guildId, user.username, montant, level);
  }
  await interaction.reply({ content: `XP de ${user.tag} défini à ${montant}` });
}
