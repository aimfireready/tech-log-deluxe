<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * An external-content FTS5 index over entries.title and entries.body_text. The triggers keep it
     * in step with every insert, update and hard delete; soft-deleted rows stay indexed and are
     * filtered out by joining back to entries.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE entries_fts USING fts5(
                title,
                body_text,
                content='entries',
                content_rowid='id',
                tokenize='porter unicode61'
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER entries_fts_after_insert AFTER INSERT ON entries BEGIN
                INSERT INTO entries_fts(rowid, title, body_text) VALUES (new.id, new.title, new.body_text);
            END
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER entries_fts_after_delete AFTER DELETE ON entries BEGIN
                INSERT INTO entries_fts(entries_fts, rowid, title, body_text) VALUES ('delete', old.id, old.title, old.body_text);
            END
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER entries_fts_after_update AFTER UPDATE OF title, body_text ON entries BEGIN
                INSERT INTO entries_fts(entries_fts, rowid, title, body_text) VALUES ('delete', old.id, old.title, old.body_text);
                INSERT INTO entries_fts(rowid, title, body_text) VALUES (new.id, new.title, new.body_text);
            END
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS entries_fts_after_update');
        DB::statement('DROP TRIGGER IF EXISTS entries_fts_after_delete');
        DB::statement('DROP TRIGGER IF EXISTS entries_fts_after_insert');
        DB::statement('DROP TABLE IF EXISTS entries_fts');
    }
};
