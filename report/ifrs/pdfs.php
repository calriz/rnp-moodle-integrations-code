<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Sao_Paulo');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('reportifrs');

if(is_siteadmin()){
	global $DB, $USER;
	print $OUTPUT->header();

	$meses = array(1=> "Jan", 2=> "Fev", 3 => "Mar", 4=> "Abr", 5 => "Mai", 6=> "Jun", 7=> "Jul", 8=> "Ago", 9=> "Set", 10=> "Out", 11=> "Nov", 12 => "Dez");
		
	$ano = date("Y");
	$dataHora = time();

	$jan1 = mktime (0,0,0,1,1,date('Y')-1);


	$listaConteudos = $DB->get_records_sql("select cm.id cmid, r.name, c.id courseid, c.fullname, cm.idnumber from mdl_course_modules cm, mdl_resource r, mdl_course c
	where cm.module = 18 and cm.instance = r.id and cm.course = c.id
	 and lower(substring(cm.idnumber,1,14)) not like '%acessibilidade%'
	order by c.fullname, r.name");
					
	echo '<table border="1"><tr><th>Nome</th></tr>';
	$nomeCurso = "";
					
	foreach ($listaConteudos as $c){
			
		if($nomeCurso != $c->fullname)
			echo '<tr><th>'.html_writer::link("$CFG->wwwroot/course/view.php?id=".$c->courseid, $c->fullname).'</td></tr>';
		$nomeCurso = $c->fullname;
		
		//if(strtolower(substr($c->idnumber,0,14)) != 'acessibilidade')
			echo '<tr><td>'.html_writer::link("$CFG->wwwroot/mod/resource/view.php?id=".$c->cmid, $c->name).'</td></tr>';
		

	}
	echo "</table>";
}