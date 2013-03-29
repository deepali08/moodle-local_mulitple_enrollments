<?php
if (!$ADMIN->locate('livetek_addons'))
{
	$ADMIN->add('root', new admin_category('livetek_addons', get_string('livetek_addons', 'local_multiple_enrollments')));
}
$ADMIN->add('livetek_addons', new admin_externalpage('multipleenrollments', get_string('multipleenrollment','local_multiple_enrollments'), "$CFG->wwwroot/local/multiple_enrollments/index.php",array('moodle/user:update', 'moodle/user:delete')));
?>