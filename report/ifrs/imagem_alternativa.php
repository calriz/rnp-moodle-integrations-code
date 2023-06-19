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

	$listaConteudos = $DB->get_records_sql("select v.*, c.id courseid, c.fullname from v_imagem_alternativas_tag v, mdl_course c where c.id = v.course and c.id in (".$curso.") order by c.fullname");

	$courseTable = new html_table();
	$courseTable->align = array('left','left', 'left', 'left', 'left');
	$courseTable->head = array('Curso', 'Tipo', 'Imagem', 'Alternativo', 'Nome');
	$courseTable->data = array();
					
	echo '<table border="1"><tr><th>Imagem</th><th>Descrição</th><th>Link</th></tr>';
	$nomeCurso = "";
					
	foreach ($listaConteudos as $c){
			
		if($nomeCurso != $c->fullname)
			echo '<tr><th colspan="3">'.$c->fullname.'</td></tr>';
		$nomeCurso = $c->fullname;
		
		$context =  get_context_instance(CONTEXT_COURSE, $c->courseid);
		$todasImagem = file_rewrite_pluginfile_urls($c->answer, 'draftfile.php', $context->id, 'user', 'draft', $c->id);
		$imagens = explode('<img', $todasImagem);
		
		unset($imagens[0]);
		
		foreach($imagens as $imag){
			$imagem = '<img'.$imag;
			
			$findme   = '<img';
			$posIni = strpos($mystring, $findme);
			$strImg = substr($imagem, $posIni, strlen($imagem));
			$posFim = strpos($strImg, '>');
			$tagImg = substr($strImg, 0, $posFim);
			
			$altIni = (int)strpos($tagImg, 'src="');
			$strImg = substr($tagImg, $altIni+5, strlen($tagImg));
			$altFim = strpos($strImg, '"');
				
			$tagImagem = '<img src="'.substr($strImg, 0, $altFim).'" width="100">';

			
			$altIni = (int)strpos($tagImg, 'alt="');
			$strImg = substr($tagImg, $altIni+5, strlen($tagImg));
			$altFim = strpos($strImg, '"');
			
			echo '<tr><td>'.$tagImagem.'</td><td>*'.substr($strImg, 0, $altFim).'*</td><td>'.html_writer::link("$CFG->wwwroot/question/question.php?courseid=".$c->courseid."&id=".$c->id, 'Questão').'</td></tr>';
		}

	}
	echo "</table>";
}