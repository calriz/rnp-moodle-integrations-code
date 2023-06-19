<?php
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Sao_Paulo');

require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot.'/lib/moodlelib.php');

global $DB;

if(is_siteadmin()){
	// tratar cpf
	$cpf = $_GET['cpf'];
	$email = $_GET['email'];
	
	$cpf = str_replace('.', '', $cpf);
	$cpf = str_replace('-', '', $cpf);
	
	
	$usercpf = $DB->get_records_sql("select * from {user_info_data} uid where replace(replace(data,'-',''),'.','') = '".$cpf."' and fieldid = '1'");
	if(count($usercpf)==1){
		$user = get_complete_user_data('id', current ($usercpf)->userid);
		$from = get_complete_user_data('id', 1);
		$firstname = $user->firstname;
		$espaco = (stripos($firstname, ' ') == 0) ? strlen($firstname) : stripos($firstname, ' ');
		$novasenha = chr(rand(65,90)).strtolower(chr(rand(65,90))).chr(rand(65,90)).strtolower(chr(rand(65,90)))."@".rand(1000,9999);

		update_internal_user_password($user, $novasenha);
		
		echo 'Login: '.$user->username.'<br>
		Senha: '.$novasenha.'<br><br>';
		
	}
	elseif(count($usercpf)==0){
		echo 'Não encontramos cadastro com este número de CPF.';
	}
	elseif(count($usercpf)>1){
		$usercpf = $DB->get_records_sql("select uid.id, uid.userid from {user_info_data} uid, {user} u where replace(replace(data,'-',''),'.','') = '".$cpf."' and fieldid = '1' and uid.userid = u.id and u.email = '".$email."'");
		if(count($usercpf)==1){
			$user = get_complete_user_data('id', current ($usercpf)->userid);
			$from = get_complete_user_data('id', 1);
			$firstname = $user->firstname;
			$espaco = (stripos($firstname, ' ') == 0) ? strlen($firstname) : stripos($firstname, ' ');
			$novasenha = chr(rand(65,90)).strtolower(chr(rand(65,90))).chr(rand(65,90)).strtolower(chr(rand(65,90)))."@".rand(1000,9999);

			update_internal_user_password($user, $novasenha);
			
			echo 'Login: '.$user->username.'<br>
			Senha: '.$novasenha.'<br><br>';
		}
		else{
			echo 'Localizamos duas contas com vinculadas a este CPF, mas que os endereços de email não conferem com o endereço fornecido por você. Tente utilizar outra conta de e-mail.';
		}		
	}

}	
?>