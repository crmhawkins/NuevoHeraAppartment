<?php

namespace App\Http\Controllers;

use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogsController extends Controller
{
    protected $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Display logs dashboard
     */
    public function index(Request $request)
    {
        $days = $request->get('days', 7);
        $stats = $this->loggingService->getLogStatistics($days);
        
        return view('admin.logs.index', compact('stats', 'days'));
    }

    /**
     * Display log files
     */
    public function files(Request $request)
    {
        $logPath = storage_path('logs');
        $files = [];
        
        if (File::exists($logPath)) {
            $logFiles = File::glob($logPath . '/*.log');
            
            foreach ($logFiles as $file) {
                $files[] = [
                    'name' => basename($file),
                    'size' => File::size($file),
                    'modified' => Carbon::createFromTimestamp(File::lastModified($file)),
                    'path' => $file
                ];
            }
            
            // Sort by modification time (newest first)
            usort($files, function($a, $b) {
                return $b['modified']->timestamp - $a['modified']->timestamp;
            });
        }
        
        return view('admin.logs.files', compact('files'));
    }

    /**
     * View specific log file
     */
    public function view(Request $request, $filename)
    {
        $logPath = storage_path('logs/' . $filename);
        
        if (!File::exists($logPath)) {
            abort(404, 'Log file not found');
        }
        
        $lines = $request->get('lines', 100);
        $offset = $request->get('offset', 0);
        
        $content = $this->getLogFileContent($logPath, $lines, $offset);
        $totalLines = $this->getLogFileLineCount($logPath);
        
        return view('admin.logs.view', compact('filename', 'content', 'totalLines', 'lines', 'offset'));
    }

    /**
     * Download log file
     */
    public function download($filename)
    {
        $logPath = storage_path('logs/' . $filename);
        
        if (!File::exists($logPath)) {
            abort(404, 'Log file not found');
        }
        
        return response()->download($logPath);
    }

    /**
     * Clear log files
     */
    public function clear(Request $request)
    {
        $this->authorize('admin');
        
        $logPath = storage_path('logs');
        $deletedFiles = 0;
        
        if (File::exists($logPath)) {
            $files = File::glob($logPath . '/*.log');
            
            foreach ($files as $file) {
                File::delete($file);
                $deletedFiles++;
            }
        }
        
        $this->logSystemEvent('LOGS_CLEARED', [
            'deleted_files' => $deletedFiles,
            'cleared_by' => auth()->user()->name
        ]);
        
        return redirect()->back()->with('success', "Se han eliminado {$deletedFiles} archivos de log.");
    }

    /**
     * Get log file content with pagination
     */
    private function getLogFileContent($filePath, $lines = 100, $offset = 0)
    {
        $file = new \SplFileObject($filePath);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key() + 1;
        
        $startLine = max(0, $totalLines - $lines - $offset);
        $endLine = min($totalLines, $startLine + $lines);
        
        $content = [];
        $file->seek($startLine);
        
        for ($i = $startLine; $i < $endLine; $i++) {
            $line = $file->current();
            if ($line !== false) {
                $content[] = [
                    'line_number' => $i + 1,
                    'content' => rtrim($line),
                    'timestamp' => $this->extractTimestamp($line)
                ];
            }
            $file->next();
        }
        
        return array_reverse($content);
    }

    /**
     * Get total line count of log file
     */
    private function getLogFileLineCount($filePath)
    {
        $file = new \SplFileObject($filePath);
        $file->seek(PHP_INT_MAX);
        return $file->key() + 1;
    }

    /**
     * Extract timestamp from log line
     */
    private function extractTimestamp($line)
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Search logs
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $level = $request->get('level');
        $date = $request->get('date');
        $user = $request->get('user');
        
        $logPath = storage_path('logs');
        $results = [];
        
        if (File::exists($logPath)) {
            $files = File::glob($logPath . '/laravel-*.log');
            
            foreach ($files as $file) {
                if ($date && !strpos($file, $date)) {
                    continue;
                }
                
                $content = File::get($file);
                $lines = explode("\n", $content);
                
                foreach ($lines as $lineNumber => $line) {
                    if ($this->matchesSearchCriteria($line, $query, $level, $user)) {
                        $results[] = [
                            'file' => basename($file),
                            'line_number' => $lineNumber + 1,
                            'content' => $line,
                            'timestamp' => $this->extractTimestamp($line)
                        ];
                    }
                }
            }
        }
        
        return view('admin.logs.search', compact('results', 'query', 'level', 'date', 'user'));
    }

    /**
     * Check if log line matches search criteria
     */
    private function matchesSearchCriteria($line, $query, $level, $user)
    {
        $matches = true;
        
        if ($query && stripos($line, $query) === false) {
            $matches = false;
        }
        
        if ($level && stripos($line, $level) === false) {
            $matches = false;
        }
        
        if ($user && stripos($line, $user) === false) {
            $matches = false;
        }
        
        return $matches;
    }
}
