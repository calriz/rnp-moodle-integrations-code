<?php
/**
 * @package    customstats
 * @subpackage local
 * @copyright  2018 Kaptiva
 * @license    http://creativecommons.org/licenses/by-nd/4.0/
 */

defined('MOODLE_INTERNAL') || die;

$settings = new admin_settingpage('local_customstats_settings', get_string('pluginname','local_customstats'), 'moodle/site:config');
$ADMIN->add('tools', $settings);

if ($ADMIN->fulltree) {
    
    $settings->add(new admin_setting_configtext('local_customstats/moodle_region',
                                                get_string('config_moodle_region', 'local_customstats'),
                                                get_string('config_moodle_regiondescription', 'local_customstats'), ''));

    $settings->add(new admin_setting_configtext('local_customstats/moodle_alias',
                                                get_string('config_moodle_alias', 'local_customstats'),
                                                get_string('config_moodle_aliasdescription', 'local_customstats'), ''));
}
