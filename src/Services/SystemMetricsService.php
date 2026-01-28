<?php

namespace App\Services;

class SystemMetricsService {

    public function getMetrics() {
        return [
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'uptime' => $this->getUptime(),
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
    }

    private function getCpuUsage() {
        // Windows friendly CPU check
        if (stristr(PHP_OS, 'win')) {
            $cmd = "wmic cpu get loadpercentage /all";
            @exec($cmd, $output);
            if ($output) {
                foreach ($output as $line) {
                    if ($line && preg_match("/^[0-9]+\$/", $line)) {
                        return (int)$line;
                    }
                }
            }
        } else {
            // Linux
            $load = sys_getloadavg();
            return (int)($load[0] * 100); // Rough approximation for load
        }
        return 0;
    }

    private function getMemoryUsage() {
        if (stristr(PHP_OS, 'win')) {
            // Get total physical memory
            $cmd = "wmic ComputerSystem get TotalPhysicalMemory";
            @exec($cmd, $outputTotal);
            // Get free physical memory
            $cmd = "wmic OS get FreePhysicalMemory";
            @exec($cmd, $outputFree);
            
            $total = 0;
            $free = 0;
            
            foreach ($outputTotal as $line) if (ctype_digit(trim($line))) $total = trim($line);
            foreach ($outputFree as $line) if (ctype_digit(trim($line))) $free = trim($line) * 1024; // KB to Bytes

            if ($total > 0) {
                $used = $total - $free;
                return [
                    'used' => $this->formatBytes($used),
                    'total' => $this->formatBytes($total),
                    'percentage' => round(($used / $total) * 100, 2)
                ];
            }
        }
        
        // Fallback or Linux implementation could go here
        return [
            'used' => $this->formatBytes(memory_get_usage(true)),
            'total' => 'N/A',
            'percentage' => 0
        ];
    }

    private function getDiskUsage() {
        $path = '.';
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;

        return [
            'used' => $this->formatBytes($used),
            'total' => $this->formatBytes($total),
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }

    private function getUptime() {
        // Windows uptime
        if (stristr(PHP_OS, 'win')) {
             // System Boot Time
             $cmd = "wmic os get lastbootuptime";
             @exec($cmd, $output);
             $bootTime = '';
             foreach($output as $line) {
                 if (strpos($line, '.') !== false) {
                     $bootTime = $line;
                     break;
                 }
             }
             // Format: 20231208090000.000000+000
             if ($bootTime) {
                $year = substr($bootTime, 0, 4);
                $month = substr($bootTime, 4, 2);
                $day = substr($bootTime, 6, 2);
                $hour = substr($bootTime, 8, 2);
                $min = substr($bootTime, 10, 2);
                $sec = substr($bootTime, 12, 2);
                $timestamp = strtotime("$year-$month-$day $hour:$min:$sec");
                return $this->formatDuration(time() - $timestamp);
             }
        }
        return 'N/A';
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function formatDuration($seconds) {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a days, %h hours, %i min');
    }
}
