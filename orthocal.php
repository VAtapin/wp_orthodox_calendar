<?php
/**
 * Plugin Name: Orthodox Calendar – Workshop
 * Plugin URI: https://kalender.georg-kloster.ru/calendar-api
 * Description: Gutenberg blocks and shortcodes for an Orthodox calendar: calendar dates, fasting rules, commemorations, readings and liturgical texts.
 * Version: 1.3.64
 * Requires at least: 6.3
 * Requires PHP: 8.0
 * Author: Vladimir Atapin
 * Author URI: https://atapin.de/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: orthodox-calendar-workshop
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/includes/media-cache.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/plugin.php';
Orthocal_Plugin::boot(__FILE__);
