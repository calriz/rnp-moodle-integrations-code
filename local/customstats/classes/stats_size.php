<?php
/**
 * @package    customstats
 * @subpackage local
 * @copyright  2018 Kaptiva
 * @license    http://creativecommons.org/licenses/by-nd/4.0/
 */

namespace local_customstats;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/customstats/classes/helper.php');

class stats_size extends \core\task\scheduled_task
{  
    private $region,
            $moodle_alias,
            $db_log;

    public function get_name()
    {
        return get_string('task_stats_size', 'local_customstats');
    }

    public function execute()
    {
        $this->region = get_config('local_customstats', 'moodle_region');
        $this->moodle_alias = get_config('local_customstats', 'moodle_alias');
        $this->db_log = helper::get_db_log();

        $this->moodledata_stats();
        $this->db_stats();
        
        return true;
    }

    private function moodledata_stats()
    {
        global $DB;

        $sql = "SELECT sum(filesize) filesize
                FROM (
                    SELECT DISTINCT contenthash, filesize
                    FROM {files}
                ) t";

        $params = [];
        $reg = $DB->get_record_sql($sql, $params);

        $comm = sprintf('aws cloudwatch put-metric-data --region %s --metric-name MoodleDataSize_%s --namespace Moodle --value %d --unit Bytes',
            $this->region,
            $this->moodle_alias,
            $reg->filesize
        );
        `$comm`;

        mtrace(sprintf('MoodleDataSize_%s = %d', $this->moodle_alias, $reg->filesize));
    }

    private function db_stats()
    {
        global $DB, $CFG;

        $size = 0;
        $sql = "SELECT sum(data_length + index_length) dbsize
                FROM information_schema.tables 
                WHERE table_schema = :schema";

        //banco principal
        $params = ['schema' => $CFG->dbname];
        $reg = $DB->get_record_sql($sql, $params);
        $size += (int)$reg->dbsize;

        //banco log (se existir)
        if ($this->db_log) {
            $params = ['schema' => helper::get_config('dbname')];
            $reg = $this->db_log->get_records_sql($sql, $params);
            if (is_array($reg))
                $reg = array_pop($reg);
            $size += (int)$reg->dbsize;
        }

        $comm = sprintf('aws cloudwatch put-metric-data --region %s --metric-name DBSize_%s --namespace Moodle --value %d --unit Bytes',
            $this->region,
            $this->moodle_alias,
            $size
        );
        `$comm`;

        mtrace(sprintf('DBSize_%s = %d', $this->moodle_alias, $size));
    }
}
