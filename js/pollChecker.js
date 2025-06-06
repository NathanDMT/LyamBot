import { Events } from 'discord.js';
import Database from 'better-sqlite3';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const db = new Database(path.join(__dirname, 'xp/xp.sqlite'));

db.prepare(`CREATE TABLE IF NOT EXISTS polls (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  message_id TEXT,
  channel_id TEXT,
  question TEXT,
  end_at INTEGER,
  closed INTEGER DEFAULT 0
)`).run();

export default function init(client) {
  setInterval(async () => {
    const now = Date.now();
    const polls = db.prepare('SELECT * FROM polls WHERE end_at <= ? AND closed = 0').all(now);
    for (const poll of polls) {
      const channel = await client.channels.fetch(poll.channel_id).catch(() => null);
      if (!channel) continue;
      const message = await channel.messages.fetch(poll.message_id).catch(() => null);
      if (!message) {
        db.prepare('UPDATE polls SET closed = 1 WHERE id = ?').run(poll.id);
        continue;
      }
      const yes = message.reactions.cache.get('✅')?.count ?? 1;
      const no = message.reactions.cache.get('❌')?.count ?? 1;
      await channel.send(`Résultat du sondage : **${poll.question}**\n✅ ${yes - 1} vote(s)\n❌ ${no - 1} vote(s)`);
      await message.delete().catch(() => {});
      db.prepare('UPDATE polls SET closed = 1 WHERE id = ?').run(poll.id);
    }
  }, 30000);
}
