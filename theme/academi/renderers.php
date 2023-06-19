<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderer file required.
 * @package    theme_academi
 * @copyright  2015 onwards LMSACE Dev Team (http://www.lmsace.com)
 * @author    LMSACE Dev Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once('renderers/core_renderer.php');
require_once('renderers/course_renderer.php');



include_once($CFG->dirroot."/mod/quiz/renderer.php");

class theme_academi_mod_quiz_renderer extends mod_quiz_renderer {

	public function review_page(quiz_attempt $attemptobj, $slots, $page, $showall, $lastpage, mod_quiz_display_options $displayoptions, $summarydata) {
			GLOBAL $USER, $DB, $COURSE;
			//echo '<br><Br><Br>'.$USER->id;
			// Number of allowed attempt for the quiz
			$allowedAttempt = $attemptobj->get_num_attempts_allowed();

			// current attempt #
			$currentAttempt = $attemptobj->get_attempt_number();

			// cmid
			$cmid = $attemptobj->get_cmid();
			$tentativas = $DB->get_record_sql("select q.id, count(1) tot
			from mdl_quiz q, mdl_quiz_attempts qa, mdl_course_modules cm
			where q.id = qa.quiz and q.id = cm.instance and cm.id = '".$cmid."' and qa.userid = '".$USER->id."' and qa.state = 'finished'
			and cm.module = 17 group by q.id");
			$tentativasFinalizadas = $tentativas->tot;
			
			$certificado = $DB->get_record_sql("select count(distinct sci.userid) tot
	from mdl_course c, mdl_simplecertificate sc, mdl_simplecertificate_issues sci
	where c.id = sc.course and sci.certificateid = sc.id and sci.userid = '".$USER->id."' and sc.course = '".$COURSE->id."' and lower(sc.name) like 'certificado digital'");
			$certificadoEmitido = $certificado->tot;

			// Display the correct answer 
			if (($allowedAttempt == $tentativasFinalizadas) || ($certificadoEmitido == 1)) {
				$displayoptions->rightanswer = 1;
			} else {
				$displayoptions->rightanswer = 0;
			}

			return parent::review_page($attemptobj, $slots, $page, $showall, $lastpage, $displayoptions, $summarydata);
	}

}