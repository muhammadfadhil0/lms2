<?php
/**
 * Time Format Helper
 * Provides consistent 24-hour time formatting throughout the LMS
 */

class TimeHelper {
    
    /**
     * Format time to 24-hour format (HH:MM)
     * @param string $time Time string (e.g., "14:30:00" or "14:30")
     * @return string Formatted time in HH:MM format
     */
    public static function format24Hour($time) {
        if (empty($time)) return '';
        
        // Handle different time formats
        if (strlen($time) > 5) {
            // If it includes seconds (HH:MM:SS), remove them
            return substr($time, 0, 5);
        }
        
        return $time;
    }
    
    /**
     * Format time range to 24-hour format (HH:MM - HH:MM)
     * @param string $startTime Start time
     * @param string $endTime End time
     * @return string Formatted time range
     */
    public static function formatTimeRange($startTime, $endTime) {
        $start = self::format24Hour($startTime);
        $end = self::format24Hour($endTime);
        
        if (empty($start) || empty($end)) {
            return '';
        }
        
        return $start . ' - ' . $end;
    }
    
    /**
     * Convert time to display format with 24-hour indicator
     * @param string $time Time string
     * @param bool $showIndicator Whether to show "24 jam" indicator
     * @return string
     */
    public static function displayFormat($time, $showIndicator = false) {
        $formatted = self::format24Hour($time);
        
        if ($showIndicator && !empty($formatted)) {
            return $formatted . ' <span class="text-xs text-gray-500">(24 jam)</span>';
        }
        
        return $formatted;
    }
    
    /**
     * Get JavaScript function to enforce 24-hour format
     * @return string JavaScript code
     */
    public static function getJS24HourScript() {
        return "
        <script>
        // Ensure 24-hour format for time inputs
        document.addEventListener('DOMContentLoaded', function() {
            const timeInputs = document.querySelectorAll('input[type=\"time\"]');
            timeInputs.forEach(function(input) {
                // Force 24-hour format
                input.setAttribute('step', '60');
                input.setAttribute('data-format', '24');
                
                // Add visual indicator
                const parent = input.parentElement;
                if (parent && !parent.querySelector('.time-format-indicator')) {
                    const indicator = document.createElement('small');
                    indicator.className = 'time-format-indicator text-xs text-gray-500 mt-1 block';
                    indicator.textContent = 'Format: 24 jam (contoh: 14:30 untuk 2:30 siang)';
                    parent.appendChild(indicator);
                }
            });
        });
        </script>
        ";
    }
}
?>