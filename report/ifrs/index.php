<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('reportifrs');

if(is_siteadmin()){
	$box = '';

	$usersOverall = $DB->get_record_sql("select count(1) total from {user}");
	$box .=  'Total de inscritos: '.$usersOverall->total.'<br>';

	$usersNotConfirmed = $DB->get_record_sql("select count(1) total from {user} where confirmed = 0");
	$box .=  'Não confirmados: '.$usersNotConfirmed->total.'<br><hr>';

	$box .= '<BR>ACESSIBILIDADE <BR>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/imagem.php", 'Imagens em Páginas').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/imagem_semdescricao.php", 'Imagens em Páginas - Sem descrição').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/imagem_questao.php", 'Imagens em Questionários').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/imagem_alternativa.php", 'Imagens em Alternativas').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/imagem_mp3.php", 'Arquivos Mp3').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/pdfs.php", 'PDFs').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/transcricao.php", 'Vídeos sem Transcrição').'<br>';
	$box .= html_writer::link("$CFG->wwwroot/report/ifrs/libras.php", 'Vídeos sem Libras').'<br>';
	print $OUTPUT->header();

	print $OUTPUT->heading('Usuários inscritos');
	print $OUTPUT->box($box.'<br>');

	print $OUTPUT->footer();
}
