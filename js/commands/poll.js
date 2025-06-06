import { SlashCommandBuilder } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const db = new Database(path.join(__dirname, '../xp/xp.sqlite'));

db.prepare(`CREATE TABLE IF NOT EXISTS polls (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  message_id TEXT,
  channel_id TEXT,
  question TEXT,
  end_at INTEGER,
  closed INTEGER DEFAULT 0
)`).run();

export const data = new SlashCommandBuilder()
  .setName('poll')
  .setDescription('Crée un sondage')
  .addStringOption(o =>
    o.setName('question').setDescription('Question du sondage').setRequired(true))
  .addIntegerOption(o =>
    o.setName('duree').setDescription('Durée en minutes').setRequired(true));

export async function execute(interaction) {
  const question = interaction.options.getString('question');
  const minutes = interaction.options.getInteger('duree');

  const msg = await interaction.reply({ content: `**${question}**\nDurée : ${minutes} min`, fetchReply: true });
  await msg.react('✅');
  await msg.react('❌');

  const end = Date.now() + minutes * 60 * 1000;
  db.prepare('INSERT INTO polls (message_id, channel_id, question, end_at) VALUES (?, ?, ?, ?)')
    .run(msg.id, msg.channel.id, question, end);
}
