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

//  Livetek Software Consulting Services custom code
//  Coder: Deepali Gujarathi
//  Contact: info@livetek.co.in
//  Date: 18 March 2013
//
//  Description: Allows admin to enrol one or more users into multiple courses at the same time.
//  Using this plugin allows admin to manage course enrolments from one screen itself

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

	require_once('../../config.php');
    //require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/enrol/locallib.php');
    require_once($CFG->dirroot.'/local/multiple_enrollments/lib.php');

	$default_role = $DB->get_record('role',array('shortname'=>'student'));

	admin_externalpage_setup('multipleenrollments');

    $roleid           = optional_param('roleid', $default_role->id, PARAM_INT); // required role id
    $userid           = optional_param('userid', 0, PARAM_INT); // needed for user tabs
    $courseid         = optional_param('courseid', 0, PARAM_RAW); // courseids are comma separated
    $courseenroll     = optional_param('courseenroll', null, PARAM_RAW);	//Action value
    $selecteduser     = optional_param_array('selecteduser', array(), PARAM_INT);	//courseids are comma separated
    $selectedcourse   = optional_param_array('selectedcourse', array(), PARAM_INT);	//userids are comma separated
    $enrol_duration   = optional_param('enrol_duration', 0, PARAM_INT);		// Value is between 1 to 365

    $errors = array();
    $title = get_string('multiple_enrollments_title','local_multiple_enrollments');
    $PAGE->set_pagelayout('admin');
    $PAGE->set_url(new moodle_url('/local/multiple_enrollments/assign_multiple_course.php'));
    $PAGE->set_title($title);
    $PAGE->requires->js('/local/multiple_enrollments/js/initajax.js');	//2nd arguement is required to include the JS in header instead of footer
	echo $OUTPUT->header();
	echo $OUTPUT->heading_with_help($title, 'multiple_enrollments_title','local_multiple_enrollments');

    if (!is_siteadmin()) {
        print_error('Access denied');
    }

	$debug_code = false;
	if(($courseenroll=="Enrol selected Users") && $selecteduser && $selectedcourse)	//Make sure that user and course are selected and action value is Enrollment
	{
		/*print_r($_POST);
		echo "<br><br>";
		print_r($selectedcourse);
		exit;
		*/

		$roleid = optional_param('userroles', null, PARAM_INT);
		$recovergrades = optional_param('recovergrades', 0, PARAM_INT);
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

		$debug_code = false;
		foreach($selectedcourse as $selectedcoursekey=>$courseid)
		{
			foreach($selecteduser as $selecteduserkey=>$enroluserid)
			{
				$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
				if($course->visible)
				{
					$instances = array();
					$context = get_context_instance(CONTEXT_COURSE, $courseid);
					$manager = new course_enrolment_manager($PAGE, $course);

					$course_enrol_instance = $DB->get_record('enrol', array('enrol'=>'manual','courseid'=>$courseid), '*', MUST_EXIST);
					$enrolid = $course_enrol_instance->id;
					//$enrolid = optional_param('enrolid', 1, PARAM_INT);
					if($debug_code) {
						echo "<br>enrolid1 = $enrolid";
					}
					//$userid = required_param('userid', PARAM_INT);


					$user = $DB->get_record('user', array('id'=>$enroluserid), '*', MUST_EXIST);
					$instances = $manager->get_enrolment_instances();
					if($debug_code) {
						echo "<br><br><pre>"; print_r($instances);
					}
					$plugins = $manager->get_enrolment_plugins();

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
							echo "<br />Came here for final enrolment";
						}
						$plugin->enrol_user($instance, $enroluserid, $roleid, $timestart, $timeend);
						if ($recovergrades) {
							$PAGE->set_context(context_system::instance());
							require_once($CFG->libdir.'/gradelib.php');
							grade_recover_history_grades($enroluserid, $instance->courseid);
						}
					} else {
						throw new enrol_ajax_exception('enrolnotpermitted');
					}
				}
			}
			//break;
		}
		if($debug_code) {
			echo "<br>All enrolments completed";
		}
		//exit;
	}

	/// prints a form to swap roles
	$roles_course = $DB->get_records('role');
	//print_simple_box_start('center');
	echo $OUTPUT->box_start();

	if($courseenroll == "Enrol selected Users" && $selecteduser && $selectedcourse)
	{
		echo '<div class="success_msg">';
		echo get_string('assignmessage',"local_multiple_enrollments");
		echo '</div><div class="separator"></div>';
	}

	include('menrol_form.html');
	echo $OUTPUT->box_end();

    echo $OUTPUT->footer();
?>