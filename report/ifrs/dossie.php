<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Sao_Paulo');

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/lib/moodlelib.php');


global $DB, $USER;
print $OUTPUT->header();

if(is_siteadmin()){
	
	// tratar cpf
	$cpf = optional_param('cpf', '', PARAM_TEXT);
	$id = optional_param('id', null, PARAM_INT);
	$email = optional_param('email', '', PARAM_TEXT);

	$cpf = str_replace('.', '', $cpf);
	$cpf = str_replace('-', '', $cpf);

	if($cpf!=''){
		$usercpf = $DB->get_records_sql("select * from {user_info_data} uid where trim(replace(replace(data,'-',''),'.','')) = trim('".$cpf."')
	and fieldid = '1'");
		if(count($usercpf)==1){
			$user = get_complete_user_data('id', current ($usercpf)->userid);
		}
		elseif(count($usercpf)>1){
			echo 'Há '.count($usercpf).' contas para este CPF';
			foreach($usercpf as $item){
				$usercpf2 = $DB->get_record_sql("select * from mdl_user u where u.id = ".$item->userid);	
				echo '<br><a href="/report/ifrs/dossie.php?email='.$usercpf2->email.'">Dossiê para '.$usercpf2->email.'</a> | <a href="/report/ifrs/dossie.php?id='.$usercpf2->id.'">Acesso pelo ID</a>';
				//print_r($usercpf2);
			}
		}
	}


	if($cpf=='' && $email !=''){
		//echo "select * from {user} u where upper(trim(u.email)) like upper(trim('".$email."'))";
		$usercpf = $DB->get_records_sql("select * from {user} u where upper(trim(u.email)) like upper(trim('".$email."'))");
		//echo $usercpf;		
		if(count($usercpf)==1){
			$user = get_complete_user_data('id', current ($usercpf)->id);
			//print_r($user);
		}
		elseif(count($usercpf)>1){
			echo 'Há mais de uma conta para este email';
		}
	}
	if($cpf=='' && $email =='' && $id !=''){
		$user = get_complete_user_data('id', $id);
	}

	if($user){
		//print_r($user->profile);
		echo "<b>Perfil:</b><br>";
		echo "ID: ".$user->id."<br>";
		echo "Nome: ".$user->firstname."<br>";
		echo "Sobrenome: ".$user->lastname."<br>";
		echo "Login: ".$user->username."<br>";
		echo "E-mail: ".$user->email."<br>";
		echo $user->profile['Documento'].": ".$user->profile['CPF']."<br>";
		echo "Cor: ".$user->profile['Corraa']."<br>";
		echo "Renda: ".$user->profile['Rendapercapitafamliar']."<br><br>";
		echo "".$user->firstname." ".$user->lastname."<br>";
		echo "".$user->city."<br><br>";
				
		echo "<a href='/user/editadvanced.php?id=".$user->id."&course=1'>Editar perfil</a>";
		echo " | <a href='/report/ifrs/recupera.php?cpf=".$user->profile['CPF']."&email=".$user->email."'>Gerar nova senha</a> | <a href='/admin/user.php?sort=name&dir=ASC&perpage=30&page=0&delete=".$user->id."&sesskey=".sesskey()."'>Excluir conta</a>";
		if($user->confirmed == 0){
			echo " | Não confirmada [<a href='/admin/user.php?sort=name&dir=ASC&perpage=30&page=0&resendemail=".$user->id."&sesskey=".sesskey()."'>Reenviar confirmação</a> | <a href='/admin/user.php?sort=name&dir=ASC&perpage=30&page=0&confirmuser=".$user->id."&sesskey=".sesskey()."'>Confirmar conta</a>]<br><br>";
		}
		
		echo "<br><br><b>Acessos:</b><br>";
		echo "Primeiro Acesso: ".userdate($user->firstaccess, get_string('strftimedatetime'))."<br>";
		echo "Último Acesso: ".userdate($user->lastaccess, get_string('strftimedatetime'))."<br><br>";
		echo "Criação da Conta: ".userdate($user->timecreated, get_string('strftimedatetime'))."<br><br>";
		echo "Penúltimo Login: ".userdate($user->lastlogin, get_string('strftimedatetime'))."<br>";
		echo "Último Login: ".userdate($user->currentlogin, get_string('strftimedatetime'))."<br>";
		
		$senhas = $DB->get_records_sql("select l.timecreated from {logstore_standard_log} l 
			where l.userid = '".$user->id."' and l.relateduserid = '".$user->id."' and l.target = 'user_password' and l.action='updated'
			order by l.id desc");
		echo '<br><br> <b>Solicitações de nova senha</b>';
		foreach($senhas as $c){
			echo '<li>'.userdate($c->timecreated, get_string('strftimedatetime')).'<br>';
		}
			
		
		$cursos = $DB->get_records_sql("SELECT  c.id, c.fullname, r.name, ue.timecreated, r.archetype
	FROM {user_enrolments} ue, {enrol} e, {role} r , {course} c
		WHERE ue.userid = '".$user->id."'
		and ue.enrolid = e.id and e.roleid = r.id and e.courseid = c.id
		order by c.fullname");
		echo '<br><br> <h2>Matrículas Ativas</h2>';
		foreach($cursos as $c){
			
			$contato = $DB->get_record_sql("select cd.value from {customfield_data} cd where cd.fieldid = 5 and cd.instanceid = '".$c->id."'");
			$ch = $DB->get_record_sql("select cd.value from {customfield_data} cd where cd.fieldid = 1 and cd.instanceid = '".$c->id."'");

			$cancelou = $DB->get_records_sql("select l.id, l.action, l.timecreated
					from {logstore_standard_log} l
					where  l.target = 'user_enrolment' and l.courseid = '".$c->id."' and l.userid = '".$user->id."'
					order by l.timecreated");
			$strcan = "<b>Histórico de Matrícula</b>";
			foreach($cancelou as $canc){
				$strcan .= '<br><li>'.$canc->action.' - '.userdate($canc->timecreated, get_string('strftimedatetime'));
			}
			
			$certificados = $DB->get_records_sql("select sc.name, sci.timecreated, sci.code
		from {course} c, {simplecertificate} sc, {simplecertificate_issues} sci
	where c.id = sc.course and sci.certificateid = sc.id and sci.userid = '".$user->id."' and sc.course = '".$c->id."'
	order by sci.timecreated");
			$strcert = "<b>Histórico de Certificados</b>";
			foreach($certificados as $cert){
				$strcert .= '<br><li><a href="/mod/simplecertificate/wmsendfile.php?code='.$cert->code.'
			">'.$cert->name.'</a> - '.userdate($cert->timecreated, get_string('strftimedatetime'));
				if(strtolower($cert->name) == 'certificado digital'){
					$conferencia = $DB->get_records_sql("select cm.id from {simplecertificate} sc, {course_modules} cm, {simplecertificate_issues} sci where sc.course = ".$c->id." and lower(sc.name) = 'certificado digital' and sc.id = cm.instance and cm.module = '26' and sci.certificateid = sc.id and sci.userid = '".$user->id."'");
				$conferencia2 = $DB->get_records_sql("select cm.id from {simplecertificate} sc, {course_modules} cm, {simplecertificate_issues} sci where sc.course = ".$c->id." and lower(sc.name) = 'declaração com nota' and sc.id = cm.instance and cm.module = '26' and  sci.certificateid = sc.id and sci.userid = '".$user->id."'");
		list($cmid_conferencia) = each($conferencia);	
				$conferencia = $DB->get_records_sql("select cm.id from {simplecertificate} sc, {course_modules} cm, {simplecertificate_issues} sci where sc.course = ".$c->id." and lower(sc.name) = 'declaração com nota' and sc.id = cm.instance and cm.module = '26' and sci.certificateid = sc.id and sci.userid = '".$user->id."'");
		list($cmid_declaracao) = each($conferencia2);			
					$strcert .= "	<br><br><form id='bulkissue' name='bulkissue' method='post' action='/mod/simplecertificate/view.php'>
						<input type='hidden' name='type' value='pdf'>
			<input type='hidden' value='".$user->id."' name='selectedusers[]'/>
						<input type='hidden' name='id' value='".$cmid_conferencia."'>
				<input type='hidden' name='tab' value='2'>
				<input type='hidden' name='page' value='0'>
				<input type='hidden' name='perpage' value='30'>
				<input type='hidden' name='orderby' value='username'>
				<input type='hidden' name='issuelist' value='allusers'>
				<input type='hidden' name='action' value='download'>
				<input type='hidden' name='sesskey' value='".sesskey()."'>
			<button type='submit' class='btn btn-secondary'
				id='single_button5d45e6b34319033'
				title=''>Gerar certificado atualizado</button>
			</form>
			
			<form id='bulkissue' name='bulkissue' method='post' action='/mod/simplecertificate/view.php'>
						<input type='hidden' name='type' value='pdf'>
			<input type='hidden' value='".$user->id."' name='selectedusers[]'/>
						<input type='hidden' name='id' value='".$cmid_declaracao."'>
				<input type='hidden' name='tab' value='2'>
				<input type='hidden' name='page' value='0'>
				<input type='hidden' name='perpage' value='30'>
				<input type='hidden' name='orderby' value='username'>
				<input type='hidden' name='issuelist' value='allusers'>
				<input type='hidden' name='action' value='download'>
				<input type='hidden' name='sesskey' value='".sesskey()."'>
			<button type='submit' class='btn btn-secondary'
				id='single_button5d45e6b34319033'
				title=''>Gerar declaração com nota</button>
			</form>
			";
					}
			}
			/* gERAR O CERTIFICADO ATEMPORAL
			echo "	<br><br><form id='bulkissue' name='bulkissue' method='post' action='/mod/simplecertificate/view.php'>
						<input type='hidden' name='type' value='pdf'>
			<input type='hidden' value='".$user->id."' name='selectedusers[]'/>
						<input type='hidden' name='id' value='55712'>
				<input type='hidden' name='tab' value='2'>
				<input type='hidden' name='page' value='0'>
				<input type='hidden' name='perpage' value='30'>
				<input type='hidden' name='orderby' value='username'>
				<input type='hidden' name='issuelist' value='allusers'>
				<input type='hidden' name='action' value='download'>
				<input type='hidden' name='sesskey' value='".sesskey()."'>
			<button type='submit' class='btn btn-secondary'
				id='single_button5d45e6b34319033'
				title=''>Gerar certificado atualizado</button>
			</form>";*/
			
			$medias = $DB->get_records_sql("select gg.id, gi.itemname, gi.gradepass, gg.finalgrade
		from {grade_items} gi, {grade_grades} gg
	where gi.courseid = '".$c->id."' and gi.itemtype = 'category' and gg.itemid = gi.id and gg.userid = '".$user->id."'");
			$strmed2 = "<b>Histórico das Médias</b>";
			foreach($medias as $med){
				if(number_format($med->gradepass)>number_format($med->finalgrade))
					$strmed2 .= '<br><li><font color="red"><b>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'</b></font>';
				else
					$strmed2 .= '<br><li>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'';
			}
			
			$medias2 = $DB->get_records_sql("select gg.id, gi.itemname, gi.gradepass, gg.finalgrade
		from {grade_items} gi, {grade_grades} gg
	where gi.courseid = '".$c->id."' and gi.itemtype = 'course' and gg.itemid = gi.id and gg.userid = '".$user->id."'");
			$strmed = "<b>Histórico do Curso</b>";
			foreach($medias2 as $med){
				if(number_format($med->gradepass)>number_format($med->finalgrade))
					$strmed .= '<br><li><font color="red"><b>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'</b></font>';
				else
					$strmed .= '<br><li>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'';
			}
			
			$tentativas = $DB->get_records_sql("select qa.id, q.id qid, q.name, q.attempts, qa.timemodified, round(100*qa.sumgrades/q.sumgrades,2) nota
from {quiz} q, {quiz_attempts} qa
where q.id = qa.quiz and q.course = '".$c->id."' and qa.userid = '".$user->id."' and q.sumgrades > 0 and qa.state = 'finished' order by qa.timemodified");
			foreach($tentativas as $t){
				$quiztentativa[$t->qid]['tentativa'] .= '<a href="/mod/quiz/review.php?attempt='.$t->id.'">'.userdate($t->timemodified, get_string('strftimedatetime')).' ['.$t->nota.'] <a> - ';
				$quiztentativa[$t->qid]['quant'] = $t->attempts;
				$quiztentativa[$t->qid]['tentativas']++;
			}
			
			$notas = $DB->get_records_sql("select gg.id, gi.itemname, gi.itemmodule, gi.gradepass, gg.finalgrade, gi.iteminstance, cm.section, cs.name
		from {grade_items} gi, {grade_grades} gg, {course_modules} cm, {modules} m, {course_sections} cs
where gi.courseid = '".$c->id."' and gi.itemtype not in ('course','category') 
and gg.itemid = gi.id and gg.userid = '".$user->id."' 
and gi.iteminstance = cm.instance and cm.module = m.id and m.name = gi.itemmodule and cm.visible = 1 and cm.section = cs.id
order by cs.section");
			$section = 0;
			$strnot = "<b>Histórico das Notas</b><br>";
			foreach($notas as $not){
				if($section != $not->section){
					$strnot .= '<br><b>'.$not->name.'</b>';
					$section = $not->section;
				}
				$strnot .= '<li>'.$not->itemname.' - '.$not->itemmodule.' - '.$not->gradepass.' - '.$not->finalgrade;
				if($not->itemmodule == 'quiz')
					if($quiztentativa[$not->iteminstance]['tentativas'] == 0)
						$strnot .= ' - <font color="red"><b>Tentativas: '.(int)$quiztentativa[$not->iteminstance]['quant'].'</b></font>';
					else
						$strnot .= ' - Tentativas: '.$quiztentativa[$not->iteminstance]['tentativas'].' de '.(int)$quiztentativa[$not->iteminstance]['quant'].' : '.$quiztentativa[$not->iteminstance]['tentativa'];
			}
			
			$diascert = $DB->get_records_sql("SELECT value FROM {customfield_data} cd WHERE cd.fieldid = 1 AND cd.instanceid = '".$c->id."'");
	//print_r($diascert);
			if(count($diascert)==1){
				$dias = ceil((current($diascert)->value)/8);
				$diasp = strpos($dias, " dias");
				$diasf = (int) substr($dias, $diasp-2, 2);
				$strdiascert = $c->timecreated + 60*60*24*$diasf;
			}
			
			if($strdiascert > time()){
				$certData = "<font color='red'><b>".userdate($strdiascert, get_string('strftimedatetime'))."</b></font>";
			}
			else{
				$certData = userdate($strdiascert, get_string('strftimedatetime'));
			}	

		
								
			echo "<div style='background-color:#333333; color: white; '>".$c->fullname." (".$c->id.")</div>
					<div style='background-color:#eeeeee; margin-left: 20px; padding: 5px'>Contato: ".$contato->value." - CH: ".$ch->value." horas<BR><BR>
					<p><a href= '/course/view.php?id=".$c->id."'>Link para o Curso</a> :: <a href= '/report/outline/user.php?id=".$user->id."&course=".$c->id."&mode=outline'>Link para o Relatório de Atividades do Aluno no Curso</a>
					</p>
					<p>
					Última matrícula: ".userdate($c->timecreated, get_string('strftimedatetime'))." - Certificado disponível a partir de: ".$certData."
					<br>Papel: ".$c->name." - ".$c->archetype."</p>
					<p>".$strcan."</p>
					<p>".$strcert."</p>
					<p>".$strmed."</p>
					<p>".$strmed2."</p>
					<p>".$strnot."</p>
					</div>
				<br>";
		}
		
		$cursos = $DB->get_records_sql("select distinct  c.id, c.fullname, l.userid, 0 reprovado, 0 concluido, 1 desligamento
	from {logstore_standard_log} l, {course} c
		where l.action = 'deleted' and l.target = 'user_enrolment' and l.courseid = c.id 
		and l.userid = '".$user->id."'");
		echo '<br><br><h2>Matrículas Canceladas</h2>';
		foreach($cursos as $c){
			$cancelou = $DB->get_records_sql("select l.id, l.action, l.timecreated
					from {logstore_standard_log} l
					where  l.target = 'user_enrolment' and l.courseid = '".$c->id."' and l.userid = '".$user->id."'
					order by l.timecreated");
			$strcan = "<b>Histórico de Matrícula</b>";
			foreach($cancelou as $canc){
				$strcan .= '<br><li>'.$canc->action.' - '.userdate($canc->timecreated, get_string('strftimedatetime'));
			}
			
			$certificados = $DB->get_records_sql("select sc.name, sci.timecreated, sci.code
		from {course} c, {simplecertificate} sc, {simplecertificate_issues} sci
	where c.id = sc.course and sci.certificateid = sc.id and sci.userid = '".$user->id."' and sc.course = '".$c->id."'
	order by sci.timecreated");
			$strcert = "<b>Histórico de Certificados</b>";
			foreach($certificados as $cert){
				$strcert .= '<br><li><a href="/mod/simplecertificate/wmsendfile.php?code='.$cert->code.'
			">'.$cert->name.'</a> - '.userdate($cert->timecreated, get_string('strftimedatetime'));
			}
			
			$medias = $DB->get_records_sql("select gg.id, gi.itemname, gi.gradepass, gg.finalgrade
		from {grade_items} gi, {grade_grades} gg
	where gi.courseid = '".$c->id."' and gi.itemtype = 'category' and gg.itemid = gi.id and gg.userid = '".$user->id."'");
			$strmed = "<b>Histórico das Médias</b>";
			foreach($medias as $med){
				if(number_format($med->gradepass)>number_format($med->finalgrade))
					$strmed .= '<br><li><font color="red"><b>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'</b></font>';
				else
					$strmed .= '<br><li>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'';
			}
			
			$medias2 = $DB->get_records_sql("select gg.id, gi.itemname, gi.gradepass, gg.finalgrade
		from {grade_items} gi, {grade_grades} gg
	where gi.courseid = '".$c->id."' and gi.itemtype = 'course' and gg.itemid = gi.id and gg.userid = '".$user->id."'");
			$strmed = "<b>Histórico do Curso</b>";
			foreach($medias2 as $med){
				if(number_format($med->gradepass)>number_format($med->finalgrade))
					$strmed .= '<br><li><font color="red"><b>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'</b></font>';
				else
					$strmed .= '<br><li>'.$med->itemname.' - '.$med->gradepass.' - '.$med->finalgrade.'';
			}
			
			$tentativas = $DB->get_records_sql("select q.id, q.name, count(1) tot
from {quiz} q, {quiz_attempts} qa
where q.id = qa.quiz and q.course = '".$c->id."' and qa.userid = '".$user->id."'
group by q.id, q.name");
			foreach($tentativas as $t){
				$quiztentativa[$t->id] = $t->tot;
			}
			
			$notas = $DB->get_records_sql("select gg.id, gi.itemname, gi.itemmodule, gi.gradepass, gg.finalgrade, gi.iteminstance
		from {grade_items} gi, {grade_grades} gg
	where gi.courseid = '".$c->id."' and gi.itemtype not in ('course','category') and gg.itemid = gi.id and gg.userid = '".$user->id."'");
			$strnot = "<b>Histórico das Notas</b>";
			foreach($notas as $not){
				$strnot .= '<br><li>'.$not->itemname.' - '.$not->itemmodule.' - '.$not->gradepass.' - '.$not->finalgrade;
				if($not->itemmodule == 'quiz')
					$strnot .= ' - Tentativas: '.(int)$quiztentativa[$not->iteminstance];
			}
			
			
			$diascert = $DB->get_records_sql("select l.name
	from {label} l
	where l.course = '".$c->id."' and l.name like '%dias%'");
	
			if(count($diascert)==1){
				$dias = current($diascert)->name;
				$diasp = strpos($dias, " dias");
				$diasf = (int) substr($dias, $diasp-2, 2);
				$strdiascert = $c->timecreated + 60*60*24*$diasf;
			}
					
					
					
					
			echo "<details>
				<summary style='background-color:#88b77b'>".$c->fullname." (".$c->id.")</summary>
					<div style='background-color:#eeeeee; margin-left: 20px; padding: 5px'>
					<p>
					Última matrícula: ".userdate($c->timecreated, get_string('strftimedatetime'))." - Certificado disponível a partir de: ".userdate($strdiascert, get_string('strftimedatetime'))."
					<br>Papel: ".$c->name." - ".$c->archetype."</p>
					<p>".$strcan."</p>
					<p>".$strcert."</p>
					<p>".$strmed."</p>
					<p>".$strnot."</p>
					</div>
				</details><br>";
		}
	}
		
}		
print $OUTPUT->footer();

