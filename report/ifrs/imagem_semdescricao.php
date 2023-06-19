<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Sao_Paulo');
set_time_limit(0);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');

$curso = optional_param('curso', null, PARAM_INT);

global $DB, $USER;
print $OUTPUT->header();

if(is_siteadmin()){
	$tresHoras = 86340;
	$contador = 0;
	if(date('m')<4){
		$turma = (date('Y'))."A";
		$fimCurso = "31/07/".date('Y');
		$fimInscricao = "31/07/".(date('Y'));
		$startDate = mktime(0,0,0,01,01,date('Y'))+$tresHoras;
		$endDate = mktime(0,0,0,07,31,date('Y'))+$tresHoras;
		$enrolEndDate = mktime(0,0,0,06,30,date('Y'))+$tresHoras;
	}
	if(date('m')>10){
		$turma = (date('Y')+1)."A";
		$fimCurso = "31/07/".(date('Y')+1);
		$fimInscricao = "31/07/".(date('Y')+1);
		$startDate = mktime(0,0,0,01,01,date('Y')+1)+$tresHoras;
		$endDate = mktime(0,0,0,07,31,date('Y')+1)+$tresHoras;
		$enrolEndDate = mktime(0,0,0,06,30,date('Y')+1)+$tresHoras;
	}
	else{
		$turma = (date('Y'))."B";
		$fimCurso = "31/01/".(date('Y')+1);
		$fimInscricao = "31/12/".(date('Y'));
		$startDate = mktime(0,0,0,07,01,date('Y'));
		$endDate = mktime(0,0,0,01,31,date('Y')+1)+$tresHoras;
		$enrolEndDate = mktime(0,0,0,12,31,date('Y'))+$tresHoras;
	}


	/* paginas */

	if($curso == 0)
		$listaConteudos = $DB->get_records_sql("select v.*, c.id courseid, c.fullname from v_imagem_tag v, {course} c where c.id = v.course and c.shortname like '%".$turma."%' and v.name <> 'Saiba mais sobre o curso, notas e certificado' order by c.fullname");
	else
		$listaConteudos = $DB->get_records_sql("select v.*, c.id courseid, c.fullname from v_imagem_tag v, {course} c where c.id = v.course and c.shortname like '%".$turma."%' v.name <> 'Saiba mais sobre o curso, notas e certificado' order by c.fullname");

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
		
		$context = get_context_instance(CONTEXT_MODULE, $c->id);
		$todasImagem = file_rewrite_pluginfile_urls($c->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $c->id);
		$imagens = explode('<img', $todasImagem);
		
		unset($imagens[0]);
		
		foreach($imagens as $imag){
			$imagem = '<img'.$imag;
			
			$findme   = '<img';
			$posIni = strpos($imagem, $findme);
			
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
			if(substr($strImg, 0, $altFim)=='' || substr($strImg, 0, $altFim)=='.')
				echo '<tr><td>'.$tagImagem.'</td><td>*'.substr($strImg, 0, $altFim).'*</td><td>'.html_writer::link("$CFG->wwwroot/mod/page/view.php?id=".$c->id, $c->name).'</td></tr>';
		}

	}

	echo "</table>";
}

