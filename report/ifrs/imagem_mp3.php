<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Sao_Paulo');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');

if(is_siteadmin()){
	$curso = optional_param('curso', null, PARAM_INT);

	global $DB, $USER;
	print $OUTPUT->header();

	$meses = array(1=> "Jan", 2=> "Fev", 3 => "Mar", 4=> "Abr", 5 => "Mai", 6=> "Jun", 7=> "Jul", 8=> "Ago", 9=> "Set", 10=> "Out", 11=> "Nov", 12 => "Dez");
		
	$ano = date("Y");
	$dataHora = time();

	$jan1 = mktime (0,0,0,1,1,date('Y')-1);


	$cursos = $DB->get_records_sql("select cd.instanceid, c.fullname
	from mdl_customfield_field cf, mdl_customfield_data cd, mdl_customfield_category cc, mdl_course c,
	mdl_customfield_field cf2, mdl_customfield_data cd2, mdl_customfield_category cc2
	where 
	cf.id = '3' and cd.value = '1' and cc.area = 'course' and cf.id = cd.fieldid and cc.id = cf.categoryid and
	cf2.id = '7' and cd2.value = '".$ano."' and cc2.area = 'course' and cf2.id = cd2.fieldid and cc2.id = cf2.categoryid
	and cd.instanceid = c.id and cd2.instanceid = c.id and c.fullname like '%2020B%'
	order by c.fullname ");
	$listaCursos = "";
	foreach($cursos as $c){
		$listaCursos .= $c->instanceid.", ";
	}
	$listaCursos = substr($listaCursos,0,strlen($listaCursos)-2);

	/* 	questões */
	if($curso == 0)
		$listaConteudos = $DB->get_records_sql("select v.*, c.id courseid, c.fullname from v_imagem_mp3_tag v, mdl_course c where c.id = v.course and c.id in (".$listaCursos.") order by c.fullname, v.id");
	else
		$listaConteudos = $DB->get_records_sql("select v.*, c.id courseid, c.fullname from v_imagem_mp3_tag v, mdl_course c where c.id = v.course and c.id in (".$curso.") order by c.fullname, v.id");


					
	echo '<table border="1"><tr><th>Link</th></tr>';
	$nomeCurso = "";
					
	foreach ($listaConteudos as $c){
			
		if($nomeCurso != $c->fullname)
			echo '<tr><th >'.$c->fullname.'</td></tr>';
		$nomeCurso = $c->fullname;
		
		echo '<tr><td>'.html_writer::link("$CFG->wwwroot/question/question.php?courseid=".$c->courseid."&id=".$c->id, $c->name).'</td></tr>';
		
	}
	echo "</table>";
}