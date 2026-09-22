<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Takes a mysqldump of the configured database and manages the files it
 * leaves behind.
 *
 * The connection password is handed to mysqldump through a temporary option
 * file rather than an argument, because arguments are readable by anyone who
 * can list processes on the machine. The option file is removed in a finally
 * block so a failed dump does not leave credentials on disk.
 */
class DatabaseBackup
{
    /**
     * Names this class will produce and accept. Download and delete both run
     * a candidate against this before touching the filesystem, so a crafted
     * name cannot walk out of the backup directory.
     */
    private const FILENAME_PATTERN = '/^raney-\d{4}-\d{2}-\d{2}-\d{6}\.sql$/';

    public function __construct(private readonly array $connection)
    {
    }

    public static function forDefaultConnection(): self
    {
        $name = config('database.default');
        $connection = config("database.connections.{$name}");

        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('Backups are only supported on MySQL and MariaDB connections.');
        }

        return new self($connection);
    }

    /** @return array{filename:string,bytes:int,created_at:Carbon} */
    public function create(): array
    {
        $directory = $this->directory();
        $filename = 'raney-' . now()->format('Y-m-d-His') . '.sql';
        $target = $directory . DIRECTORY_SEPARATOR . $filename;

        $optionFile = $this->writeOptionFile();
        $handle = fopen($target, 'wb');

        if ($handle === false) {
            @unlink($optionFile);
            throw new RuntimeException('Could not open the backup file for writing.');
        }

        try {
            $process = new Process([
                $this->binary(),
                '--defaults-extra-file=' . $optionFile,
                '--default-character-set=utf8mb4',
                '--single-transaction',
                '--quick',
                '--routines',
                '--events',
                '--add-drop-table',
                $this->connection['database'],
            ], null, $this->systemEnvironment());

            $process->setTimeout(config('backup.timeout', 300));

            // Streamed to disk rather than collected in memory, so the dump
            // size is bounded by the disk and not by PHP's memory limit.
            $process->run(function (string $type, string $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });

            fclose($handle);
            $handle = null;

            if (!$process->isSuccessful()) {
                @unlink($target);

                throw new RuntimeException($this->readableError($process->getErrorOutput()));
            }

            if (filesize($target) === 0) {
                @unlink($target);

                throw new RuntimeException('mysqldump produced an empty file.');
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            @unlink($optionFile);
        }

        $this->prune();

        clearstatcache(true, $target);

        return [
            'filename' => $filename,
            'bytes' => filesize($target),
            'created_at' => Carbon::createFromTimestamp(filemtime($target)),
        ];
    }

    /**
     * Newest first.
     *
     * @return array<int,array{filename:string,bytes:int,created_at:Carbon}>
     */
    public function all(): array
    {
        $files = glob($this->directory() . DIRECTORY_SEPARATOR . 'raney-*.sql') ?: [];

        $backups = array_map(fn (string $path) => [
            'filename' => basename($path),
            'bytes' => filesize($path),
            'created_at' => Carbon::createFromTimestamp(filemtime($path)),
        ], $files);

        // The timestamp is inside the name, so sorting by name sorts by age.
        usort($backups, fn ($a, $b) => strcmp($b['filename'], $a['filename']));

        return $backups;
    }

    /** The full path, or null when the name is not one of ours. */
    public function pathFor(string $filename): ?string
    {
        if (!preg_match(self::FILENAME_PATTERN, $filename)) {
            return null;
        }

        $path = $this->directory() . DIRECTORY_SEPARATOR . $filename;

        return is_file($path) ? $path : null;
    }

    public function delete(string $filename): bool
    {
        $path = $this->pathFor($filename);

        return $path !== null && @unlink($path);
    }

    public function totalBytes(): int
    {
        return array_sum(array_column($this->all(), 'bytes'));
    }

    /** Drops the oldest dumps once there are more than the configured limit. */
    private function prune(): void
    {
        $keep = max(1, (int) config('backup.keep', 20));
        $backups = $this->all();

        foreach (array_slice($backups, $keep) as $old) {
            @unlink($this->directory() . DIRECTORY_SEPARATOR . $old['filename']);
        }
    }

    private function directory(): string
    {
        $directory = config('backup.path');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the backup directory.');
        }

        return $directory;
    }

    private function binary(): string
    {
        $configured = config('backup.mysqldump');

        if (filled($configured)) {
            if (!is_file($configured)) {
                throw new RuntimeException('MYSQLDUMP_PATH points at a file that does not exist.');
            }

            return $configured;
        }

        foreach (config('backup.mysqldump_candidates', []) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Last resort: trust PATH. If it is not there either, the process
        // fails and the error output explains why.
        return 'mysqldump';
    }

    /**
     * The handful of variables mysqldump needs from the operating system.
     *
     * Symfony derives a child process's default environment from the
     * superglobals, and under `php artisan serve` those hold the request
     * variables rather than the system ones. mysqldump then starts without
     * SystemRoot, cannot initialise Winsock, and fails with error 10106 --
     * but only when run through the web server, never from the console.
     * Naming them explicitly makes a backup behave the same either way.
     *
     * @return array<string,string>
     */
    private function systemEnvironment(): array
    {
        $names = ['SystemRoot', 'SYSTEMROOT', 'WINDIR', 'COMSPEC', 'PATH', 'Path', 'TEMP', 'TMP', 'HOME'];
        $environment = [];

        foreach ($names as $name) {
            $value = getenv($name);

            if ($value !== false && $value !== '') {
                $environment[$name] = $value;
            }
        }

        return $environment;
    }

    /**
     * A [client] option file holding the credentials, so they never appear
     * in the process arguments.
     */
    private function writeOptionFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'raney-dump-');

        if ($path === false) {
            throw new RuntimeException('Could not create a temporary credentials file.');
        }

        $contents = "[client]\n"
            . 'user=' . $this->quote((string) ($this->connection['username'] ?? '')) . "\n"
            . 'password=' . $this->quote((string) ($this->connection['password'] ?? '')) . "\n"
            . 'host=' . $this->quote((string) ($this->connection['host'] ?? '127.0.0.1')) . "\n"
            . 'port=' . (int) ($this->connection['port'] ?? 3306) . "\n";

        file_put_contents($path, $contents);
        @chmod($path, 0600);

        return $path;
    }

    /** Option-file values are double quoted, so both escapes must be doubled. */
    private function quote(string $value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    /**
     * mysqldump's stderr is several lines of context around one useful
     * sentence, and it may name the option file. Only the first line is worth
     * showing, and the path is masked out of it.
     */
    private function readableError(string $stderr): string
    {
        $first = trim(strtok(trim($stderr), "\n") ?: '');

        if ($first === '') {
            return 'mysqldump failed without reporting a reason. Check that MySQL is running.';
        }

        return preg_replace('/--defaults-extra-file=\S+/', '--defaults-extra-file=***', $first);
    }
}
