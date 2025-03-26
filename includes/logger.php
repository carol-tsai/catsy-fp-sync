<?php
class Logger {
    private $logFile;
    
    public function __construct($file = 'sync.log') {
        $this->logFile = __DIR__ . '/../logs/' . $file;
    }
    
    public function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}