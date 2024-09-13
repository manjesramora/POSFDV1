<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

class ClearLogs extends Command
{
    protected $signature = 'logs:clear';
    protected $description = 'Clear log files';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        File::delete(storage_path('logs/laravel.log'));
        $this->info('Logs have been cleared!');
    }
}
