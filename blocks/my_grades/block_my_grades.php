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
 * My Grades Block.
 *
 * @package   block_my_grades
 * @author    Karen Holland <kholland.dev@gmail.com>, Mei Jin, Jiajia Chen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/gradelib.php");
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once $CFG->dirroot.'/grade/report/overview/lib.php';
require_once $CFG->dirroot.'/grade/lib.php';

class block_my_grades extends block_base {
	public function init() {
		$this->title = get_string('my_grades', 'block_my_grades');
	}

	public function get_content() {
		global $DB, $USER, $COURSE;

		if ($this->content !== null) {
			return $this->content;
		}

		$this->content	 =  new stdClass;
		$this->content->text = "";

		/// return tracking object
		$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'overview', 'courseid'=>$COURSE->id, 'userid'=>$USER->id));

		// Create a report instance
		$context = context_course::instance($COURSE->id);
		
		$inscricao = $DB->get_record_sql("select ue.timestart
					from {user_enrolments} ue, {enrol} e
					where e.id = ue.enrolid and e.courseid = '".$COURSE->id."' and e.enrol = 'self' and ue.userid = '".$USER->id."' order by ue.timestart desc ");
		$this->content->text = "Minha inscrição: <b>".userdate($inscricao->timestart, get_string('strftimedaydatetime', 'core_langconfig')).'</b><br><br>';
		
		$certificado = $DB->get_record('customfield_data', array('fieldid'=>1, 'instanceid'=>$COURSE->id), 'value', MUST_EXIST);
				
		$ch[4] = 0;
		$ch[10] = 0;
		$ch[20] = 4;
		$ch[25] = 4;
		$ch[30] = 5;
		$ch[40] = 6;
		$ch[45] = 7;
		$ch[50] = 7;
		$ch[60] = 8;
		$ch[70] = 8;
		$ch[80] = 11;
		$ch[90] = 13;
		$ch[100] = 14;
		$ch[200] = 28;
		$ch[400] = 50;
		
		if($ch[$certificado->value]==0)
			$dias = (1+(ceil($certificado->value/8)));
		else
			$dias = $ch[$certificado->value];
		$this->content->text .= "Emissão do certificado a partir de: <b>".userdate(($inscricao->timestart+($dias*60*60*24)), get_string('strftimedaydatetime', 'core_langconfig')).'</b><br><br>';
		
		$this->content->text .= "Prazo final para conclusão do curso e geração do certificado: <b>".userdate($COURSE->enddate, get_string('strftimedaydatetime', 'core_langconfig')).'</b><br><br>';
		
		
		$medias2 = $DB->get_record_sql("select gg.id, gi.itemname, gi.gradepass, gg.finalgrade
					from {grade_items} gi, {grade_grades} gg
					where gi.courseid = '".$COURSE->id."' and gi.itemtype = 'course' and gg.itemid = gi.id and gg.userid = '".$USER->id."'");
		if($medias2>0)
			$this->content->text .= 'Minha média atual: <b>'.round($medias2->finalgrade,2).'%</b>';
		else
			$this->content->text .= 'Minha média atual: você não tem notas registradas';
		
		
		$via2 = $DB->get_record_sql("select sc.id
					from {simplecertificate} sc, {simplecertificate_issues} sci
					where sc.id = sci.certificateid and sc.course = '".$COURSE->id."' and sci.userid = '".$USER->id."'");
		if($via2->id>0)
			$this->content->text .= '<br><br>'.html_writer::link(new moodle_url('/blocks/simple_certificate/view.php', array('cid' => 1)), 'Obter 2a via do certificado aqui');
			
		/*$report = new grade_report_overview($USER->id, $gpr, $context);

		$newdata=$this->grade_data($report);
		if (is_array($newdata))
		{
			if (count($newdata)>0)
			{
				$newtext ="";
				//$newtext="<table class=\"grades\"><tr><th>".get_string('gradetblheader_course', 'block_my_grades')."</th><th>".get_string('gradetblheader_grade', 'block_my_grades')."</th></tr>";
				foreach($newdata as $newgrade)
				{
					$pos = strpos($newgrade[0], "id=".$COURSE->id."&");
					if ($pos > 0){
						// need to put data into table for display here
						$newtext .= '<h1>'.$newgrade[1].'</h1>';
						//$newtext.="<tr><td class=\"block_".$this->name()."_link\">{$newgrade[0]}</td>".
						//"<td class=\"block_".$this->name()."_grade\">{$newgrade[1]}</td></tr>";
					}

				}
				//$newtext.="</table>";
				$this->content->text.=$newtext;
			}
		}
		else
		{
			$this->content->text.=$newdata;
		}*/

		return $this->content;
	}

	public function instance_allow_multiple() {
		return false;
	}

	public function html_attributes() {
		$attributes = parent::html_attributes(); // Get default values
		$attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute
		return $attributes;
	}

	public function grade_data($report) {
		global $CFG, $DB, $OUTPUT, $USER;
		$data = array();

		if ($courses = enrol_get_users_courses($report->user->id, false, 'id, shortname, showgrades')) {
			$numusers = $report->get_numusers(false);

			foreach ($courses as $course) {
				if (!$course->showgrades) {
					continue;
				}

				$coursecontext = context_course::instance($course->id);

				if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
					// The course is hidden and the user isn't allowed to see it
					continue;
				}

				$courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
				$courselink = html_writer::link(new moodle_url('/grade/report/user/index.php', array('id' => $course->id, 'userid' => $report->user->id)), $courseshortname);
				$canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);

				// issue with hidden grades, migrating to user viewable results
				// Get course grade_item
				$course_item = grade_item::fetch_course_item($course->id);

				// Get the stored grade
				$course_grade = new grade_grade(array('itemid'=>$course_item->id, 'userid'=>$USER->id));
				$course_grade->grade_item =& $course_item;
				$finalgrade = $course_grade->finalgrade;

				if (!$canviewhidden and !is_null($finalgrade)) {
					if ($course_grade->is_hidden()) {
						$finalgrade = null;
					} else {
						$finalgrade = block_my_grades::blank_hidden_total($report, $course->id, $course_item, $finalgrade);
					}
				}

				$data[] = array($courselink, grade_format_gradevalue($finalgrade, $course_item, true));
			}

			if (count($data)==0) {
				return $OUTPUT->notification(get_string('nocourses', 'grades'));
			} else {
				return $data;
			}
		} else {
			return $OUTPUT->notification(get_string('nocourses', 'grades'));
		}
	}

	/**
	 * Optionally blank out course/category totals if they contain any hidden items
	 * @param string $courseid the course id
	 * @param string $course_item an instance of grade_item
	 * @param string $finalgrade the grade for the course_item
	 * @return string The new final grade
	 */
	public function blank_hidden_total($report, $courseid, $course_item, $finalgrade) {
		global $CFG, $DB;
		static $hiding_affected = null;//array of items in this course affected by hiding

		// If we're dealing with multiple users we need to know when we've moved on to a new user.
		static $previous_userid = null;

		// If we're dealing with multiple courses we need to know when we've moved on to a new course.
		static $previous_courseid = null;

		if (!is_array($report->showtotalsifcontainhidden)) {
			debugging('showtotalsifcontainhidden should be an array', DEBUG_DEVELOPER);
			$report->showtotalsifcontainhidden = array($courseid => $report->showtotalsifcontainhidden);
		}


		if ($report->showtotalsifcontainhidden[$courseid] == GRADE_REPORT_SHOW_REAL_TOTAL_IF_CONTAINS_HIDDEN) {
			return $finalgrade;
		}

		// If we've moved on to another course or user, reload the grades.
		if ($previous_userid != $report->user->id || $previous_courseid != $courseid) {
			$hiding_affected = null;
			$previous_userid = $report->user->id;
			$previous_courseid = $courseid;
		}

		if( !$hiding_affected ) {
			$items = grade_item::fetch_all(array('courseid'=>$courseid));
			$grades = array();
			$sql = "SELECT g.*
					  FROM {grade_grades} g
					  JOIN {grade_items} gi ON gi.id = g.itemid
					 WHERE g.userid = {$report->user->id} AND gi.courseid = {$courseid}";
			if ($gradesrecords = $DB->get_records_sql($sql)) {
				foreach ($gradesrecords as $grade) {
					$grades[$grade->itemid] = new grade_grade($grade, false);
				}
				unset($gradesrecords);
			}
			foreach ($items as $itemid=>$unused) {
				if (!isset($grades[$itemid])) {
					$grade_grade = new grade_grade();
					$grade_grade->userid = $report->user->id;
					$grade_grade->itemid = $items[$itemid]->id;
					$grades[$itemid] = $grade_grade;
				}
				$grades[$itemid]->grade_item =& $items[$itemid];
			}
			$hiding_affected = grade_grade::get_hiding_affected($grades, $items);
		}

		//if the item definitely depends on a hidden item
		if (array_key_exists($course_item->id, $hiding_affected['altered'])) {
			if( !$report->showtotalsifcontainhidden[$courseid] ) {
				//hide the grade
				$finalgrade = null;
			}
			else {
				//use reprocessed marks that exclude hidden items
				$finalgrade = $hiding_affected['altered'][$course_item->id];
			}
		} else if (!empty($hiding_affected['unknown'][$course_item->id])) {
			//not sure whether or not this item depends on a hidden item
			if( !$report->showtotalsifcontainhidden[$courseid] ) {
				//hide the grade
				$finalgrade = null;
			}
			else {
				//use reprocessed marks that exclude hidden items
				$finalgrade = $hiding_affected['unknown'][$course_item->id];
			}
		}

		return $finalgrade;
	}
}

?>
