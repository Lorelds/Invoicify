<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;

class BackupController extends Controller
{
    public function download()
    {
        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $dbPath = database_path('database.sqlite');
        
        if (!File::exists($dbPath)) {
            return redirect()->back()->withErrors(['backup' => 'Database file not found.']);
        }

        $downloadName = 'database_backup_' . $date . '.sqlite';

        return response()->download($dbPath, $downloadName);
    }
}
