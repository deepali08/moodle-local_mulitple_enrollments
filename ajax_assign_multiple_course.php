<?PHP
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
 * Multiple Enrollments - Allows admin to enrol one or more users into multiple courses at the same time.
 *                        There is a single screen which allows admin to manage course enrolments.
 *
 * @package      local
 * @subpackage   multiple_enrollments
 * @maintainer   Livetek Software Consulting Services
 * @author       Deepali Gujarathi
 * @contact      info@livetek.co.in
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

	define('AJAX_SCRIPT', true);
	require_once('../../config.php');
    //require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/enrol/locallib.php');
    require_once($CFG->dirroot.'/local/multiple_enrollments/lib.php');
	set_time_limit(0);

	$enrol_action = optional_param('enrollmenttype', null, PARAM_RAW);
	$roleid = optional_param('roleid', 0, PARAM_INT);
	$recovergrades = optional_param('recovergrades', 0, PARAM_INT);
 	$PAGE->set_url(new moodle_url('/local/multiple_enrollments/assign_multiple_course.php'));

	if($enrol_action == "newenrollment")
	{
		$availableusers = $DB->get_records_sql("SELECT distinct u.id, u.firstname, u.lastname, u.email
		FROM mdl_user u
		LEFT JOIN mdl_role_assignments ra ON ra.userid = u.id
		WHERE u.deleted =0
		AND u.username <> 'guest'
		AND u.confirmed =1
		AND u.suspended =0
		AND ra.userid IS NULL
		ORDER BY u.firstname
		");

		//$courses = get_courses('all','c.fullname asc');
		$courses = get_all_active_courses();
?>
<div>
<?php
		echo '<div style="width:100%; float:left; border:0px solid #0033FF;margin-top:20px;">
		<div class="coursesection">'.
		 get_string("coursesrole","local_multiple_enrollments").'
		</div>
		<div class="usersection">
			'.get_string("roleuser","local_multiple_enrollments").'
		</div>
	</div>
	<div style="width:100%; float:left; border:0px solid #0033FF">
		<div class="coursesection">
			<select name="selectedcourse[]" size="20" id="selectedcourse" multiple="multiple">';

				foreach($courses as $course)
				{
					echo '<option value="'.$course->id.'">'.$course->fullname.' ('.$course->shortname.')</option>';
				}

		echo '</select>
		</div>
		<div class="usersection">
			<select name="selecteduser[]" size="20" id="selecteduser" multiple="multiple">';

				foreach($availableusers as $auser)
				{
					echo '<option value="'.$auser->id.'">'.$auser->firstname." ".$auser->lastname."(". $auser->email.')</option>';

				}
			echo '</select>
		</div>
	</div>
	<div class="separator">'. get_string("select_multiple_options","local_multiple_enrollments").'</div>
	<div style="width:100%; float:left; border:0px solid #0033FF; margin-top:20px;">
		<input type="submit" name="courseenroll" value="Enrol selected Users" onclick="return validation();">
		<input type="reset" name="reset" value="Reset">
	</div>
	';
	}
	else
	{
		 $euserid = optional_param('userid', null, PARAM_INT);
		 $selected_courses = optional_param('courseid', null, PARAM_RAW);
		 $enrol_action = optional_param('enrollmenttype', null, PARAM_RAW);
		 $enrol_duration = optional_param('enrol_duration', 0, PARAM_INT);
		 //$user_id = $euserid;
		 $debug_code = false;

		 //For Click on Add Button start here
		 $selected_courses = rtrim($selected_courses,',');
		 if($euserid && $selected_courses)
		 {
			//Start time
			$today = time();
			$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
			$timestart = $today;

			//End time
			if ($enrol_duration <= 0) {
				$timeend = 0;
			} else {
				$timeend = $timestart + ($enrol_duration*24*60*60);
			}

			 $selected_course_array = explode(",",$selected_courses);
			 //print_r($_REQUEST);
			 //print_r($selected_course_array);
			 for($i=0; $i < sizeof($selected_course_array); $i++)
			 {
				$action_course_id = $selected_course_array[$i];
				if($course = $DB->get_record('course', array('id' => $action_course_id)))
				{
					//$course_context=get_record('context','instanceid',$selected_course_array[$i]);
					$context = get_context_instance(CONTEXT_COURSE, $action_course_id);
					if($context)
					{
						//$role_assignment_ajax->contextid = $course_context->id;
						if($enrol_action == "add")
						{
							//echo "<br>Case Add course";
							$manager = new course_enrolment_manager($PAGE, $course);

							$course_enrol_instance = $DB->get_record('enrol', array('enrol'=>'manual','courseid'=>$action_course_id), '*', MUST_EXIST);
							$enrolid = $course_enrol_instance->id;
							if($debug_code) {
								echo "<br>enrolid1 = $enrolid";
							}
							//$userid = required_param('userid', PARAM_INT);

							//$roleid = optional_param('userroles', null, PARAM_INT);

							$user = $DB->get_record('user', array('id'=>$euserid), '*', MUST_EXIST);
							$instances = $manager->get_enrolment_instances();
							if($debug_code) {
								echo "<br><br><pre>"; print_r($instances);
							}
							$plugins = $manager->get_enrolment_plugins();

							//echo "<br><br><pre>"; print_r($instances_array);
							if (!array_key_exists($enrolid, $instances)) {
								throw new enrol_ajax_exception('invalidenrolinstance');
							}
							$instance = $instances[$enrolid];
							if($debug_code) {
								echo "<br><br><pre>"; print_r($instance);
							}
							$plugin = $plugins[$instance->enrol];
							if ($plugin->allow_enrol($instance) && has_capability('enrol/'.$plugin->get_name().':enrol', $context)) {
								if($debug_code) {
									echo "<br />Came here for final enrolment: euserid=$euserid, roleid=$roleid, timestart=$timestart";
								}
								$plugin->enrol_user($instance, $euserid, $roleid, $timestart, $timeend);
								if ($recovergrades) {
									$PAGE->set_context(context_system::instance());
									require_once($CFG->libdir.'/gradelib.php');
									grade_recover_history_grades($euserid, $instance->courseid);
								}
							} else {
								throw new enrol_ajax_exception('enrolnotpermitted');
							}


						}
						elseif($enrol_action == "remove")
						{
							//echo "<br>Case Unenrol course";
							$check_enrol_sql = "select ue.* from {$CFG->prefix}user_enrolments ue left join {$CFG->prefix}enrol e on ue.enrolid=e.id where ue.userid=$euserid and e.enrol='manual' and e.courseid='$action_course_id'";
							//echo "<br>$check_enrol_sql";
							$ue = $DB->get_record_sql($check_enrol_sql);
							$user = $DB->get_record('user', array('id'=>$ue->userid), '*', MUST_EXIST);
							$instance = $DB->get_record('enrol', array('id'=>$ue->enrolid), '*', MUST_EXIST);
							//echo "<pre>Instance -> "; print_r($instance);
							$plugin = enrol_get_plugin($instance->enrol);

							if (!$plugin->allow_unenrol_user($instance, $ue) or !has_capability("enrol/$instance->enrol:unenrol", $context)) {
								print_error('erroreditenrolment', 'enrol');
							}
							$plugin->unenrol_user($instance, $ue->userid);
							//echo "<br>Course unenroll Done";
						}
						//print_r($role_assignment_ajax);
					}
					//insert_record('role_assignments',$role_assignment_ajax);
				}
			 }
		 }
	//End add button Here


	//Remove Button End Here
	//$temp_courses = enrol_get_my_courses($user_id, 'sortorder ASC ');

	//print_r($temp_courses);

	$temp_courses_array = array();
	//$all_courses = get_courses('all','c.fullname asc');
	$all_courses = get_all_active_courses();
	$temp_courses = enrol_get_users_courses($euserid, 'sortorder ASC ');

	$availableusers = $DB->get_records_sql("SELECT distinct u.id, u.firstname, u.lastname, u.email
	FROM mdl_user u
	INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
	WHERE u.deleted =0
	AND u.username <> 'guest'
	AND u.confirmed =1
	AND u.suspended =0
	AND ra.roleid = '$roleid'
	ORDER BY u.firstname
	");

	echo '<div style="width:100%; float:left; margin-top:20px;">
		<div class="label_div">'.
		 get_string('selectuser','local_multiple_enrollments').'
	</div>
		<div>
			<select name="userid" id="userid" onchange="javascript:enrollment_type()">
				<option value="">Select user</option>';
				foreach($availableusers as $auser)
				{
					if($euserid == $auser->id)
					{
						$selectuser = " selected";
					}
					else
					{
						$selectuser =" ";
					}
					echo '<option value="'.$auser->id.'" '.$selectuser.'>'.$auser->firstname." ".$auser->lastname."(". $auser->email.')</option>';

				}
			echo '</select>
		</div>

	</div>

	<div class="separator"></div>
	<div class="fulldiv separator">
		<div class="assignedcourse">'.get_string("existingcourse","local_multiple_enrollments").'&nbsp;&nbsp;<input name="remove" id="remove" type="button" value='. get_string("munenrol_course",'local_multiple_enrollments').' onclick="javascript:enrollment_type(\'remove\')" />
		</div>
		<div class="potentialcourse">'.get_string("potentialcourse","local_multiple_enrollments").'&nbsp;&nbsp;<input name="add" id="add" type="button" value='.get_string("multiple_enrollments_course",'local_multiple_enrollments').' onclick="javascript:enrollment_type(\'add\')" />
		</div>
	</div>

	<div class="fulldiv">
		<div class="assignedcourse">
			<select name="removecourseselect[]" size="20" id="removecourseselect" multiple="multiple">';
				foreach($temp_courses as $course)
				{
					$temp_courses_array[]=$course->id;
					echo '<option value="'.$course->id.'">'.$course->fullname.'('.$course->shortname.')</option>';

				}
			echo '</select>
		</div>
		<div class="potentialcourse">
			<select name="addcourseselect[]" size="20" id="addcourseselect" multiple="multiple">';
				foreach($all_courses as $all_course)
				{
					if(!in_array($all_course->id,$temp_courses_array))
					{
						echo '<option value="'.$all_course->id.'">'.$all_course->fullname.' ('.$all_course->shortname.')</option>';
					}
				}
			echo '</select>
		</div>
	</div>
	<div class="separator">'. get_string("select_multiple_options","local_multiple_enrollments").'</div>';
}
?>