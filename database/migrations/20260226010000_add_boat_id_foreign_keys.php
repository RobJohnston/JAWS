<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBoatIdForeignKeys extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('PRAGMA foreign_keys = OFF');

        $this->execute(
            "CREATE TABLE crew_whitelist_new (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                crew_id INTEGER NOT NULL,
                boat_key VARCHAR(255) NOT NULL,
                boat_id INTEGER NULL,
                created_at DATETIME_TEXT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (crew_id) REFERENCES crews (id) ON DELETE CASCADE ON UPDATE NO ACTION,
                FOREIGN KEY (boat_id) REFERENCES boats (id) ON DELETE SET NULL ON UPDATE NO ACTION
            )"
        );

        $this->execute(
            "INSERT INTO crew_whitelist_new (id, crew_id, boat_key, boat_id, created_at)
            SELECT cw.id, cw.crew_id, cw.boat_key, b.id, cw.created_at
            FROM crew_whitelist cw
            LEFT JOIN boats b ON b.key = cw.boat_key"
        );

        $this->execute('DROP TABLE crew_whitelist');
        $this->execute('ALTER TABLE crew_whitelist_new RENAME TO crew_whitelist');

        $this->execute('CREATE UNIQUE INDEX crew_whitelist_crew_id_boat_key_index ON crew_whitelist (crew_id, boat_key)');
        $this->execute('CREATE INDEX idx_crew_whitelist_crew ON crew_whitelist (crew_id)');
        $this->execute('CREATE INDEX idx_crew_whitelist_boat ON crew_whitelist (boat_key)');
        $this->execute('CREATE INDEX idx_crew_whitelist_boat_id ON crew_whitelist (boat_id)');

        $this->execute(
            "CREATE TABLE crew_history_new (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                crew_id INTEGER NOT NULL,
                event_id VARCHAR(255) NOT NULL,
                boat_key VARCHAR(255) NULL DEFAULT '',
                boat_id INTEGER NULL,
                FOREIGN KEY (crew_id) REFERENCES crews (id) ON DELETE CASCADE ON UPDATE NO ACTION,
                FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE ON UPDATE NO ACTION,
                FOREIGN KEY (boat_id) REFERENCES boats (id) ON DELETE SET NULL ON UPDATE NO ACTION
            )"
        );

        $this->execute(
            "INSERT INTO crew_history_new (id, crew_id, event_id, boat_key, boat_id)
            SELECT ch.id,
                   ch.crew_id,
                   ch.event_id,
                   ch.boat_key,
                   CASE WHEN ch.boat_key = '' THEN NULL ELSE b.id END AS boat_id
            FROM crew_history ch
            LEFT JOIN boats b ON b.key = ch.boat_key"
        );

        $this->execute('DROP TABLE crew_history');
        $this->execute('ALTER TABLE crew_history_new RENAME TO crew_history');

        $this->execute('CREATE UNIQUE INDEX crew_history_crew_id_event_id_index ON crew_history (crew_id, event_id)');
        $this->execute('CREATE INDEX idx_crew_history_crew ON crew_history (crew_id)');
        $this->execute('CREATE INDEX idx_crew_history_event ON crew_history (event_id)');
        $this->execute('CREATE INDEX idx_crew_history_boat ON crew_history (boat_key)');
        $this->execute('CREATE INDEX idx_crew_history_boat_id ON crew_history (boat_id)');

        $this->execute('PRAGMA foreign_keys = ON');
        $this->execute('PRAGMA foreign_key_check');
    }

    public function down(): void
    {
        $this->execute('PRAGMA foreign_keys = OFF');

        $this->execute(
            "CREATE TABLE crew_whitelist_old (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                crew_id INTEGER NOT NULL,
                boat_key VARCHAR(255) NOT NULL,
                created_at DATETIME_TEXT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (crew_id) REFERENCES crews (id) ON DELETE CASCADE ON UPDATE NO ACTION
            )"
        );

        $this->execute(
            "INSERT INTO crew_whitelist_old (id, crew_id, boat_key, created_at)
            SELECT id, crew_id, boat_key, created_at
            FROM crew_whitelist"
        );

        $this->execute('DROP TABLE crew_whitelist');
        $this->execute('ALTER TABLE crew_whitelist_old RENAME TO crew_whitelist');

        $this->execute('CREATE UNIQUE INDEX crew_whitelist_crew_id_boat_key_index ON crew_whitelist (crew_id, boat_key)');
        $this->execute('CREATE INDEX idx_crew_whitelist_crew ON crew_whitelist (crew_id)');
        $this->execute('CREATE INDEX idx_crew_whitelist_boat ON crew_whitelist (boat_key)');

        $this->execute(
            "CREATE TABLE crew_history_old (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                crew_id INTEGER NOT NULL,
                event_id VARCHAR(255) NOT NULL,
                boat_key VARCHAR(255) NULL DEFAULT '',
                FOREIGN KEY (crew_id) REFERENCES crews (id) ON DELETE CASCADE ON UPDATE NO ACTION,
                FOREIGN KEY (event_id) REFERENCES events (event_id) ON DELETE CASCADE ON UPDATE NO ACTION
            )"
        );

        $this->execute(
            "INSERT INTO crew_history_old (id, crew_id, event_id, boat_key)
            SELECT id, crew_id, event_id, boat_key
            FROM crew_history"
        );

        $this->execute('DROP TABLE crew_history');
        $this->execute('ALTER TABLE crew_history_old RENAME TO crew_history');

        $this->execute('CREATE UNIQUE INDEX crew_history_crew_id_event_id_index ON crew_history (crew_id, event_id)');
        $this->execute('CREATE INDEX idx_crew_history_crew ON crew_history (crew_id)');
        $this->execute('CREATE INDEX idx_crew_history_event ON crew_history (event_id)');
        $this->execute('CREATE INDEX idx_crew_history_boat ON crew_history (boat_key)');

        $this->execute('PRAGMA foreign_keys = ON');
        $this->execute('PRAGMA foreign_key_check');
    }
}
