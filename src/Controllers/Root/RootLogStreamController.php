<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Controllers\Root\BaseRootController;

class RootLogStreamController extends BaseRootController
{
    public function stream()
    {
        $this->requireRootAuth();

        $logFile = __DIR__ . '/../../../../storage/logs/audit.log'; // Adjust path if needed
        if (!file_exists($logFile)) {
            // Create dummy if not exists for demo
            if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] SYSTEM.INFO: Log stream initialized.\n");
        }

        // Get last N lines
        $lines = $this->tailCustom($logFile, 50);

        return $this->successResponse([
            'logs' => $lines
        ]);
    }

    private function tailCustom($filepath, $lines = 1)
    {
        $f = @fopen($filepath, "rb");
        if ($f === false) return [];

        // Sets buffer size, according to the number of lines to retrieve.
        // This gives a performance boost when reading a few lines from the file.
        $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n") $lines -= 1;
        
        $output = '';
        $chunk = '';

        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }

        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }
        
        fclose($f);
        
        // Parse lines into structured format if possible
        $parsedLines = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (empty(trim($line))) continue;
            $parsedLines[] = $this->parseLogLine($line);
        }
        
        return $parsedLines;
    }

    private function parseLogLine($line)
    {
        // Example: [2023-12-08 14:00:01] TENANT_1.INFO: User logged in
        preg_match('/^\[(.*?)\]\s(.*?)\.(.*?):\s(.*)$/', $line, $matches);
        if (count($matches) === 5) {
            return [
                'timestamp' => $matches[1],
                'source' => $matches[2],
                'level' => $matches[3],
                'message' => $matches[4],
                'raw' => $line
            ];
        }
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'source' => 'SYSTEM',
            'level' => 'INFO',
            'message' => $line,
            'raw' => $line
        ];
    }
}
