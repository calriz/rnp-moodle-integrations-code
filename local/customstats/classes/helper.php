<?php
/**
 * @package    customstats
 * @subpackage local
 * @copyright  2018 Kaptiva
 * @license    http://creativecommons.org/licenses/by-nd/4.0/
 */

namespace local_customstats;

defined('MOODLE_INTERNAL') || die();

class helper
{
    public static function get_db_log()
    {
        global $CFG;

        //se existir este banco e for o mesmo que o principal, desconsiderar
        if (($CFG->dbhost == self::get_config('dbhost')) &&
            ($CFG->dbname == self::get_config('dbname')))
            return false;

        $dbdriver = self::get_config('dbdriver');
        list($dblibrary, $dbtype) = explode('/', $dbdriver);

        if (!$db = \moodle_database::get_driver_instance($dbtype, $dblibrary, true)) {
            debugging("Unknown driver $dblibrary/$dbtype", DEBUG_DEVELOPER);
            return false;
        }

        $dboptions = [
            'dbpersist'        => self::get_config('dbpersist'),
            'dbsocket'         => self::get_config('dbsocket'),
            'dbport'           => self::get_config('dbport'),
            'dbschema'         => self::get_config('dbschema'),
            'dbcollation'      => self::get_config('dbcollation'),
            'dbhandlesoptions' => self::get_config('dbhandlesoptions'),
        ];
            
        try {
            $db->connect(
                self::get_config('dbhost'), 
                self::get_config('dbuser'), 
                self::get_config('dbpass'),
                self::get_config('dbname'), 
                false, 
                $dboptions
            );
            $tables = $db->get_tables();
            if (!in_array(self::get_config('dbtable'), $tables)) {
                debugging('Cannot find the specified table', DEBUG_DEVELOPER);
                return false;
            }
        } catch (\moodle_exception $e) {
            debugging('Cannot connect to external database: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }

        return $db;
    }

    public static function dbtable()
    {
        return self::get_config('dbtable');
    }

    public static function get_config($name)
    {
        return get_config('logstore_database', $name);
    }
}