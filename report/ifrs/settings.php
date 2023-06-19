<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportifrs', get_string('pluginname', 'report_ifrs'),
                                              "$CFG->wwwroot/report/ifrs/index.php"));

$settings = null;

