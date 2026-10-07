<?php
if (!defined('ABSPATH')) exit;

final class Orthocal_Config {
    const DEFAULT_CALENDAR_ORIGIN = 'https://kalender.georg-kloster.ru';
    const DEFAULT_PUBLIC_API_ORIGIN = 'https://bible-desktop.com';

    static function normalize_origin($origin) {
        $origin = untrailingslashit(trim((string) $origin));
        $parts = wp_parse_url($origin);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment'])
            || !empty($parts['path'])) return '';
        return $origin;
    }

    static function calendar_origin() {
        $origin = defined('ORTHOCAL_API_ORIGIN') ? (string) ORTHOCAL_API_ORIGIN : self::DEFAULT_CALENDAR_ORIGIN;
        $origin = (string) apply_filters('orthocal_api_origin', $origin);
        return self::normalize_origin($origin) ?: self::DEFAULT_CALENDAR_ORIGIN;
    }

    static function calendar_url($path = '') {
        return self::calendar_origin() . ($path === '' ? '' : '/' . ltrim((string) $path, '/'));
    }

    static function public_api_origin() {
        $options = get_option('orthocal_options', []);
        $saved = is_array($options) ? self::normalize_origin($options['public_api_url'] ?? '') : '';
        $origin = (string) apply_filters('orthocal_public_api_origin', $saved ?: self::DEFAULT_PUBLIC_API_ORIGIN);
        return self::normalize_origin($origin) ?: self::DEFAULT_PUBLIC_API_ORIGIN;
    }

    static function public_api_url($path = '') {
        return self::public_api_origin() . ($path === '' ? '' : '/' . ltrim((string) $path, '/'));
    }
}
