<?php
set_time_limit(900);
 
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

function validaCPF($cpf = null) {

	// Verifica se um número foi informado
	if(empty($cpf)) {
		return false;
	}

	// Elimina possivel mascara
	$cpf = preg_replace("/[^0-9]/", "", $cpf);
	$cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
	
	// Verifica se o numero de digitos informados é igual a 11 
	if (strlen($cpf) != 11) {
		return false;
	}
	// Verifica se nenhuma das sequências invalidas abaixo 
	// foi digitada. Caso afirmativo, retorna falso
	else if ($cpf == '00000000000' || 
		$cpf == '11111111111' || 
		$cpf == '22222222222' || 
		$cpf == '33333333333' || 
		$cpf == '44444444444' || 
		$cpf == '55555555555' || 
		$cpf == '66666666666' || 
		$cpf == '77777777777' || 
		$cpf == '88888888888' || 
		$cpf == '99999999999') {
		return false;
	 // Calcula os digitos verificadores para verificar se o
	 // CPF é válido
	 } else {   
		
		for ($t = 9; $t < 11; $t++) {
			
			for ($d = 0, $c = 0; $c < $t; $c++) {
				$d += $cpf{$c} * (($t + 1) - $c);
			}
			$d = ((10 * $d) % 11) % 10;
			if ($cpf{$c} != $d) {
				return false;
			}
		}

		return true;
	}
}


$fim = mktime (0, 0, 0, date('m'), date('d'), date('Y')); 
$inicio = $fim - 60*60*24;

$certificate = $DB->get_records_sql("select row_number() OVER (ORDER BY coursename, studentname) AS cod, DATE_FORMAT(FROM_UNIXTIME(dia2), '%Y%m%d') dia, cpf, upper(studentname) studentname2, cor, renda, coursename,
 instituicao, campus, responsavel, email,
CASE WHEN sum(concluido) > 0 THEN 1
     WHEN sum(reprovado) > 0 THEN 2
     WHEN sum(cancelado) > 0 THEN 0
END situacao from
(SELECT t5.courseid, c.fullname, t5.userid, cd3.value instituicao, cd4.value campus, cd5.value responsavel, cd6.value email,
concat(u.firstname, ' ', u.lastname) studentname, substring(replace(replace(replace(uid.data,'-',''),' ',''),'.',''),1,11) cpf, 
uid2.data cor, uid3.data renda, t5.dia dia2, concluido, reprovado, cancelado, scn.coursename
from {user} u, {user_info_data} uid, {user_info_data} uid2, {user_info_data} uid3, {course} c, {simplecertificate} scn , {simplecertificate_issues} scni,
	{customfield_field} cf3, {customfield_data} cd3, {customfield_category} cc3, 
	{customfield_field} cf4, {customfield_data} cd4, {customfield_category} cc4, 
	{customfield_field} cf5, {customfield_data} cd5, {customfield_category} cc5, 
	{customfield_field} cf6, {customfield_data} cd6, {customfield_category} cc6, 
(select distinct l.userid, l.courseid, l.timecreated dia, 0 reprovado, 1 cancelado, 0 concluido 
 from {logstore_standard_log} l WHERE l.timecreated between ".$inicio." and ".$fim." and l.action = 'deleted' and 
 l.target = 'user_enrolment' 
 union select sci.userid, sc.course, sci.timecreated dia, 0 reprovado, 0 cancelado, 1 concluido 
 from {simplecertificate} sc, {simplecertificate_issues} sci 
 where sc.id = sci.certificateid
 and sc.name like '%Certificado digital%' ) t5 WHERE t5.dia between ".$inicio." and ".$fim." and  uid.fieldid = 1 
					and uid2.fieldid = 3 and uid3.fieldid = 4 and trim(uid.data) <> '' and scn.name like 'Certificado digital'
					and t5.userid = u.id and t5.userid = uid.userid and u.id = uid.userid and u.id = uid2.userid 
					and u.id = uid3.userid AND u.id = scni.userid AND scni.certificateid = scn.id
					and t5.courseid = c.id and scn.course = t5.courseid
 and cf3.id = '2' and cc3.area = 'course' and cf3.id = cd3.fieldid and cc3.id = cf3.categoryid and cd3.instanceid = t5.courseid
 and cf4.id = '3' and cc4.area = 'course' and cf4.id = cd4.fieldid and cc4.id = cf4.categoryid and cd4.instanceid = t5.courseid
 and cf5.id = '5' and cc5.area = 'course' and cf5.id = cd5.fieldid and cc5.id = cf5.categoryid and cd5.instanceid = t5.courseid
 and cf6.id = '6' and cc6.area = 'course' and cf6.id = cd6.fieldid and cc6.id = cf6.categoryid and cd6.instanceid = t5.courseid
																	  ) t6
group by cpf, coursename, userid, studentname2, cor, renda, instituicao, campus, responsavel, email, dia");


$validos = 0;

foreach ($certificate as $c){
	if(validaCPF($c->cpf)){
		$data = new stdClass();
		$data->dia = $c->dia;
		$data->cpf = $c->cpf;
		$data->studentname = $c->studentname2;
		$data->cor = $c->cor;
		$data->renda = $c->renda;
		$data->coursename = $c->coursename;
		$data->email = $c->email;
		$data->instituicao = $c->instituicao;
		$data->campus = $c->campus;
		$data->situacao = $c->situacao;
		$data->timemodified = time();
		$idnovo = $DB->insert_record('ifrs_sistec', $data, true);
		$validos++;
	}
}

$user = get_complete_user_data('id', 6);
email_to_user($user, core_user::get_support_user(), 'Aprenda Mais: Sistec Diário', "Válidos: ".$validos, '', '', false);
