<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class GenerateLogReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:report {--days=7 : Number of days to analyze} {--output=console : Output format (console, file)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a comprehensive log report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $output = $this->option('output');
        
        $this->info("Generating log report for the last {$days} days...");
        
        $report = $this->generateReport($days);
        
        if ($output === 'file') {
            $this->saveReportToFile($report, $days);
        } else {
            $this->displayReport($report);
        }
        
        return 0;
    }

    /**
     * Generate the log report
     */
    private function generateReport(int $days): array
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/laravel-*.log');
        
        $report = [
            'period' => "Last {$days} days",
            'generated_at' => now()->toISOString(),
            'total_requests' => 0,
            'errors' => 0,
            'warnings' => 0,
            'unique_users' => [],
            'most_accessed_routes' => [],
            'error_rate' => 0,
            'hourly_distribution' => [],
            'daily_distribution' => [],
            'top_errors' => [],
            'performance_metrics' => []
        ];
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        foreach ($files as $file) {
            if (filemtime($file) >= $cutoffDate->timestamp) {
                $this->analyzeLogFile($file, $report);
            }
        }
        
        // Calculate derived metrics
        if ($report['total_requests'] > 0) {
            $report['error_rate'] = round(($report['errors'] / $report['total_requests']) * 100, 2);
        }
        
        // Sort arrays by frequency
        arsort($report['most_accessed_routes']);
        arsort($report['top_errors']);
        arsort($report['hourly_distribution']);
        arsort($report['daily_distribution']);
        
        return $report;
    }

    /**
     * Analyze a single log file
     */
    private function analyzeLogFile(string $file, array &$report): void
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Parse log line
            $parsed = $this->parseLogLine($line);
            if (!$parsed) continue;
            
            // Count requests
            if (strpos($line, 'HTTP Request') !== false) {
                $report['total_requests']++;
                
                // Track routes
                if (isset($parsed['url'])) {
                    $route = $this->extractRoute($parsed['url']);
                    $report['most_accessed_routes'][$route] = ($report['most_accessed_routes'][$route] ?? 0) + 1;
                }
                
                // Track hourly distribution
                $hour = $parsed['timestamp']->hour ?? 0;
                $report['hourly_distribution'][$hour] = ($report['hourly_distribution'][$hour] ?? 0) + 1;
                
                // Track daily distribution
                $day = $parsed['timestamp']->format('Y-m-d') ?? '';
                $report['daily_distribution'][$day] = ($report['daily_distribution'][$day] ?? 0) + 1;
            }
            
            // Count errors and warnings
            if (strpos($line, 'ERROR') !== false) {
                $report['errors']++;
                $this->trackError($line, $report);
            } elseif (strpos($line, 'WARNING') !== false) {
                $report['warnings']++;
            }
            
            // Track unique users
            if (isset($parsed['user_id']) && $parsed['user_id']) {
                $report['unique_users'][$parsed['user_id']] = $parsed['user_name'] ?? 'Unknown';
            }
            
            // Track performance metrics
            if (isset($parsed['execution_time_ms'])) {
                $report['performance_metrics'][] = $parsed['execution_time_ms'];
            }
        }
    }

    /**
     * Parse a log line to extract structured data
     */
    private function parseLogLine(string $line): ?array
    {
        // Simple JSON log parsing - adjust based on your log format
        if (preg_match('/\{.*\}/', $line, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['timestamp'])) {
                $json['timestamp'] = Carbon::parse($json['timestamp']);
                return $json;
            }
        }
        
        return null;
    }

    /**
     * Extract route from URL
     */
    private function extractRoute(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        
        // Simplify common patterns
        if (preg_match('/\/admin\/([^\/]+)/', $path, $matches)) {
            return '/admin/' . $matches[1];
        }
        
        if (preg_match('/\/api\/([^\/]+)/', $path, $matches)) {
            return '/api/' . $matches[1];
        }
        
        return $path;
    }

    /**
     * Track error details
     */
    private function trackError(string $line, array &$report): void
    {
        // Extract error message
        if (preg_match('/"message":"([^"]+)"/', $line, $matches)) {
            $error = $matches[1];
            $report['top_errors'][$error] = ($report['top_errors'][$error] ?? 0) + 1;
        }
    }

    /**
     * Display the report in console
     */
    private function displayReport(array $report): void
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info("LOG REPORT - {$report['period']}");
        $this->info("Generated at: {$report['generated_at']}");
        $this->info(str_repeat('=', 60));
        
        // Summary
        $this->info("\n📊 SUMMARY:");
        $this->line("Total Requests: " . number_format($report['total_requests']));
        $this->line("Errors: " . number_format($report['errors']));
        $this->line("Warnings: " . number_format($report['warnings']));
        $this->line("Error Rate: {$report['error_rate']}%");
        $this->line("Unique Users: " . count($report['unique_users']));
        
        // Top routes
        $this->info("\n🛣️  TOP ROUTES:");
        $count = 0;
        foreach ($report['most_accessed_routes'] as $route => $hits) {
            if ($count++ >= 10) break;
            $this->line("  {$route}: {$hits} hits");
        }
        
        // Top errors
        if (!empty($report['top_errors'])) {
            $this->info("\n❌ TOP ERRORS:");
            $count = 0;
            foreach ($report['top_errors'] as $error => $count) {
                if ($count++ >= 5) break;
                $this->line("  {$error}: {$count} occurrences");
            }
        }
        
        // Performance
        if (!empty($report['performance_metrics'])) {
            $avgTime = array_sum($report['performance_metrics']) / count($report['performance_metrics']);
            $maxTime = max($report['performance_metrics']);
            $this->info("\n⚡ PERFORMANCE:");
            $this->line("  Average Response Time: " . round($avgTime, 2) . "ms");
            $this->line("  Max Response Time: " . round($maxTime, 2) . "ms");
        }
        
        $this->info("\n" . str_repeat('=', 60));
    }

    /**
     * Save report to file
     */
    private function saveReportToFile(array $report, int $days): void
    {
        $filename = "log_report_{$days}days_" . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);
        
        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("Report saved to: {$filepath}");
    }
}
