<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

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
	$turma = 20;
	
	$libras = $DB->get_records_sql("select c.id, count(1) total
	from 
	(select u.id, u.firstname, u.lastname, u.email, u.timecreated, uid2.data as cid
	from {user_info_data} uid, {user} u left join  {user_info_data} uid2 on (uid2.fieldid = 5
	and uid2.userid = u.id)
	where uid.fieldid = 5 and uid.data = 'Sim, deficiência auditiva ou surdez' and uid.userid = u.id) tab, 
	{user_enrolments} ue, {enrol} e, {course} c
	where c.fullname like '%".$turma."%' and tab.id = ue.userid and ue.enrolid = e.id and e.courseid = c.id
	group by c.id
	order by total desc");
	$curso = array();
	foreach($libras as $i){
		$curso[$i->id] = $i->total;
	}

	$openCourse = $DB->get_records_sql("select * from (select cm.id cmid, p.id, c.id cid, c.fullname, p.name, p.content, 'page' as tipo, p.timemodified, cm.idnumber
	from {course} c, {page} p, {course_modules} cm
	where c.shortname like '%".$turma."%' and p.course = c.id and p.content like '%https://www.youtube.com/%' and cm.instance = p.id and cm.module =16
				   and  p.content not like '%Clique aqui para versão em Libras%'   and cm.visible = 1
	union
	select cm.id cmid, u.id, c.id cid, c.fullname, u.name, u.externalurl, 'url' as tipo, u.timemodified, cm.idnumber
	from {course} c, {url} u, {course_modules} cm
	where c.shortname like '%".$turma."%' and u.course = c.id and u.externalurl like '%https://www.youtube.com/%'  and cm.instance = u.id and cm.module =21  and cm.visible = 1
	union
	select cm.id cmid, a.id, c.id cid, c.fullname, a.name, a.intro, 'assign' as tipo, a.timemodified, cm.idnumber
	from {course} c, {assign} a, {course_modules} cm
	where c.shortname like '%".$turma."%' and a.course = c.id and a.intro like '%https://www.youtube.com/%' and cm.instance = a.id and cm.module =1 and  cm.visible = 1 and a.intro not like '%Clique aqui para versão em Libras%' 
	union

	select cm.id cmid, l.id, c.id cid, c.fullname, l.name, l.intro , 'label' as tipo, l.timemodified, cm.idnumber
	from {course} c, {label} l, {course_modules} cm
	where c.shortname like '%".$turma."%' and l.course = c.id and l.intro like '%https://www.youtube.com/%' and cm.instance = l.id and cm.module =13 and cm.visible = 1 and l.intro not like '%Clique aqui para versão em Libras%' 
	) t
	where t.fullname not like '%Inglês%' and t.fullname not like '%Espanhol%' and t.fullname not like '%Adicional%' and t.fullname not like '%Libras%'
	and ((lower(substring(t.idnumber,1,14)) <> 'acessibilidade'))
	order by t.cid desc, cmid
	");

	$courseTable = new html_table();
	$courseTable->align = array('left', 'left', 'left', 'left', 'left','left', 'center', 'center','center', 'center');
	$courseTable->head = array('Curso', 'Tipo', 'Total', 'Surdos', 'Link',  'Alteração', 'Libras', 'Legenda', 'Transc', 'Total de vídeos na página');
	$courseTable->data = array();

	foreach ($openCourse as $cid => $line) {
		
		if(strpos(strtolower($line->idnumber), 'libras') === false){
			$contador+=substr_count($line->content, 'www.youtube.com');
			
			$link  = $row[] = $line->content;
			$pos = strpos($link, 'https://www.youtube.com/');
			$final = strpos(substr($link, $pos, strlen($link)), '"');
			
			$row = array();
			if($curso[$line->cid]>1)
				$row[] = '<b>'.$line->fullname.'</b>';
			else
				$row[] = $line->fullname;
			$row[] = $line->tipo;
			$row[] = html_writer::link("$CFG->wwwroot/mod/".$line->tipo."/view.php?id=".$line->cmid, $line->name);
			$row[] = html_writer::link("$CFG->wwwroot/mod/".$line->tipo."/view.php?id=".$line->cmid, $curso[$line->cid]);
			$row[] = '<a href="'.substr($link,$pos, $final).'">'.substr($link,$pos, $final)."</a>";
			
			if($line->timemodified > (time()-60*60*24*30))
				$row[] = '<b>'.userdate($line->timemodified, get_string('strftimedatefullshort')).'</b>';
			else
				$row[] = userdate($line->timemodified, get_string('strftimedatefullshort'));
			
			if(strpos(strtolower($line->idnumber), 'libras') === false)
				$row[] = "";
			else
				$row[] = '<i class="icon fa fa-sign-language fa-fw" title="Libras"></i>';
			if(strpos(strtolower($line->idnumber), 'legenda') === false)
				$row[] = "";
			else
				$row[] = '<i class="icon fa fa-comments fa-fw" title="Legenda"></i>';
			if(strpos(strtolower($line->idnumber), 'transcrição') === false)
				$row[] = "";
			else
				$row[] = '<i class="icon fa fa-align-left fa-fw" title="Transcrição"></i>';
			
			if(substr_count($line->content, 'www.youtube.com')>1)
				$row[] = '<font color="red"><b>'.substr_count($line->content, 'www.youtube.com').'</b></font>';
			else
				$row[] = substr_count($line->content, 'www.youtube.com');
			
			$courseTable->data[] = $row;
		}
	}

	// All the processing done, the rest is just output stuff.

	print $OUTPUT->header();

	print $OUTPUT->heading('Vídeos');

	print html_writer::table($courseTable);
	echo '<br><br>Total de vídeos: '.$contador;

	print $OUTPUT->footer();
}