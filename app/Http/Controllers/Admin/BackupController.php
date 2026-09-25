<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
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

    /**
     * Put the database back to how a backup found it.
     *
     * The page used to end with a mysql command line and the words "done
     * deliberately from the command line rather than from this page", which
     * is only true for somebody who has a command line and knows what to
     * type. For everybody else it meant the backups were unusable: taking
     * them was a button, and using one was somebody else's job.
     *
     * Three things make the button safe enough to offer. Only a file this
     * system wrote can be chosen, so there is nothing to upload and nothing
     * to smuggle in. A copy of the database as it stands is taken first, so
     * the restore itself can be undone. And the word RESTORE has to be typed,
     * because this is the one control here that destroys work.
     */
    public function restore(Request $request)
    {
        $request->validate([
            'filename' => 'required|string|max:120',
            'confirm' => 'required|string',
        ]);

        if (strtoupper(trim($request->input('confirm'))) !== 'RESTORE') {
            return response()->json([
                'success' => false,
                'message' => 'Type RESTORE to confirm.',
            ], 422);
        }

        $backup = DatabaseBackup::forDefaultConnection();
        $filename = $request->input('filename');

        if ($backup->pathFor($filename) === null) {
            return response()->json([
                'success' => false,
                'message' => 'That backup could not be found.',
            ], 404);
        }

        $user = auth()->user();

        // Before anything is dropped. If the restore goes wrong, or restores
        // the wrong night, this is the way back.
        try {
            $safety = $backup->create();
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing was changed: a safety copy could not be taken first. ' . $e->getMessage(),
            ], 500);
        }

        try {
            $backup->restore($filename);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'The restore failed. ' . $e->getMessage()
                    . ' Your data is as it was, and a copy of it is saved as ' . $safety['filename'] . '.',
            ], 500);
        }

        /*
         * The users table has just been replaced, so the session id stored
         * against this account is whatever it was on the night of the backup
         * and no longer matches the browser that pressed the button. Without
         * this, restoring signs the administrator out on their next click.
         */
        $restored = User::where('email', $user->email)->first();

        if ($restored && $request->hasSession()) {
            $restored->forceFill(['current_session_id' => $request->session()->getId()])->save();
        }

        ActivityLog::logAction(
            $restored?->user_id ?? $user->user_id,
            'database_restored',
            $user->full_name . " restored the database from {$filename}, after saving the previous state as {$safety['filename']}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Restored from ' . $filename . '. The database as it was a moment ago is saved as ' . $safety['filename'] . '.',
            'data' => ['safety_copy' => $safety['filename']],
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
