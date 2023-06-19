<?php
/**
 * @package    customstats
 * @subpackage local
 * @copyright  2018 Kaptiva
 * @license    http://creativecommons.org/licenses/by-nd/4.0/
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_customstats\stats',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    [
        'classname' => 'local_customstats\stats_size',
        'blocking' => 0,
        'minute' => '38',
        'hour' => '*/6',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
];