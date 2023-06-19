<?php
defined('MOODLE_INTERNAL') || die;

function xmldb_report_ifrs_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

		$table = new xmldb_table('ifrs_sistec');
		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
			$table->add_field('dia', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
			$table->add_field('cpf', XMLDB_TYPE_CHAR, '13', null, null, null, null);
			$table->add_field('studentname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('cor', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('renda', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('coursename', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('campus', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('instituicao', XMLDB_TYPE_CHAR, '255', null, null, null, null);
			$table->add_field('situacao', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
			$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
			$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
			$dbman->create_table($table);
        }

    return true;
}

