<?php
/**
 * Class for standardizing date handling throughout the plugin
 */
class UWGuide_Date_Handler {
    
    /**
     * Standardize a date string to YYYYMMDD format
     *
     * @param string|null $date_string The date string to standardize
     * @return string Standardized date in Ymd format, or empty string if invalid
     */
    public static function standardize($date_string) {
        if (empty($date_string)) {
            return '';
        }
        
        // Try to convert the string to a timestamp
        $timestamp = strtotime($date_string);
        
        if ($timestamp === false) {
            error_log('Invalid date format: ' . $date_string);
            return '';
        }
        
        // Return standardized format - YYYYMMDD
        return date('Ymd', $timestamp);
    }
    
    /**
     * Check if a date is valid according to specified formats
     *
     * @param string $date_string Date string to check
     * @param array $formats Array of date formats to try
     * @return bool|DateTime False if invalid, DateTime object if valid
     */
    public static function parse_date($date_string, $formats = ['Ymd', 'Y-m-d H:i:s', 'd/m/Y']) {
        if (empty($date_string)) {
            return false;
        }
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $date_string);
            if ($date !== false) {
                return $date;
            }
        }
        
        return false;
    }
    
    /**
     * Compare two dates to determine if an update is required
     *
     * @param string $stored_date The stored date in the database
     * @param string $remote_date The remote date to compare against
     * @return bool True if update is required, false otherwise
     */
    public static function is_update_required($stored_date, $remote_date) {
        // If either date is empty, update is required
        if (empty($stored_date) || empty($remote_date)) {
            return true;
        }
        
        $stored = self::parse_date($stored_date);
        $remote = self::parse_date($remote_date);
        
        // If either date is invalid, update is required
        if ($stored === false || $remote === false) {
            return true;
        }
        
        // Update if remote date is newer than stored date
        return $remote > $stored;
    }
}
