<?php
/**
 * @package    customstats
 * @subpackage local
 * @copyright  2018 Kaptiva
 * @license    http://creativecommons.org/licenses/by-nd/4.0/
 */

namespace local_customstats;

defined('MOODLE_INTERNAL') || die();

class stats extends \core\task\scheduled_task
{  
    private $region,
            $moodle_alias;

    public function get_name()
    {
        return get_string('task_stats', 'local_customstats');
    }

    public function execute()
    {
        $this->region = get_config('local_customstats', 'moodle_region');
        $this->moodle_alias = get_config('local_customstats', 'moodle_alias');

        $this->usuarios_stats();
        
        return true;
    }

    private function usuarios_stats()
    {
        $usuarios = [
            '5min'  => 60*5,           //5min
            '1h'    => 60*60,          //1h
            '1d'    => 60*60*24,       //1d
            '1m'    => 60*60*24*30,    //1m
            '1y'    => 60*60*24*365,   //1a
            'todos' => null,           //todos
        ];

        foreach($usuarios as $janela => $tempo) {
            
            $qtde = $this->calcular_usuarios_qtde($tempo);
            $comm = sprintf('aws cloudwatch put-metric-data --region %s --metric-name Users_%s_%s --namespace Moodle --value %d',
                $this->region,
                $this->moodle_alias,
                $janela,
                $qtde
            );
            `$comm`;

            mtrace(sprintf('Users_%s_%s = %d', $this->moodle_alias, $janela, $qtde));
        }
    }

    private function calcular_usuarios_qtde($tempo)
    {
        global $DB;

        $sql = "SELECT count(1) qtde
                FROM {user}
                WHERE lastaccess > :lastaccess
                  AND deleted = 0
                  AND suspended = 0
                  AND username <> 'guest'";

        $lastaccess = 0;
        if (!is_null($tempo))
            $lastaccess = time() - $tempo;
            
        $params = ['lastaccess' => $lastaccess];
        $reg = $DB->get_record_sql($sql, $params);
        return $reg->qtde;
    }
}
