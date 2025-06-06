import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const db = new Database(path.join(__dirname, 'xp.sqlite'));

db.prepare(`CREATE TABLE IF NOT EXISTS users_activity (
  user_id TEXT NOT NULL,
  guild_id TEXT NOT NULL,
  username TEXT,
  xp INTEGER DEFAULT 0,
  level INTEGER DEFAULT 0,
  last_message_at INTEGER,
  PRIMARY KEY (user_id, guild_id)
)`).run();

function calculerNiveau(xp) {
  return Math.floor(Math.sqrt(xp / 100));
}

export default {
  handleMessage(message) {
    if (message.author.bot || !message.guild) return;
    const now = Date.now();
    const row = db.prepare('SELECT * FROM users_activity WHERE user_id = ? AND guild_id = ?').get(message.author.id, message.guild.id);
    const gainXP = Math.floor(Math.random() * 11) + 5; // 5-15
    if (row) {
      const diff = now - (row.last_message_at || 0);
      if (diff < 60000) return;
      const newXP = row.xp + gainXP;
      const newLevel = calculerNiveau(newXP);
      db.prepare('UPDATE users_activity SET xp = ?, level = ?, username = ?, last_message_at = ? WHERE user_id = ? AND guild_id = ?')
        .run(newXP, newLevel, message.author.username, now, message.author.id, message.guild.id);
      if (newLevel > row.level) {
        message.channel.send({ content: `🎉 <@${message.author.id}> passe niveau **${newLevel}** !` });
      }
    } else {
      const lvl = calculerNiveau(gainXP);
      db.prepare('INSERT INTO users_activity (user_id, guild_id, username, xp, level, last_message_at) VALUES (?, ?, ?, ?, ?, ?)')
        .run(message.author.id, message.guild.id, message.author.username, gainXP, lvl, now);
    }
  }
};
