<?php
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

function get_all_active_courses()
{
	global $DB, $CFG;
    $courses = $DB->get_records_sql("select * from {$CFG->prefix}course where id > 1 and visible=1 order by fullname");
	$course_array = array();
	foreach($courses as $course)
	{
	    $course_array[] = $course;
	}
	return $course_array;
}
?>