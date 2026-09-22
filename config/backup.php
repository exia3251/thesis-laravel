<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Where dumps are written
    |--------------------------------------------------------------------------
    | Inside storage/app, which the framework's own .gitignore already excludes,
    | so a dump of live customer data can never be committed by accident.
    */
    'path' => storage_path('app/backups'),

    /*
    |--------------------------------------------------------------------------
    | mysqldump binary
    |--------------------------------------------------------------------------
    | Set MYSQLDUMP_PATH in .env to override. Left empty, the service walks the
    | candidates below and finally falls back to whatever is on PATH, so a
    | standard XAMPP install needs no configuration at all.
    */
    'mysqldump' => env('MYSQLDUMP_PATH'),

    'mysqldump_candidates' => [
        'C:\xampp\mysql\bin\mysqldump.exe',
        'C:\Program Files\MariaDB\bin\mysqldump.exe',
        'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    | Dumps of this database are a few hundred kilobytes, but they accumulate.
    | Anything past this count is pruned oldest-first after a successful run.
    */
    'keep' => (int) env('BACKUP_KEEP', 20),

    /** Seconds before a dump is considered hung and killed. */
    'timeout' => 300,

];
