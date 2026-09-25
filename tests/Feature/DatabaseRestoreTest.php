<?php

namespace Tests\Feature;

use App\Services\DatabaseBackup;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Taking a backup and reading it back in.
 *
 * Deliberately not run against the application's own database, test or
 * otherwise: a restore drops every table, and a test that does that to the
 * database the rest of the suite is using leaves nothing behind it. A scratch
 * database is made here, used, and dropped.
 *
 * It does shell out to the real mysqldump and mysql, because that is the part
 * worth proving -- the file has to be one the client can actually read back.
 * Where those binaries are missing the test says so rather than failing.
 */
class DatabaseRestoreTest extends TestCase
{
    private string $scratch = 'raney_restore_probe';
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('app/backups-test');
        config(['backup.path' => $this->directory]);

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$this->scratch}` CHARACTER SET utf8mb4");
    }

    protected function tearDown(): void
    {
        DB::statement("DROP DATABASE IF EXISTS `{$this->scratch}`");

        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);

        parent::tearDown();
    }

    private function backup(): DatabaseBackup
    {
        $connection = config('database.connections.' . config('database.default'));
        $connection['database'] = $this->scratch;

        return new DatabaseBackup($connection);
    }

    /** Runs a statement against the scratch database, not the app's. */
    private function onScratch(string $sql): void
    {
        config(['database.connections.scratch' => array_merge(
            config('database.connections.' . config('database.default')),
            ['database' => $this->scratch]
        )]);

        DB::connection('scratch')->statement($sql);
    }

    private function rowsOnScratch(string $sql): array
    {
        return DB::connection('scratch')->select($sql);
    }

    #[Test]
    public function a_backup_can_be_taken_and_read_back_in(): void
    {
        $backup = $this->backup();

        try {
            $this->onScratch('CREATE TABLE ledger (id INT PRIMARY KEY, note VARCHAR(50))');
        } catch (\Throwable $e) {
            $this->markTestSkipped('No database to work against: ' . $e->getMessage());
        }

        $this->onScratch("INSERT INTO ledger VALUES (1, 'written before the backup')");

        try {
            $result = $backup->create();
        } catch (\Throwable $e) {
            $this->markTestSkipped('mysqldump is not available here: ' . $e->getMessage());
        }

        $this->assertGreaterThan(0, $result['bytes'], 'An empty dump restores an empty database.');

        // Everything after the backup, which a restore must undo.
        $this->onScratch("INSERT INTO ledger VALUES (2, 'written after the backup')");
        $this->onScratch('CREATE TABLE stray (id INT PRIMARY KEY)');

        $this->assertCount(2, $this->rowsOnScratch('SELECT * FROM ledger'));

        try {
            $backup->restore($result['filename']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('The mysql client is not available here: ' . $e->getMessage());
        }

        $rows = $this->rowsOnScratch('SELECT * FROM ledger ORDER BY id');

        $this->assertCount(1, $rows, 'The row added after the backup should be gone.');
        $this->assertSame('written before the backup', $rows[0]->note);

        // A table created after the backup is not in the dump, so it survives.
        // Worth knowing rather than assuming either way.
        $this->assertNotEmpty(
            $this->rowsOnScratch("SHOW TABLES LIKE 'stray'"),
            'A dump drops and recreates what it contains; it does not drop what it has never heard of.'
        );
    }

    #[Test]
    public function a_file_outside_the_backup_folder_cannot_be_restored(): void
    {
        $backup = $this->backup();

        foreach (['../../.env', 'C:\\Windows\\win.ini', '/etc/passwd', 'not-a-backup.sql'] as $attempt) {
            $this->assertNull(
                $backup->pathFor($attempt),
                "pathFor accepted {$attempt}, which is how a restore becomes a way of running someone else's file."
            );
        }
    }
}
