<?php

if (!function_exists('generate_uuid')) {
    /**
     * Generate a UUID.
     *
     * @return string
     */
    function generate_uuid(): string
    {
        return \Illuminate\Support\Str::uuid()->toString();
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format currency amount.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function format_currency(float $amount, string $currency = 'USD'): string
    {
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date in human readable format.
     *
     * @param \Carbon\Carbon|string $date
     * @param string $format
     * @return string
     */
    function format_date($date, string $format = 'M d, Y'): string
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        
        return $date->format($format);
    }
}

if (!function_exists('calculate_age')) {
    /**
     * Calculate age from date of birth.
     *
     * @param \Carbon\Carbon|string $dateOfBirth
     * @return int
     */
    function calculate_age($dateOfBirth): int
    {
        if (is_string($dateOfBirth)) {
            $dateOfBirth = \Carbon\Carbon::parse($dateOfBirth);
        }
        
        return $dateOfBirth->age;
    }
}

if (!function_exists('generate_slug')) {
    /**
     * Generate URL friendly slug.
     *
     * @param string $text
     * @return string
     */
    function generate_slug(string $text): string
    {
        return \Illuminate\Support\Str::slug($text);
    }
}

if (!function_exists('truncate_text')) {
    /**
     * Truncate text to specified length.
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    function truncate_text(string $text, int $length = 100, string $suffix = '...'): string
    {
        return \Illuminate\Support\Str::limit($text, $length, $suffix);
    }
}

if (!function_exists('format_file_size')) {
    /**
     * Format file size in human readable format.
     *
     * @param int $bytes
     * @return string
     */
    function format_file_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * Validate email format.
     *
     * @param string $email
     * @return bool
     */
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('generate_random_string')) {
    /**
     * Generate random string.
     *
     * @param int $length
     * @return string
     */
    function generate_random_string(int $length = 10): string
    {
        return \Illuminate\Support\Str::random($length);
    }
}

if (!function_exists('array_to_xml')) {
    /**
     * Convert array to XML.
     *
     * @param array $array
     * @return string
     */
    function array_to_xml(array $array): string
    {
        $xml = new \SimpleXMLElement('<root/>');
        
        foreach ($array as $key => $value) {
            $xml->addChild($key, $value);
        }
        
        return $xml->asXML();
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * Get client IP address.
     *
     * @return string|null
     */
    function get_client_ip(): ?string
    {
        return request()->ip();
    }
}

if (!function_exists('is_mobile')) {
    /**
     * Check if request is from mobile device.
     *
     * @return bool
     */
    function is_mobile(): bool
    {
        $userAgent = request()->header('User-Agent');
        
        $mobileAgents = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone'
        ];
        
        foreach ($mobileAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('get_gravatar_url')) {
    /**
     * Get Gravatar URL for email.
     *
     * @param string $email
     * @param int $size
     * @return string
     */
    function get_gravatar_url(string $email, int $size = 80): string
    {
        $hash = md5(strtolower(trim($email)));
        
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
    }
}

if (!function_exists('format_phone_number')) {
    /**
     * Format phone number.
     *
     * @param string $phone
     * @return string
     */
    function format_phone_number(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format as (XXX) XXX-XXXX
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        }
        
        return $phone;
    }
}

if (!function_exists('get_time_ago')) {
    /**
     * Get time ago string from datetime.
     *
     * @param \Carbon\Carbon|string $datetime
     * @return string
     */
    function get_time_ago($datetime): string
    {
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime);
        }
        
        return $datetime->diffForHumans();
    }
}

if (!function_exists('sanitize_filename')) {
    /**
     * Sanitize filename for security.
     *
     * @param string $filename
     * @return string
     */
    function sanitize_filename(string $filename): string
    {
        // Remove any path traversal attempts
        $filename = basename($filename);
        
        // Remove any non-alphanumeric characters except dots, hyphens, and underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        return $filename;
    }
}

if (!function_exists('get_mimetype_icon')) {
    /**
     * Get icon class for MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    function get_mimetype_icon(string $mimeType): string
    {
        $iconMap = [
            'image/jpeg' => 'fa-file-image',
            'image/png' => 'fa-file-image',
            'image/gif' => 'fa-file-image',
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'application/zip' => 'fa-file-archive',
            'text/plain' => 'fa-file-alt',
            'application/json' => 'fa-file-code',
        ];
        
        return $iconMap[$mimeType] ?? 'fa-file';
    }
}
