<?php
/**
 * Error logging and reporting
 */
class UWGuide_Logger {
    
    /**
     * Log levels
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    
    /**
     * Maximum number of logs to keep
     */
    const MAX_LOGS = 100;
    
    /**
     * Log a message
     *
     * @param string $message Message to log
     * @param string $level Log level
     * @param array $context Additional context
     */
    public static function log($message, $level = self::INFO, $context = []) {
        // Only log errors in production
        if (defined('WP_ENV') && WP_ENV === 'production' && $level !== self::ERROR) {
            return;
        }
        
        // Format the log entry
        $entry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
        
        // Get existing logs
        $logs = get_option('uw_guide_logs', []);
        
        // Add new entry
        array_unshift($logs, $entry);
        
        // Keep only the most recent logs
        $logs = array_slice($logs, 0, self::MAX_LOGS);
        
        // Save logs
        update_option('uw_guide_logs', $logs);
        
        // Also log to WordPress error log for errors
        if ($level === self::ERROR) {
            error_log('UW Guide Error: ' . $message . ' | Context: ' . print_r($context, true));
        }
    }
    
    /**
     * Get all logs
     *
     * @param string $level Filter by log level
     * @return array Logs
     */
    public static function get_logs($level = null) {
        $logs = get_option('uw_guide_logs', []);
        
        if ($level) {
            return array_filter($logs, function($log) use ($level) {
                return $log['level'] === $level;
            });
        }
        
        return $logs;
    }
    
    /**
     * Clear all logs
     */
    public static function clear_logs() {
        update_option('uw_guide_logs', []);
    }
}
