<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\DatabaseBackup;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Taking and managing database dumps. Restricted to administrators by the
 * backup_database permission on every route.
 */
class BackupController extends Controller
{
    public function index()
    {
        return view('admin.backup');
    }

    public function list()
    {
        $backup = DatabaseBackup::forDefaultConnection();

        return response()->json([
            'success' => true,
            'data' => [
                'backups' => $this->present($backup->all()),
                'total_bytes' => $backup->totalBytes(),
                'keep' => (int) config('backup.keep', 20),
            ],
        ]);
    }

    public function store()
    {
        try {
            $result = DatabaseBackup::forDefaultConnection()->create();
        } catch (Throwable $e) {
            // The message comes from our own service and has already been
            // stripped of anything sensitive.
            return response()->json([
                'success' => false,
                'message' => 'Backup failed. ' . $e->getMessage(),
            ], 500);
        }

        ActivityLog::logAction(
            auth()->id(),
            'database_backup_created',
            auth()->user()->full_name . " created database backup {$result['filename']} ("
                . $this->humanBytes($result['bytes']) . ')'
        );

        return response()->json([
            'success' => true,
            'message' => 'Backup created.',
            'data' => $this->present([$result])[0],
        ]);
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = DatabaseBackup::forDefaultConnection()->pathFor($filename);

        abort_if($path === null, 404, 'That backup no longer exists.');

        ActivityLog::logAction(
            auth()->id(),
            'database_backup_downloaded',
            auth()->user()->full_name . " downloaded database backup {$filename}"
        );

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function destroy(string $filename)
    {
        if (!DatabaseBackup::forDefaultConnection()->delete($filename)) {
            return response()->json([
                'success' => false,
                'message' => 'That backup no longer exists.',
            ], 404);
        }

        ActivityLog::logAction(
            auth()->id(),
            'database_backup_deleted',
            auth()->user()->full_name . " deleted database backup {$filename}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Backup deleted.',
        ]);
    }

    /** @param array<int,array{filename:string,bytes:int,created_at:\Illuminate\Support\Carbon}> $backups */
    private function present(array $backups): array
    {
        return array_map(fn (array $backup) => [
            'filename' => $backup['filename'],
            'bytes' => $backup['bytes'],
            'size' => $this->humanBytes($backup['bytes']),
            'created_at' => $backup['created_at']->toIso8601String(),
            'created_label' => $backup['created_at']->format('d M Y, g:i A'),
            'age' => $backup['created_at']->diffForHumans(),
        ], $backups);
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }

        return $bytes . ' B';
    }
}
