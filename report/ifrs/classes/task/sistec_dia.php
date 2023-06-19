<?php
namespace report_ifrs\task;

defined('MOODLE_INTERNAL') || die();

class sistec_dia extends \core\task\scheduled_task {

    public function get_name() {
        return 'sistec_dia';
    }
	
    public function execute() {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/report/ifrs/roda_sistec_dia.php");

    }
}
