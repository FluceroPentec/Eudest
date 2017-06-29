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
 * COMPONENT External functions unit tests
 *
 * @package    local_eudest
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(__DIR__. '/../local_eudest.php');

/**
 * This class is used to run the unit tests
 *
 * @package    local_eudest
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudest_testcase extends advanced_testcase {

    /**
     * Call protected/private method of a class.
     *
     * @param object $object    Instantiated object that we will run method on.
     * @param string $methodname Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invoke_method (&$object, $methodname, array $parameters = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get a property for an object.
     * @param object $obj Object owner of the property.
     * @param string $prop Property to get.
     * @return object Value of the property
     */
    public function access_protected ($obj, $prop) {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * Set a property in an object.
     * @param object $obj Object owner of the property.
     * @param string $prop Property to set.
     * @param object $value Value to set
     */
    public function set_protected ($obj, $prop, $value) {
        $reflection = new ReflectionClass($obj);
        $reflectionproperty = $reflection->getProperty($prop);
        $reflectionproperty->setAccessible(true);
        $reflectionproperty->setValue($obj, $value);
    }
    /**
     * Enable the manual enrol plugin.
     *
     * @return bool $manualplugin Return true if is enabled.
     */
    public function enable_enrol_plugin() {
        $manualplugin = enrol_get_plugin('manual');
        return $manualplugin;
    }

    /**
     * Get Student object.
     *
     * @return stdClass $studentrole Object student role record.
     */
    public function get_student_role() {
        global $DB;
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        return $studentrole;
    }

    /**
     * Get Teacher object.
     *
     * @return stdClass $teacherrole Object teacher role record.
     */
    public function get_teacher_role() {
        global $DB;
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        return $teacherrole;
    }

    /**
     * Create manual instance to enrol in a course.
     * @param int $courseid Course id.
     *
     * @return stdClass $manualinstance Object type of enrol to be enrolled.
     */
    public function create_manual_instance($courseid) {
        global $DB;
        $manualinstance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => 'manual'), '*', MUST_EXIST);
        return $manualinstance;
    }

    /**
     * Tests if configuration can be loaded
     */
    public function test_eude_load_configuration () {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a few instances of the local_eudest class.
        $instance1 = new local_eudest();

        // Gonna insert initial data in the db.
        $record = new stdClass();
        $record->id = 1;
        $record->last_enrolid = 15;
        $record->last_inactivity_date = 700000;
        $record->last_califications_date = 10000000;
        $DB->insert_record('local_eudest_config', $record);

        // Testing the function with instance 1.
        $this->invoke_method($instance1, 'eude_load_configuration', array());
        $result = $this->access_protected($instance1, 'eudeconfig');
        $this->assertEquals(15, $result->last_enrolid);
        $this->assertEquals(700000, $result->last_inactivity_date);
        $this->assertEquals(10000000, $result->last_califications_date);

        // Testing the function with the data reseted.
        $this->resetAllData();
        $this->invoke_method($instance1, 'eude_load_configuration', array());
        $result = $this->access_protected($instance1, 'eudeconfig');
        $this->assertEquals(0, $result->last_enrolid);
        $this->assertEquals(0, $result->last_inactivity_date);
        $this->assertEquals(0, $result->last_califications_date);
    }
    /**
     * Tests if a course is intensive.
     */
    public function test_eude_module_is_intensive() {
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating courses.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M01', 'category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.CAT1.M01', 'category' => $category1->id));

        // Test1: use course 1 and check for result (should be 0).
        $result = $this->invoke_method($instance1, 'eude_module_is_intensive', array($course1->shortname));
        $this->assertEquals(0, $result);

        // Test2: use course 2 and check for result (should be 1).
        $result = $this->invoke_method($instance1, 'eude_module_is_intensive', array($course2->shortname));
        $this->assertEquals(1, $result);
    }

    /**
     * Tests if a course allows to a master.
     */
    public function test_eude_module_allows_to_master() {
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating courses.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M01', 'category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.CAT1.M01', 'category' => $category1->id));
        $course3 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.01', 'category' => $category1->id));
        $course4 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.CAT1.01', 'category' => $category1->id));

        // Test1: Use course1 (should return 1).
        $result = $this->invoke_method($instance1, 'eude_module_allows_to_master', array($course1->shortname));
        $this->assertEquals(1, $result);

        // Test2: Use course2 (should return 1).
        $result = $this->invoke_method($instance1, 'eude_module_allows_to_master', array($course2->shortname));
        $this->assertEquals(1, $result);

        // Test3: Use course3 (should return 0).
        $result = $this->invoke_method($instance1, 'eude_module_allows_to_master', array($course3->shortname));
        $this->assertEquals(0, $result);

        // Test4: Use course4 (should return 0).
        $result = $this->invoke_method($instance1, 'eude_module_allows_to_master', array($course4->shortname));
        $this->assertEquals(0, $result);
    }

    /**
     * Tests if a course is convalidable.
     */
    public function test_eude_module_is_convalidable() {
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating courses.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M01[-1-]', 'category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M02[-', 'category' => $category1->id));
        $course3 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M02', 'category' => $category1->id));

        // Test1: Use course1 (should return 1).
        $result = $this->invoke_method($instance1, 'eude_module_is_convalidable', array($course1->shortname));
        $this->assertEquals(1, $result);

        // Test2: Use course2 (should return 0).
        $result = $this->invoke_method($instance1, 'eude_module_is_convalidable', array($course2->shortname));
        $this->assertEquals(0, $result);

        // Test3: use course 3 (should return 0).
        $result = $this->invoke_method($instance1, 'eude_module_is_convalidable', array($course3->shortname));
        $this->assertEquals(0, $result);

    }

    /**
     * Tests if an enrolment can be saved.
     */
    public function test_eude_save_enrolment_instance() {
        global $DB;
        $this->resetAfterTest(true);

        // Creating users.
        $user1 = $this->getDataGenerator()->create_user(
                array('username' => 'user1'));

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating categories.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));

        // Creating courses.
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M01', 'category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.CAT1.M02', 'category' => $category1->id));

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $normalstart = 333333;
        $normalend = 666666;
        $intensivestart = 555555;
        $intensiveend = 777777;
        // Enrolling the user in both courses.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id, 'manual', $normalstart, $normalend);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $studentrole->id, 'manual', $intensivestart, $intensiveend);

        // Test1: enrolment normal course.
        // Creating param object.
        $paramobject = new stdClass();
        $paramobject->userid = $user1->id;
        $paramobject->courseid = $course1->id;
        $paramobject->shortname = $course1->shortname;
        $paramobject->category = $course1->category;
        $paramobject->timestart = $normalstart;
        $paramobject->timeend = $normalend;

        $intensive = 0;
        $pdteencap = 1;
        $pdteconv = 0;

        $this->invoke_method($instance1, 'eude_save_enrolment_instance',
                array($paramobject, $intensive, $pdteencap, $pdteconv));

        $result = $DB->get_record('local_eudest_enrols', array('userid' => $user1->id, 'courseid' => $course1->id));

        // Creating expected object.
        $expected = new stdClass();
        $expected->userid = $user1->id;
        $expected->courseid = $course1->id;
        $expected->shortname = $course1->shortname;
        $expected->categoryid = $course1->category;
        $expected->startdate = $normalstart;
        $expected->enddate = $normalend;
        $expected->pend_event = 1;
        $expected->pend_encapsulation = $pdteencap;
        $expected->pend_convalidation = $pdteconv;
        $expected->intensive = $intensive;
        $expected->masterid = 0;

        $this->assertEquals($expected->userid, $result->userid);
        $this->assertEquals($expected->courseid, $result->courseid);
        $this->assertEquals($expected->shortname, $result->shortname);
        $this->assertEquals($expected->categoryid, $result->categoryid);
        $this->assertEquals($expected->startdate, $result->startdate);
        $this->assertEquals($expected->enddate, $result->enddate);
        $this->assertEquals($expected->pend_event, $result->pend_event);
        $this->assertEquals($expected->pend_encapsulation, $result->pend_encapsulation);
        $this->assertEquals($expected->pend_convalidation, $result->pend_convalidation);
        $this->assertEquals($expected->intensive, $result->intensive);
        $this->assertEquals($expected->masterid, $result->masterid);

        // Test2: Use intensive course.
        // Creating param object.
        $paramobject = new stdClass();
        $paramobject->userid = $user1->id;
        $paramobject->courseid = $course2->id;
        $paramobject->shortname = $course2->shortname;
        $paramobject->category = $course2->category;
        $paramobject->timestart = $intensivestart;
        $paramobject->timeend = $intensiveend;

        $intensive = 1;
        $pdteencap = 0;
        $pdteconv = 0;

        $this->invoke_method($instance1, 'eude_save_enrolment_instance',
                array($paramobject, $intensive, $pdteencap, $pdteconv));

        $result2 = $DB->get_record('local_eudest_enrols', array('userid' => $user1->id, 'courseid' => $course2->id));

        // Creating expected object.
        $expected2 = new stdClass();
        $expected2->userid = $user1->id;
        $expected2->courseid = $course2->id;
        $expected2->shortname = $course2->shortname;
        $expected2->categoryid = $course2->category;
        $expected2->startdate = $intensivestart;
        $expected2->enddate = $intensiveend;
        $expected2->pend_event = 1;
        $expected2->pend_encapsulation = $pdteencap;
        $expected2->pend_convalidation = $pdteconv;
        $expected2->intensive = $intensive;
        $expected2->masterid = 0;

        $this->assertEquals($expected2->userid, $result2->userid);
        $this->assertEquals($expected2->courseid, $result2->courseid);
        $this->assertEquals($expected2->shortname, $result2->shortname);
        $this->assertEquals($expected2->categoryid, $result2->categoryid);
        $this->assertEquals($expected2->startdate, $result2->startdate);
        $this->assertEquals($expected2->enddate, $result2->enddate);
        $this->assertEquals($expected2->pend_event, $result2->pend_event);
        $this->assertEquals($expected2->pend_encapsulation, $result2->pend_encapsulation);
        $this->assertEquals($expected2->pend_convalidation, $result2->pend_convalidation);
        $this->assertEquals($expected2->intensive, $result2->intensive);
        $this->assertEquals($expected2->masterid, $result2->masterid);
    }

    /**
     * Tests the copy of moodle enrolments into own table.
     */
    public function test_eude_register_enrolments() {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating users.
        $user1 = $this->getDataGenerator()->create_user(
                array('username' => 'user1'));

        // Creating categories.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));

        // Creating courses.
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M01', 'category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.CAT1.M02', 'category' => $category1->id));
        $course3 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M03', 'category' => $category1->id));
        $course4 = $this->getDataGenerator()->create_course(
                array('shortname' => 'CAT1.M04', 'category' => $category1->id));

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $normalstart = 333333;
        $normalend = 666666;
        $intensivestart = 555555;
        $intensiveend = 777777;

        // Enrolling the user in both courses.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id, 'manual', $normalstart, $normalend);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $studentrole->id, 'manual', $intensivestart, $intensiveend);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id, $studentrole->id, 'manual', $normalstart, $normalend);
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id, $studentrole->id, 'manual', $normalstart, $normalend);

        // Get last enrolment id.
        $sql = 'SELECT id
                  FROM {user_enrolments}
              ORDER BY id DESC
                 LIMIT 1';

        $lastid = $DB->get_record_sql($sql, array());

        // Test1: Set lastid attribute equal to last enrolment id and check if function cant process any enrolment.
        $eudeconfig = new stdClass();
        $eudeconfig->id = 0;
        $eudeconfig->last_enrolid = $lastid->id;
        $eudeconfig->last_inactivity_date = 0;
        $eudeconfig->last_califications_date = 0;

        $this->set_protected($instance1, 'eudeconfig', $eudeconfig);
        $result = $this->invoke_method($instance1, 'eude_register_enrolments', array());

        // Try to find any record in local_eudest_enrols (should be 0).
        $query = $DB->get_records('local_eudest_enrols', array('userid' => $user1->id));
        $this->assertCount(0, $query);

        // Test2: Set lastid attribute = last enrolment -2 and check there are two enrolments processed.
        $eudeconfig = new stdClass();
        $eudeconfig->id = 0;
        $eudeconfig->last_enrolid = ($lastid->id) - 2;
        $eudeconfig->last_inactivity_date = 0;
        $eudeconfig->last_califications_date = 0;

        $this->set_protected($instance1, 'eudeconfig', $eudeconfig);
        $result = $this->invoke_method($instance1, 'eude_register_enrolments', array());
        $query = $DB->get_records('local_eudest_enrols', array('userid' => $user1->id));

        $this->assertCount(2, $query);

        $intensive = 0;
        $pdteencap = 1;
        $pdteconv = 0;

        $enrol1 = new stdClass();
        $enrol1->userid = $user1->id;
        $enrol1->courseid = $course3->id;
        $enrol1->shortname = $course3->shortname;
        $enrol1->categoryid = $course3->category;
        $enrol1->startdate = "$normalstart";
        $enrol1->enddate = "$normalend";
        $enrol1->pend_event = 1;
        $enrol1->pend_encapsulation = $pdteencap;
        $enrol1->pend_convalidation = $pdteconv;
        $enrol1->intensive = $intensive;
        $enrol1->masterid = 0;

        $enrol2 = new stdClass();
        $enrol2->userid = $user1->id;
        $enrol2->courseid = $course4->id;
        $enrol2->shortname = $course4->shortname;
        $enrol2->categoryid = $course4->category;
        $enrol2->startdate = "$normalstart";
        $enrol2->enddate = "$normalend";
        $enrol2->pend_event = 1;
        $enrol2->pend_encapsulation = $pdteencap;
        $enrol2->pend_convalidation = $pdteconv;
        $enrol2->intensive = $intensive;
        $enrol2->masterid = 0;

        $enrol1id = $DB->get_record('local_eudest_enrols', array('courseid' => $course3->id));
        $enrol2id = $DB->get_record('local_eudest_enrols', array('courseid' => $course4->id));

        $expected = [$enrol1id->id => $enrol1, $enrol2id->id => $enrol2];

        $this->assertEquals($expected[$enrol1id->id]->courseid, $query[$enrol1id->id]->courseid);
        $this->assertEquals($expected[$enrol1id->id]->shortname, $query[$enrol1id->id]->shortname);
        $this->assertEquals($expected[$enrol1id->id]->categoryid, $query[$enrol1id->id]->categoryid);
        $this->assertEquals($expected[$enrol1id->id]->startdate, $query[$enrol1id->id]->startdate);
        $this->assertEquals($expected[$enrol1id->id]->enddate, $query[$enrol1id->id]->enddate);
        $this->assertEquals($expected[$enrol1id->id]->pend_event, $query[$enrol1id->id]->pend_event);
        $this->assertEquals($expected[$enrol1id->id]->pend_encapsulation, $query[$enrol1id->id]->pend_encapsulation);
        $this->assertEquals($expected[$enrol1id->id]->pend_convalidation, $query[$enrol1id->id]->pend_convalidation);
        $this->assertEquals($expected[$enrol1id->id]->intensive, $query[$enrol1id->id]->intensive);
        $this->assertEquals($expected[$enrol1id->id]->masterid, $query[$enrol1id->id]->masterid);

        $this->assertEquals($expected[$enrol2id->id]->courseid, $query[$enrol2id->id]->courseid);
        $this->assertEquals($expected[$enrol2id->id]->shortname, $query[$enrol2id->id]->shortname);
        $this->assertEquals($expected[$enrol2id->id]->categoryid, $query[$enrol2id->id]->categoryid);
        $this->assertEquals($expected[$enrol2id->id]->startdate, $query[$enrol2id->id]->startdate);
        $this->assertEquals($expected[$enrol2id->id]->enddate, $query[$enrol2id->id]->enddate);
        $this->assertEquals($expected[$enrol2id->id]->pend_event, $query[$enrol2id->id]->pend_event);
        $this->assertEquals($expected[$enrol2id->id]->pend_encapsulation, $query[$enrol2id->id]->pend_encapsulation);
        $this->assertEquals($expected[$enrol2id->id]->pend_convalidation, $query[$enrol2id->id]->pend_convalidation);
        $this->assertEquals($expected[$enrol2id->id]->intensive, $query[$enrol2id->id]->intensive);
        $this->assertEquals($expected[$enrol2id->id]->masterid, $query[$enrol2id->id]->masterid);

    }

    /**
     * Tests save encapsulations of enrolments.
     */
    public function test_eude_save_encapsulation() {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating an users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1'));

        // Creating several categories for future use.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $category2 = $this->getDataGenerator()->create_category(array('name' => 'Category 2'));

        // Setting initial parameters.
        $startdate = time();
        $enddate = time() + 100000;

        // Testing the function with user 1 and category 1.
        $result = $this->invoke_method($instance1, 'eude_save_encapsulation',
                array($user1->id, $category1->id, $startdate, $enddate));
        $expectedresult = $DB->get_record('local_eudest_masters',
                array('userid' => $user1->id, 'categoryid' => $category1->id, 'startdate' => $startdate, 'enddate' => $enddate));
        $this->assertEquals($expectedresult->id, $result);

        // Testing the function with user 1 and category 2.
        $result = $this->invoke_method($instance1, 'eude_save_encapsulation',
                array($user1->id, $category2->id, $startdate, $enddate));
        $expectedresult = $DB->get_record('local_eudest_masters',
                array('userid' => $user1->id, 'categoryid' => $category2->id, 'startdate' => $startdate, 'enddate' => $enddate));
        $this->assertEquals($expectedresult->id, $result);
    }

    /**
     * Tests if enrolments are encapsulated in a master encapsulation.
     */
    public function test_eude_encapsulate_enrolments () {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Creating a few users.
        $student1 = $this->getDataGenerator()->create_user(array('username' => 'student1'));
        $student2 = $this->getDataGenerator()->create_user(array('username' => 'student2'));

        // Creating several categories for future use.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $category2 = $this->getDataGenerator()->create_category(array('name' => 'Category 2'));
        $category3 = $this->getDataGenerator()->create_category(array('name' => 'Category 3'));

        // Creating courses related to the categories above.
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 1', 'category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 2', 'category' => $category1->id));
        $course3 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 3', 'category' => $category2->id));
        $course4 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 4', 'category' => $category2->id));
        $course5 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 5', 'category' => $category3->id));
        $course6 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 6', 'category' => $category3->id));
        $course7 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.Course 1', 'category' => $category1->id));
        $course8 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.Course 3', 'category' => $category2->id));

        // Enrol student 1 in courses 1 to 8 as a student.
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course3->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course4->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course5->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course6->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course7->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student1->id, $course8->id, $studentrole->id, 'manual');

        // Enrol student 2 in courses 3, 4 and 8 as student.
        $this->getDataGenerator()->enrol_user($student2->id, $course3->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student2->id, $course4->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($student2->id, $course8->id, $studentrole->id, 'manual');

        // Setting initial parameters.
        $startdate = time();
        $enddate = time() + 100000;

        // Recording initial data in local_eudest_enrols.
        $record1 = new stdClass();
        $record1->userid = $student1->id;
        $record1->courseid = $course1->id;
        $record1->shortname = $course1->shortname;
        $record1->categoryid = $category1->id;
        $record1->startdate = $startdate;
        $record1->enddate = $enddate;
        $record1->pend_event = 1;
        $record1->pend_encapsulation = 1;
        $record1->pend_convalidation = 0;
        $record1->intensive = 0;
        $record1->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record1);
        $record2 = new stdClass();
        $record2->userid = $student1->id;
        $record2->courseid = $course2->id;
        $record2->shortname = $course2->shortname;
        $record2->categoryid = $category1->id;
        $record2->startdate = $startdate;
        $record2->enddate = $enddate;
        $record2->pend_event = 1;
        $record2->pend_encapsulation = 1;
        $record2->pend_convalidation = 0;
        $record2->intensive = 0;
        $record2->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record2);
        $record3 = new stdClass();
        $record3->userid = $student1->id;
        $record3->courseid = $course3->id;
        $record3->shortname = $course3->shortname;
        $record3->categoryid = $category2->id;
        $record3->startdate = $startdate;
        $record3->enddate = $enddate;
        $record3->pend_event = 1;
        $record3->pend_encapsulation = 1;
        $record3->pend_convalidation = 0;
        $record3->intensive = 0;
        $record3->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record3);
        $record4 = new stdClass();
        $record4->userid = $student1->id;
        $record4->courseid = $course4->id;
        $record4->shortname = $course4->shortname;
        $record4->categoryid = $category2->id;
        $record4->startdate = $startdate;
        $record4->enddate = $enddate;
        $record4->pend_event = 1;
        $record4->pend_encapsulation = 1;
        $record4->pend_convalidation = 0;
        $record4->intensive = 0;
        $record4->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record4);
        $record5 = new stdClass();
        $record5->userid = $student1->id;
        $record5->courseid = $course5->id;
        $record5->shortname = $course5->shortname;
        $record5->categoryid = $category3->id;
        $record5->startdate = $startdate;
        $record5->enddate = $enddate;
        $record5->pend_event = 1;
        $record5->pend_encapsulation = 1;
        $record5->pend_convalidation = 0;
        $record5->intensive = 0;
        $record5->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record5);
        $record6 = new stdClass();
        $record6->userid = $student1->id;
        $record6->courseid = $course6->id;
        $record6->shortname = $course6->shortname;
        $record6->categoryid = $category3->id;
        $record6->startdate = $startdate;
        $record6->enddate = $enddate;
        $record6->pend_event = 1;
        $record6->pend_encapsulation = 1;
        $record6->pend_convalidation = 0;
        $record6->intensive = 0;
        $record6->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record6);
        $record7 = new stdClass();
        $record7->userid = $student1->id;
        $record7->courseid = $course7->id;
        $record7->shortname = $course7->shortname;
        $record7->categoryid = $category1->id;
        $record7->startdate = $startdate;
        $record7->enddate = $enddate;
        $record7->pend_event = 1;
        $record7->pend_encapsulation = 1;
        $record7->pend_convalidation = 0;
        $record7->intensive = 1;
        $record7->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record7);
        $record8 = new stdClass();
        $record8->userid = $student1->id;
        $record8->courseid = $course8->id;
        $record8->shortname = $course8->shortname;
        $record8->categoryid = $category2->id;
        $record8->startdate = $startdate;
        $record8->enddate = $enddate;
        $record8->pend_event = 1;
        $record8->pend_encapsulation = 1;
        $record8->pend_convalidation = 0;
        $record8->intensive = 1;
        $record8->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record8);
        $record9 = new stdClass();
        $record9->userid = $student2->id;
        $record9->courseid = $course3->id;
        $record9->shortname = $course3->shortname;
        $record9->categoryid = $category2->id;
        $record9->startdate = $startdate;
        $record9->enddate = $enddate;
        $record9->pend_event = 1;
        $record9->pend_encapsulation = 1;
        $record9->pend_convalidation = 0;
        $record9->intensive = 0;
        $record9->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record9);
        $record10 = new stdClass();
        $record10->userid = $student2->id;
        $record10->courseid = $course4->id;
        $record10->shortname = $course4->shortname;
        $record10->categoryid = $category2->id;
        $record10->startdate = $startdate;
        $record10->enddate = $enddate;
        $record10->pend_event = 1;
        $record10->pend_encapsulation = 1;
        $record10->pend_convalidation = 0;
        $record10->intensive = 0;
        $record10->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record10);
        $record11 = new stdClass();
        $record11->userid = $student2->id;
        $record11->courseid = $course8->id;
        $record11->shortname = $course8->shortname;
        $record11->categoryid = $category2->id;
        $record11->startdate = $startdate;
        $record11->enddate = $enddate;
        $record11->pend_event = 1;
        $record11->pend_encapsulation = 1;
        $record11->pend_convalidation = 0;
        $record11->intensive = 1;
        $record11->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record11);

        // Testing the function.
        $this->invoke_method($instance1, 'eude_encapsulate_enrolments');

        // Check different asserts with the expected results.
        $expectedeudestmastersentries = $DB->get_records('local_eudest_masters');
        $this->assertCount(4, $expectedeudestmastersentries);
        $expectedeudestmastersentriesforuser1 = $DB->get_records('local_eudest_masters', array('userid' => $student1->id));
        $this->assertCount(3, $expectedeudestmastersentriesforuser1);
        $expectedeudestmastersentriesforuser2 = $DB->get_records('local_eudest_masters', array('userid' => $student2->id));
        $this->assertCount(1, $expectedeudestmastersentriesforuser2);

        $expectedeudestenrolsforuser1 = $DB->get_records('local_eudest_enrols',
                array('userid' => $student1->id, 'pend_encapsulation' => 0));
        $this->assertCount(6, $expectedeudestenrolsforuser1);
        $expectedeudestenrolsforuser2 = $DB->get_records('local_eudest_enrols',
                array('userid' => $student2->id, 'pend_encapsulation' => 0));
        $this->assertCount(2, $expectedeudestenrolsforuser2);

        $masteriduser1cat1 = $DB->get_record('local_eudest_masters',
                array('userid' => $student1->id, 'categoryid' => $category1->id));
        $masteriduser1cat2 = $DB->get_record('local_eudest_masters',
                array('userid' => $student1->id, 'categoryid' => $category2->id));
        $masteriduser1cat3 = $DB->get_record('local_eudest_masters',
                array('userid' => $student1->id, 'categoryid' => $category3->id));
        $masteriduser2cat2 = $DB->get_record('local_eudest_masters',
                array('userid' => $student2->id, 'categoryid' => $category2->id));

        $expectedeudestenrolsforuser1cat1 = $DB->get_records('local_eudest_enrols',
                array('userid' => $student1->id, 'pend_encapsulation' => 0, 'masterid' => $masteriduser1cat1->id));
        $this->assertCount(2, $expectedeudestenrolsforuser1cat1);
        $expectedeudestenrolsforuser1cat2 = $DB->get_records('local_eudest_enrols',
                array('userid' => $student1->id, 'pend_encapsulation' => 0, 'masterid' => $masteriduser1cat2->id));
        $this->assertCount(2, $expectedeudestenrolsforuser1cat2);
        $expectedeudestenrolsforuser1cat3 = $DB->get_records('local_eudest_enrols',
                array('userid' => $student1->id, 'pend_encapsulation' => 0, 'masterid' => $masteriduser1cat3->id));
        $this->assertCount(2, $expectedeudestenrolsforuser1cat3);
        $expectedeudestenrolsforuser2cat2 = $DB->get_records('local_eudest_enrols',
                array('userid' => $student2->id, 'pend_encapsulation' => 0, 'masterid' => $masteriduser2cat2->id));
        $this->assertCount(2, $expectedeudestenrolsforuser2cat2);
    }

    /**
     * Tests if event can be added to calendar.
     */
    public function test_eude_add_event_to_calendar () {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a few instances of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2'));

        // Setting up initial data.
        $timestart1 = time();
        $duration1 = 345000;
        $timestart2 = time();
        $duration2 = 139000;

        $e1 = new stdClass();
        $e1->name = 'E1 name';
        $e1->description = 'E1 desc';
        $e1->timestart = $timestart1;
        $e1->timeduration = $duration1;
        $e1->userid = $user1->id;

        $e2 = new stdClass();
        $e2->name = 'E2 name';
        $e2->description = 'E2 desc';
        $e2->timestart = $timestart2;
        $e2->timeduration = $duration2;
        $e2->userid = $user1->id;

        $e3 = new stdClass();
        $e3->name = 'E3 name';
        $e3->description = 'E3 desc';
        $e3->timestart = $timestart1;
        $e3->timeduration = $duration1;
        $e3->userid = $user1->id;

        $e4 = new stdClass();
        $e4->name = 'E4 name';
        $e4->description = 'E4 desc';
        $e4->timestart = $timestart2;
        $e4->timeduration = $duration2;
        $e4->userid = $user2->id;

        // Admin capability to update the calendar.
        $this->setAdminUser();

        // Testing the function with the initial settings.
        $this->invoke_method($instance1, 'eude_add_event_to_calendar',
                array('name' => $e1->name, 'description' => $e1->description, 'timestart' => $e1->timestart,
                    'timeduration' => $e1->timeduration, 'userid' => $e1->userid));
        $this->invoke_method($instance1, 'eude_add_event_to_calendar',
                array('name' => $e2->name, 'description' => $e2->description, 'timestart' => $e2->timestart,
                    'timeduration' => $e2->timeduration, 'userid' => $e2->userid));
        $this->invoke_method($instance1, 'eude_add_event_to_calendar',
                array('name' => $e3->name, 'description' => $e3->description, 'timestart' => $e3->timestart,
                    'timeduration' => $e3->timeduration, 'userid' => $e3->userid));
        $this->invoke_method($instance1, 'eude_add_event_to_calendar',
                array('name' => $e4->name, 'description' => $e4->description, 'timestart' => $e4->timestart,
                    'timeduration' => $e4->timeduration, 'userid' => $e4->userid));

        // Checking asserts.
        $expectedeudestmastersentries = $DB->get_records('event');
        $this->assertCount(4, $expectedeudestmastersentries);
        $expectedeudestmastersentriesforuser1 = $DB->get_records('event', array('userid' => $user1->id));
        $this->assertCount(3, $expectedeudestmastersentriesforuser1);
        $expectedeudestmastersentriesforuser2 = $DB->get_records('event', array('userid' => $user2->id));
        $this->assertCount(1, $expectedeudestmastersentriesforuser2);
    }

    /**
     * Tests if generate events in calendar from enrolments.
     */
    public function test_eude_generate_course_events () {
        global $DB;
        global $CFG;
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2'));

        // Creating several categories for future use.
        $categ1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $categ2 = $this->getDataGenerator()->create_category(array('name' => 'Category 2'));
        $categ3 = $this->getDataGenerator()->create_category(array('name' => 'Category 3'));

        // Creating courses related to the categories above.
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 1', 'category' => $categ1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 2', 'category' => $categ1->id));
        $course3 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 3', 'category' => $categ2->id));
        $course4 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 4', 'category' => $categ2->id));
        $course5 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 5', 'category' => $categ3->id));
        $course6 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 6', 'category' => $categ3->id));
        $course7 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.Course 1', 'category' => $categ1->id));
        $course8 = $this->getDataGenerator()->create_course(
                array('shortname' => 'MI.Course 3', 'category' => $categ2->id));

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Enrol user1 in courses 1 to 8 as a student.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course4->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course5->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course6->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course7->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course8->id, $studentrole->id, 'manual');

        // Enrol user2 in courses 3, 4 and 8 as student.
        $this->getDataGenerator()->enrol_user($user2->id, $course3->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course4->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course8->id, $studentrole->id, 'manual');

        // Setting initial parameters.
        $startdate = time();
        $enddate = time() + 100000;

        // Recording initial data in local_eudest_enrols.
        $recordenrol1 = new stdClass();
        $recordenrol1->userid = $user1->id;
        $recordenrol1->courseid = $course1->id;
        $recordenrol1->shortname = $course1->shortname;
        $recordenrol1->categoryid = $categ1->id;
        $recordenrol1->startdate = $startdate;
        $recordenrol1->enddate = $enddate;
        $recordenrol1->pend_event = 1;
        $recordenrol1->pend_encapsulation = 1;
        $recordenrol1->pend_convalidation = 0;
        $recordenrol1->intensive = 0;
        $recordenrol1->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol1);
        $recordenrol2 = new stdClass();
        $recordenrol2->userid = $user1->id;
        $recordenrol2->courseid = $course2->id;
        $recordenrol2->shortname = $course2->shortname;
        $recordenrol2->categoryid = $categ1->id;
        $recordenrol2->startdate = $startdate;
        $recordenrol2->enddate = $enddate;
        $recordenrol2->pend_event = 1;
        $recordenrol2->pend_encapsulation = 1;
        $recordenrol2->pend_convalidation = 0;
        $recordenrol2->intensive = 0;
        $recordenrol2->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol2);
        $recordenrol3 = new stdClass();
        $recordenrol3->userid = $user1->id;
        $recordenrol3->courseid = $course3->id;
        $recordenrol3->shortname = $course3->shortname;
        $recordenrol3->categoryid = $categ2->id;
        $recordenrol3->startdate = $startdate;
        $recordenrol3->enddate = $enddate;
        $recordenrol3->pend_event = 0;
        $recordenrol3->pend_encapsulation = 1;
        $recordenrol3->pend_convalidation = 0;
        $recordenrol3->intensive = 0;
        $recordenrol3->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol3);
        $recordenrol4 = new stdClass();
        $recordenrol4->userid = $user1->id;
        $recordenrol4->courseid = $course4->id;
        $recordenrol4->shortname = $course4->shortname;
        $recordenrol4->categoryid = $categ2->id;
        $recordenrol4->startdate = $startdate;
        $recordenrol4->enddate = $enddate;
        $recordenrol4->pend_event = 0;
        $recordenrol4->pend_encapsulation = 1;
        $recordenrol4->pend_convalidation = 0;
        $recordenrol4->intensive = 0;
        $recordenrol4->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol4);
        $recordenrol5 = new stdClass();
        $recordenrol5->userid = $user1->id;
        $recordenrol5->courseid = $course5->id;
        $recordenrol5->shortname = $course5->shortname;
        $recordenrol5->categoryid = $categ3->id;
        $recordenrol5->startdate = $startdate;
        $recordenrol5->enddate = $enddate;
        $recordenrol5->pend_event = 1;
        $recordenrol5->pend_encapsulation = 1;
        $recordenrol5->pend_convalidation = 0;
        $recordenrol5->intensive = 0;
        $recordenrol5->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol5);
        $recordenrol6 = new stdClass();
        $recordenrol6->userid = $user1->id;
        $recordenrol6->courseid = $course6->id;
        $recordenrol6->shortname = $course6->shortname;
        $recordenrol6->categoryid = $categ3->id;
        $recordenrol6->startdate = $startdate;
        $recordenrol6->enddate = $enddate;
        $recordenrol6->pend_event = 1;
        $recordenrol6->pend_encapsulation = 1;
        $recordenrol6->pend_convalidation = 0;
        $recordenrol6->intensive = 0;
        $recordenrol6->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol6);
        $recordenrol7 = new stdClass();
        $recordenrol7->userid = $user1->id;
        $recordenrol7->courseid = $course7->id;
        $recordenrol7->shortname = $course7->shortname;
        $recordenrol7->categoryid = $categ1->id;
        $recordenrol7->startdate = $startdate;
        $recordenrol7->enddate = $enddate;
        $recordenrol7->pend_event = 0;
        $recordenrol7->pend_encapsulation = 1;
        $recordenrol7->pend_convalidation = 0;
        $recordenrol7->intensive = 1;
        $recordenrol7->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol7);
        $recordenrol8 = new stdClass();
        $recordenrol8->userid = $user1->id;
        $recordenrol8->courseid = $course8->id;
        $recordenrol8->shortname = $course8->shortname;
        $recordenrol8->categoryid = $categ2->id;
        $recordenrol8->startdate = $startdate;
        $recordenrol8->enddate = $enddate;
        $recordenrol8->pend_event = 1;
        $recordenrol8->pend_encapsulation = 1;
        $recordenrol8->pend_convalidation = 0;
        $recordenrol8->intensive = 1;
        $recordenrol8->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol8);
        $recordenrol9 = new stdClass();
        $recordenrol9->userid = $user2->id;
        $recordenrol9->courseid = $course3->id;
        $recordenrol9->shortname = $course3->shortname;
        $recordenrol9->categoryid = $categ2->id;
        $recordenrol9->startdate = $startdate;
        $recordenrol9->enddate = $enddate;
        $recordenrol9->pend_event = 0;
        $recordenrol9->pend_encapsulation = 1;
        $recordenrol9->pend_convalidation = 0;
        $recordenrol9->intensive = 0;
        $recordenrol9->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol9);
        $recordenrol10 = new stdClass();
        $recordenrol10->userid = $user2->id;
        $recordenrol10->courseid = $course4->id;
        $recordenrol10->shortname = $course4->shortname;
        $recordenrol10->categoryid = $categ2->id;
        $recordenrol10->startdate = $startdate;
        $recordenrol10->enddate = $enddate;
        $recordenrol10->pend_event = 0;
        $recordenrol10->pend_encapsulation = 1;
        $recordenrol10->pend_convalidation = 0;
        $recordenrol10->intensive = 0;
        $recordenrol10->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol10);
        $recordenrol11 = new stdClass();
        $recordenrol11->userid = $user2->id;
        $recordenrol11->courseid = $course8->id;
        $recordenrol11->shortname = $course8->shortname;
        $recordenrol11->categoryid = $categ2->id;
        $recordenrol11->startdate = $startdate;
        $recordenrol11->enddate = $enddate;
        $recordenrol11->pend_event = 1;
        $recordenrol11->pend_encapsulation = 1;
        $recordenrol11->pend_convalidation = 0;
        $recordenrol11->intensive = 1;
        $recordenrol11->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $recordenrol11);

        // Admin capability to update the calendar.
        $this->setAdminUser();

        // Set the settings to allow the generation of new events in the calendar.
        $record0 = $DB->get_record('config', array('name' => 'local_eudest_genenrolcalendar'));
        $record0->value = 1;
        $DB->update_record('config', $record0);
        $CFG->local_eudest_genenrolcalendar = 1;

        // Check the initial entries with no pending events.
        $expectedresult = $DB->get_records('local_eudest_enrols', array('pend_event' => 0));
        $this->assertCount(5, $expectedresult);

        // Testing the function with the initial settings.
        $this->invoke_method($instance1, 'eude_generate_course_events');

        // Checking asserts.
        $expectedevents = $DB->get_records('event');
        $this->assertCount(6, $expectedevents);
        $expectedeudestenrols = $DB->get_records('local_eudest_enrols', array('pend_event' => 0));
        $this->assertCount(11, $expectedeudestenrols);
    }

    /**
     * Tests if message is added to stack.
     */
    public function test_eude_add_message_to_stack () {
        global $DB;

        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating several categories for future use.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $category2 = $this->getDataGenerator()->create_category(array('name' => 'Category 2'));
        $category3 = $this->getDataGenerator()->create_category(array('name' => 'Category 3'));

        // Setting initial parameters.
        $startdate = time();

        // Testing the function with the initial settings.
        $this->invoke_method($instance1, 'eude_add_message_to_stack',
                array($category1->id, 'to1', 'target1', 'type1', $startdate));
        $this->invoke_method($instance1, 'eude_add_message_to_stack',
                array($category2->id, 'to1', 'target1', 'type1', $startdate));
        $this->invoke_method($instance1, 'eude_add_message_to_stack',
                array($category3->id, 'to1', 'target1', 'type1', $startdate));
        $this->invoke_method($instance1, 'eude_add_message_to_stack',
                array($category1->id, 'to1', 'target1', 'type2', $startdate));
        $this->invoke_method($instance1, 'eude_add_message_to_stack',
                array($category1->id, 'to1', 'target2', 'type2', $startdate));
        $this->invoke_method($instance1, 'eude_add_message_to_stack',
                array($category1->id, 'to2', 'target1', 'type2', $startdate));

        // Checking asserts.
        $expectedresult = $DB->get_records('local_eudest_msgs');
        $this->assertCount(6, $expectedresult);
        $expectedresultfortype1 = $DB->get_records('local_eudest_msgs', array('msgtype' => 'type1'));
        $this->assertCount(3, $expectedresultfortype1);
        $expectedresultfortype2 = $DB->get_records('local_eudest_msgs', array('msgtype' => 'type2'));
        $this->assertCount(3, $expectedresultfortype2);
        $expectedresultforcat1 = $DB->get_records('local_eudest_msgs', array('categoryid' => $category1->id));
        $this->assertCount(4, $expectedresultforcat1);
        $expectedresultforcat2 = $DB->get_records('local_eudest_msgs', array('categoryid' => $category2->id));
        $this->assertCount(1, $expectedresultforcat2);
        $expectedresultforcat3 = $DB->get_records('local_eudest_msgs', array('categoryid' => $category3->id));
        $this->assertCount(1, $expectedresultforcat3);
    }

    /**
     * Test to get the manager of a category.
     */
    public function test_eude_get_rm () {
        global $DB;

        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2'));

        // Creating several categories for future use.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $category2 = $this->getDataGenerator()->create_category(array('name' => 'Category 2'));
        $category3 = $this->getDataGenerator()->create_category(array('name' => 'Category 3'));

        // Recovering the manager role data.
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));

        // Recovering the context of the categories.
        $contextcat1 = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSECAT, 'instanceid' => $category1->id));
        $contextcat2 = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSECAT, 'instanceid' => $category2->id));

        // Enroling user1 in category 1 and category 2, enroling user2 in category 2 both as manager.
        $record1 = new stdClass();
        $record1->roleid = $managerrole->id;
        $record1->contextid = $contextcat1->id;
        $record1->userid = $user1->id;
        $lastinsertid = $DB->insert_record('role_assignments', $record1);
        $record2 = new stdClass();
        $record2->roleid = $managerrole->id;
        $record2->contextid = $contextcat2->id;
        $record2->userid = $user1->id;
        $lastinsertid = $DB->insert_record('role_assignments', $record2);
        $record3 = new stdClass();
        $record3->roleid = $managerrole->id;
        $record3->contextid = $contextcat2->id;
        $record3->userid = $user2->id;
        $lastinsertid = $DB->insert_record('role_assignments', $record3);

        // Test the function with category 1 (Expected results: user1).
        // Testing the function with the initial settings.
        $result = $this->invoke_method($instance1, 'eude_get_rm', array($category1->id));
        $expectedresult = $user1->id;
        $this->assertEquals($expectedresult, $result);

        // Test the function with category 1 (Expected results: user1).
        // Testing the function with the initial settings.
        $result = $this->invoke_method($instance1, 'eude_get_rm', array($category1->id));
        $expectedresult = $user1->id;
        $this->assertEquals($expectedresult, $result);

        // Test the function with category 1 (Expected results: user1).
        // Testing the function with the initial settings.
        $result = $this->invoke_method($instance1, 'eude_get_rm', array($category1->id));
        $expectedresult = $user1->id;
        $this->assertEquals($expectedresult, $result);

        // Test the function with category 2 (Expected results: array with user1 and user2).
        $result = $this->invoke_method($instance1, 'eude_get_rm', array($category1->id));
        $expectedresult = $user1->id;
        $this->assertEquals($expectedresult, $result);

        // Test the function with category 3 (Expected results: empty array).
        $result = $this->invoke_method($instance1, 'eude_get_rm', array($category3->id));
        $expectedresult = 0;
        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Tests convalidation between courses.
     */
    public function test_eude_convalidate_modules () {
        global $DB;
        global $CFG;
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest class.
        $instance1 = new local_eudest();

        // Setting the initial CFG parameter to not allow convalidations.
        $CFG->local_eudest_convalidations = 0;

        // Testing with the setting not allowing the modifications.
        $result = $this->invoke_method($instance1, 'eude_convalidate_modules', array());
        $this->assertEquals(0, $result);

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1', 'email' => 'user1@php.com'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2', 'email' => 'user2@php.com'));

        // Creating a course category for future use.
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));

        // Creating courses related to the categorie above.
        $course1mod1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 1[-Modulo 1-]', 'category' => $category1->id));
        $course2mod2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 2[-Modulo 2-]', 'category' => $category1->id));
        $course3mod1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 3[-Modulo 1-]', 'category' => $category1->id));
        $course4mod2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 4[-Modulo 2-]', 'category' => $category1->id));

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Setting initial parameters.
        $startdate = time();
        $enddate = time() + 100000;

        // Enrol user1 in courses 1 to 4 as a student.
        $this->getDataGenerator()->enrol_user($user1->id, $course1mod1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course2mod2->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course3mod1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user1->id, $course4mod2->id, $studentrole->id, 'manual');

        // Enrol user2 in courses 1, 2 and 3 as student, not enrol in course 4.
        $this->getDataGenerator()->enrol_user($user2->id, $course1mod1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course2mod2->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $course3mod1->id, $studentrole->id, 'manual');

        // Recording initial data in eudest_enrols.
        $record1 = new stdClass();
        $record1->userid = $user1->id;
        $record1->courseid = $course1mod1->id;
        $record1->shortname = $course1mod1->shortname;
        $record1->categoryid = $category1->id;
        $record1->startdate = $startdate;
        $record1->enddate = $enddate;
        $record1->pend_event = 1;
        $record1->pend_encapsulation = 1;
        $record1->pend_convalidation = 0;
        $record1->intensive = 0;
        $record1->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record1);
        $recordone = $DB->get_record('local_eudest_enrols', array());
        $identif = $recordone->id;
        $record2 = new stdClass();
        $record2->userid = $user1->id;
        $record2->courseid = $course2mod2->id;
        $record2->shortname = $course2mod2->shortname;
        $record2->categoryid = $category1->id;
        $record2->startdate = $startdate;
        $record2->enddate = $enddate;
        $record2->pend_event = 1;
        $record2->pend_encapsulation = 1;
        $record2->pend_convalidation = 1;
        $record2->intensive = 0;
        $record2->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record2);
        $record3 = new stdClass();
        $record3->userid = $user1->id;
        $record3->courseid = $course3mod1->id;
        $record3->shortname = $course3mod1->shortname;
        $record3->categoryid = $category1->id;
        $record3->startdate = $startdate;
        $record3->enddate = $enddate;
        $record3->pend_event = 1;
        $record3->pend_encapsulation = 1;
        $record3->pend_convalidation = 1;
        $record3->intensive = 0;
        $record3->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record3);
        $record4 = new stdClass();
        $record4->userid = $user1->id;
        $record4->courseid = $course4mod2->id;
        $record4->shortname = $course4mod2->shortname;
        $record4->categoryid = $category1->id;
        $record4->startdate = $startdate;
        $record4->enddate = $enddate;
        $record4->pend_event = 1;
        $record4->pend_encapsulation = 1;
        $record4->pend_convalidation = 0;
        $record4->intensive = 0;
        $record4->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record4);
        $record5 = new stdClass();
        $record5->userid = $user2->id;
        $record5->courseid = $course1mod1->id;
        $record5->shortname = $course1mod1->shortname;
        $record5->categoryid = $category1->id;
        $record5->startdate = $startdate;
        $record5->enddate = $enddate;
        $record5->pend_event = 1;
        $record5->pend_encapsulation = 1;
        $record5->pend_convalidation = 1;
        $record5->intensive = 0;
        $record5->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record5);
        $record6 = new stdClass();
        $record6->userid = $user2->id;
        $record6->courseid = $course2mod2->id;
        $record6->shortname = $course2mod2->shortname;
        $record6->categoryid = $category1->id;
        $record6->startdate = $startdate;
        $record6->enddate = $enddate;
        $record6->pend_event = 1;
        $record6->pend_encapsulation = 1;
        $record6->pend_convalidation = 0;
        $record6->intensive = 0;
        $record6->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record6);
        $record7 = new stdClass();
        $record7->userid = $user2->id;
        $record7->courseid = $course3mod1->id;
        $record7->shortname = $course3mod1->shortname;
        $record7->categoryid = $category1->id;
        $record7->startdate = $startdate;
        $record7->enddate = $enddate;
        $record7->pend_event = 1;
        $record7->pend_encapsulation = 1;
        $record7->pend_convalidation = 0;
        $record7->intensive = 0;
        $record7->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record7);

        // Creating grade_categories for the courses.
        $this->getDataGenerator()->create_grade_category(
                array('courseid' => $course1mod1->id, 'fullname' => 'Grade Category', 'aggregation' => '13'));
        $this->getDataGenerator()->create_grade_category(
                array('courseid' => $course2mod2->id, 'fullname' => 'Grade Category', 'aggregation' => '13'));
        $this->getDataGenerator()->create_grade_category(
                array('courseid' => $course3mod1->id, 'fullname' => 'Grade Category', 'aggregation' => '13'));
        $this->getDataGenerator()->create_grade_category(
                array('courseid' => $course4mod2->id, 'fullname' => 'Grade Category', 'aggregation' => '13'));

        // Creating grades.
        $grade1 = $this->getDataGenerator()->create_grade_item(array(
                'itemtype' => 'course', 'courseid' => $course1mod1->id, 'category' => $category1->id));
        $grade2 = $this->getDataGenerator()->create_grade_item(array(
                'itemtype' => 'course', 'courseid' => $course2mod2->id, 'category' => $category1->id));
        $grade3 = $this->getDataGenerator()->create_grade_item(array(
                'itemtype' => 'course', 'courseid' => $course3mod1->id, 'category' => $category1->id));
        $grade4 = $this->getDataGenerator()->create_grade_item(array(
                'itemtype' => 'course', 'courseid' => $course4mod2->id, 'category' => $category1->id));

        $grades1 = new stdClass();
        $grades1->itemid = $grade1->id;
        $grades1->finalgrade = 78;
        $grades1->userid = $user1->id;
        $DB->insert_record('grade_grades', $grades1, false);

        $grades4 = new stdClass();
        $grades4->itemid = $grade4->id;
        $grades4->finalgrade = 69;
        $grades4->userid = $user1->id;
        $DB->insert_record('grade_grades', $grades4, false);

        $grades5 = new stdClass();
        $grades5->itemid = $grade3->id;
        $grades5->finalgrade = 51;
        $grades5->userid = $user2->id;
        $DB->insert_record('grade_grades', $grades5, false);

        $grades6 = new stdClass();
        $grades6->itemid = $grade2->id;
        $grades6->finalgrade = 65;
        $grades6->userid = $user2->id;
        $DB->insert_record('grade_grades', $grades6, false);

        $sqlgrade = "SELECT gg.id, gg.itemid, gi.courseid, c.shortname, gg.userid, gi.grademax, gg.finalgrade
                       FROM {grade_items} gi
                       JOIN {grade_grades} gg on gg.itemid = gi.id
                       JOIN {course} c on gi.courseid = c.id
                      WHERE gi.itemtype = 'course'";
        $grades = $DB->get_records_sql($sqlgrade, array());
        $this->assertCount(4, $grades);

        // Test data of 'local_eudest_enrols' table.
        $enrols = $DB->get_records('local_eudest_enrols', array());
        $this->assertEquals(1, $enrols[$identif + 1]->pend_convalidation);
        $this->assertEquals(1, $enrols[$identif + 2]->pend_convalidation);
        $this->assertEquals(1, $enrols[$identif + 4]->pend_convalidation);

        // Testing the function when convalidation is not allowed.
        $this->invoke_method($instance1, 'eude_convalidate_modules', array());

        $othergrades = $DB->get_records_sql($sqlgrade, array());
        $this->assertCount(4, $othergrades);

         // Setting the initial CFG parameter to allow convalidations.
        $CFG->local_eudest_convalidations = 1;

        // Testing the function when convalidation is allowed.
        $this->invoke_method($instance1, 'eude_convalidate_modules', array());

        // Test data of 'local_eudest_enrols' table.
        $expected = $DB->get_records('local_eudest_enrols');

        $this->assertEquals(0, $expected[$identif + 1]->pend_convalidation);
        $this->assertEquals(0, $expected[$identif + 2]->pend_convalidation);
        $this->assertEquals(0, $expected[$identif + 4]->pend_convalidation);

        $newgrades = $DB->get_records_sql($sqlgrade, array());
        $this->assertCount(7, $newgrades);
    }

    /**
     * Tests generate master messages from encapsulation.
     */
    public function test_eude_generate_master_messages() {
        global $DB;
        global $CFG;

        $this->resetAfterTest(true);

        $instance1 = new local_eudest();

        $manualplugin = self::enable_enrol_plugin();
        $this->assertNotEmpty($manualplugin);
        $studentrole = self::get_student_role();

        $today = time();
        $month = 2629800;

        // Test without CFG parameters.
        $return = $this->invoke_method($instance1, 'eude_generate_master_messages', array());
        $this->assertEmpty($return);

        // Add CFG parameters.
        $CFG->local_eudest_enrolnotice = 'Enrol';
        $CFG->local_eudest_stfinishnotice = 'Finish Masters';
        $CFG->local_eudest_rmfinishnotice = 'Remove Finish';

        $user1 = $this->getDataGenerator()->create_user(array('username' => 'usuario 1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'usuario 2'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'usuario 3'));

        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));

        $course1 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 1', 'category' => $category1->id));

        $manualinstance = self::create_manual_instance($course1->id);
        $manualplugin->enrol_user($manualinstance, $user1->id, $studentrole->id, $today - (10 * $month), $today - (5 * $month));
        $manualplugin->enrol_user($manualinstance, $user2->id, $studentrole->id, $today - (8 * $month), $today - (3 * $month));
        $manualplugin->enrol_user($manualinstance, $user3->id, $studentrole->id, $today - (15 * $month), $today - (7 * $month));

        $access1 = new stdClass();
        $access1->userid = $user1->id;
        $access1->categoryid = $category1->id;
        $access1->startdate = $today - (10 * $month);
        $access1->enddate = $today + $month;
        $access1->pend_holidays = 0;
        $access1->pend_master_messages = 0;
        $access1->inactivity6 = 0;
        $access1->inactivity18 = 0;
        $access1->inactivity24 = 0;

        $DB->insert_record('local_eudest_masters', $access1, false);
        $data = $DB->get_record('local_eudest_masters', array());
        $identif = $data->id;

        // Don't working in this case.
        $access2 = new stdClass();
        $access2->userid = $user2->id;
        $access2->categoryid = $category1->id;
        $access2->startdate = $today - (8 * $month);
        $access2->enddate = $today - $month;
        $access2->pend_holidays = 0;
        $access2->pend_master_messages = 1;
        $access2->inactivity6 = 0;
        $access2->inactivity18 = 0;
        $access2->inactivity24 = 0;

        $DB->insert_record('local_eudest_masters', $access2, false);

        // Test the update inside the function.
        $data2 = $DB->get_records('local_eudest_masters', array());
        $this->assertEquals($data2[$identif + 1]->pend_master_messages, '1');

        $this->invoke_method($instance1, 'eude_generate_master_messages', array());

        $data3 = $DB->get_records('local_eudest_masters', array());
        $this->assertEquals($data3[$identif + 1]->pend_master_messages, '0');

        // Restart data.
        $access2b = new stdClass();
        $access2b->id = $identif + 1;
        $access2b->pend_master_messages = 1;

        $DB->update_record('local_eudest_masters', $access2b, false);

        // Add user 3 data on eudest_master table.
        $access3 = new stdClass();
        $access3->userid = $user3->id;
        $access3->categoryid = $category1->id;
        $access3->startdate = $today - (15 * $month);
        $access3->enddate = $today - $month;
        $access3->pend_holidays = 0;
        $access3->pend_master_messages = 1;
        $access3->inactivity6 = 0;
        $access3->inactivity18 = 0;
        $access3->inactivity24 = 0;
        $DB->insert_record('local_eudest_masters', $access3, false);

        $data4 = $DB->get_records('local_eudest_masters', array());
        $this->assertEquals($data4[$identif + 1]->pend_master_messages, '1');
        $this->assertEquals($data4[$identif + 2]->pend_master_messages, '1');

        $this->invoke_method($instance1, 'eude_generate_master_messages', array());

        $data5 = $DB->get_records('local_eudest_masters', array());
        $this->assertEquals($data5[$identif + 1]->pend_master_messages, '0');
        $this->assertEquals($data5[$identif + 2]->pend_master_messages, '0');

    }

    /**
     * Tests generate inactivity messages.
     */
    public function test_eude_generate_inactivity_messages() {
        global $DB;
        global $CFG;

        $this->resetAfterTest(true);

        $instance1 = new local_eudest();

        $manualplugin = self::enable_enrol_plugin();
        $this->assertNotEmpty($manualplugin);
        $studentrole = self::get_student_role();

        $today = time();
        $month = 2629800;

        $CFG->local_eudest_inac6notice = '6 meses inactivo';
        $CFG->local_eudest_inac18notice = '18 meses inactivo';
        $CFG->local_eudest_inac24notice = '24 meses inactivo';

        $user1 = $this->getDataGenerator()->create_user(array('username' => 'usuario 1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'usuario 2'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'usuario 3'));
        $user4 = $this->getDataGenerator()->create_user(array('username' => 'usuario 4'));

        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));

        $course1 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 1', 'category' => $category1->id));

        $manualinstance = self::create_manual_instance($course1->id);
        $manualplugin->enrol_user($manualinstance, $user1->id, $studentrole->id, $today - (20 * $month));
        $manualplugin->enrol_user($manualinstance, $user2->id, $studentrole->id, $today - (25 * $month));
        $manualplugin->enrol_user($manualinstance, $user3->id, $studentrole->id, $today - (25 * $month));
        $manualplugin->enrol_user($manualinstance, $user4->id, $studentrole->id, $today - (25 * $month));

        // Add data to eudest_masters for each user.
        $access1 = new stdClass();
        $access1->userid = $user1->id;
        $access1->categoryid = $category1->id;
        $access1->startdate = $today - (20 * $month);
        $access1->enddate = $today + $month;
        $access1->pend_holidays = 0;
        $access1->pend_master_messages = 0;
        $access1->inactivity6 = 0;
        $access1->inactivity18 = 0;
        $access1->inactivity24 = 0;

        $DB->insert_record('local_eudest_masters', $access1, false);
        $accessid1 = $DB->get_record('local_eudest_masters', array('userid' => $user1->id));

        $access2 = new stdClass();
        $access2->userid = $user2->id;
        $access2->categoryid = $category1->id;
        $access2->startdate = $today - (25 * $month);
        $access2->enddate = $today - $month;
        $access2->pend_holidays = 0;
        $access2->pend_master_messages = 0;
        $access2->inactivity6 = 0;
        $access2->inactivity18 = 0;
        $access2->inactivity24 = 0;

        $DB->insert_record('local_eudest_masters', $access2, false);
        $accessid2 = $DB->get_record('local_eudest_masters', array('userid' => $user2->id));

        $access3 = new stdClass();
        $access3->userid = $user3->id;
        $access3->categoryid = $category1->id;
        $access3->startdate = $today - (22 * $month);
        $access3->enddate = $today - (19 * $month);
        $access3->pend_holidays = 0;
        $access3->pend_master_messages = 0;
        $access3->inactivity6 = 0;
        $access3->inactivity18 = 0;
        $access3->inactivity24 = 0;

        $DB->insert_record('local_eudest_masters', $access3, false);
        $accessid3 = $DB->get_record('local_eudest_masters', array('userid' => $user3->id));

        $access4 = new stdClass();
        $access4->userid = $user4->id;
        $access4->categoryid = $category1->id;
        $access4->startdate = $today - (35 * $month);
        $access4->enddate = $today - (30 * $month);
        $access4->pend_holidays = 0;
        $access4->pend_master_messages = 0;
        $access4->inactivity6 = 0;
        $access4->inactivity18 = 0;
        $access4->inactivity24 = 0;

        $DB->insert_record('local_eudest_masters', $access4, false);
        $accessid4 = $DB->get_record('local_eudest_masters', array('userid' => $user4->id));

        // Add data to user_lastaccess.
        $lastaccess1 = new stdClass();
        $lastaccess1->userid = $user1->id;
        $lastaccess1->courseid = $category1->id;
        $lastaccess1->timeaccess = $today - (10 * $month);

        $DB->insert_record('user_lastaccess', $lastaccess1, false);

        $lastaccess2 = new stdClass();
        $lastaccess2->userid = $user2->id;
        $lastaccess2->courseid = $category1->id;
        $lastaccess2->timeaccess = $today - (2 * $month);

        $DB->insert_record('user_lastaccess', $lastaccess2, false);

        $lastaccess3 = new stdClass();
        $lastaccess3->userid = $user3->id;
        $lastaccess3->courseid = $category1->id;
        $lastaccess3->timeaccess = $today - (20 * $month);

        $DB->insert_record('user_lastaccess', $lastaccess3, false);

        $lastaccess4 = new stdClass();
        $lastaccess4->userid = $user4->id;
        $lastaccess4->courseid = $category1->id;
        $lastaccess4->timeaccess = $today - (30 * $month);

        $DB->insert_record('user_lastaccess', $lastaccess4, false);

        // Test with today like a last inactivity date.
        $eudeconfig = new stdClass();
        $eudeconfig->last_inactivity_date = $today;

        $this->set_protected ($instance1, 'eudeconfig', $eudeconfig);

        $return = $this->invoke_method($instance1, 'eude_generate_inactivity_messages', array());

        // Test with previous last inactivity date.
        $eudeconfig2 = new stdClass();
        $eudeconfig2->last_inactivity_date = $today - $month;

        $this->set_protected ($instance1, 'eudeconfig', $eudeconfig2);
        $return = $this->invoke_method($instance1, 'eude_generate_inactivity_messages', array());
        $results = $DB->get_records('local_eudest_masters');

        // User 1, last access after 6 months on active module.
        $this->assertEquals($results[$accessid1->id]->inactivity6, 1);
        $this->assertEquals($results[$accessid1->id]->inactivity18, 0);
        $this->assertEquals($results[$accessid1->id]->inactivity24, 0);

        // User 2, finish module after 18 months.
        $this->assertEquals($results[$accessid2->id]->inactivity6, 0);
        $this->assertEquals($results[$accessid2->id]->inactivity18, 0);
        $this->assertEquals($results[$accessid2->id]->inactivity24, 0);

        // User 3, finish module between 18 and 24 months.
        $this->assertEquals($results[$accessid3->id]->inactivity6, 0);
        $this->assertEquals($results[$accessid3->id]->inactivity18, 1);
        $this->assertEquals($results[$accessid3->id]->inactivity24, 0);

        // User 4, finish module before 24 months.
        $this->assertEquals($results[$accessid4->id]->inactivity6, 0);
        $this->assertEquals($results[$accessid4->id]->inactivity18, 1);
        $this->assertEquals($results[$accessid4->id]->inactivity24, 1);

    }

    /**
     * Tests if can update course total grade.
     */
    public function test_eude_update_course_grade() {
        global $DB;

        $this->resetAfterTest(true);

        $instance1 = new local_eudest();

        $manualplugin = self::enable_enrol_plugin();
        $this->assertNotEmpty($manualplugin);
        $studentrole = self::get_student_role();

        // Create user, category, course and grade_category.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'usuario 1'));
        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $course1 = $this->getDataGenerator()->create_course(array('shortname' => 'Course 1', 'category' => $category1->id));

        $gradecat = $this->getDataGenerator()->create_grade_category(array(
                                                            'courseid' => $course1->id,
                                                            'fullname' => 'Grade Category',
                                                            'aggregation' => '13'));

        $manualinstance = self::create_manual_instance($course1->id);
        $manualplugin->enrol_user($manualinstance, $user1->id, $studentrole->id, time() - 1000000, time() + 1000000);

        // Create an instance grade category.
        $prueba1 = grade_category::fetch(array('id' => $gradecat->id));

        // Create grades for the instance grade category.
        $grades1 = new stdClass();
        $grades1->itemid = $prueba1->id;
        $grades1->finalgrade = 27;
        $grades1->userid = $user1->id;
        $grades1->information = 'Nota curso normal:2.50.';

        $DB->insert_record('grade_grades', $grades1, false);
        $data = $DB->get_record('grade_grades', array());
        $identif = $data->id;
        // Define the parameters to use in the function.
        $gradeitemid = $prueba1->id;
        $courseid = $course1->id;
        $userid = $user1->id;
        $finalgrade = $grades1->finalgrade;
        $info = $grades1->information;
        $params = array($gradeitemid, $courseid, $userid, $finalgrade, $info);
        $this->assertNotEmpty($params);
        $result = $this->invoke_method($instance1, 'eude_update_course_grade',
                array($gradeitemid, $courseid, $userid, $finalgrade, $info));

        // Test the results.
        $allgrades = $DB->get_records('grade_grades', array());

        $this->assertEquals($allgrades[$identif]->finalgrade, 27);
        $this->assertEquals($allgrades[$identif]->feedback, 'Nota curso normal:2.50.');
        $this->assertEquals($allgrades[$identif]->information, 'Nota curso normal:2.50.');

        // Change parameters to update.
        $newfinalgrade = 35;
        $newinfo = 'Nota curso intensivo:3.50.';

        $result = $this->invoke_method($instance1, 'eude_update_course_grade',
                array($gradeitemid, $courseid, $userid, $newfinalgrade, $newinfo));

        // Test the new results.
        $allgrades2 = $DB->get_records('grade_grades', array());

        $this->assertEquals($allgrades2[$identif]->finalgrade, 35);
        $this->assertEquals($allgrades2[$identif]->feedback, 'Nota curso intensivo:3.50.');
    }

    /**
     * Tests if messages are sended.
     */
    public function test_eude_send_scheduled_messages() {
        global $DB;
        global $CFG;

        $this->resetAfterTest(true);

        $instance1 = new local_eudest();

        $CFG->local_eudest_enroltext = 'Enrol Text';
        $CFG->local_eudest_rmfinishtext = 'Responsable Master Finish Text';
        $CFG->local_eudest_stfinishtext = 'Student Finish Text';
        $CFG->local_eudest_inac6text = 'Inactive 6 months Text';
        $CFG->local_eudest_inac18text = 'Inactive 18 months Text';
        $CFG->local_eudest_inac24rmtext = 'Inactive 24 months Responsable Master Text';
        $CFG->local_eudest_inac24sttext = 'Inactive 24 months Student Text';
        $CFG->wwwroot = 'http://192.168.1.26/moodle30';

        $today = time();
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));

        // Create users, category and category context.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'usuario 1', 'email' => 'user1@php.com'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'usuario 2', 'email' => 'user2@php.com'));

        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));

        $contextcat1 = $DB->get_record('context', array('contextlevel' => CONTEXT_COURSECAT, 'instanceid' => $category1->id));

        // Enroling user1 and user2 in category 1 as manager.
        $record1 = new stdClass();
        $record1->roleid = $managerrole->id;
        $record1->contextid = $contextcat1->id;
        $record1->userid = $user1->id;
        $lastinsertid = $DB->insert_record('role_assignments', $record1);
        $record2 = new stdClass();
        $record2->roleid = $managerrole->id;
        $record2->contextid = $contextcat1->id;
        $record2->userid = $user2->id;
        $lastinsertid = $DB->insert_record('role_assignments', $record2);

        // Create message type new student.
        $msg5 = new stdClass();
        $msg5->categoryid = $category1->id;
        $msg5->msgto = $user1->id;
        $msg5->msgtarget = $user2->id;
        $msg5->msgtype = 'NEW_STUDENT';
        $msg5->msgdate = $today;
        $msg5->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msg5, false);

        // Create message type student finish master.
        $msg6 = new stdClass();
        $msg6->categoryid = $category1->id;
        $msg6->msgto = $user1->id;
        $msg6->msgtarget = $user2->id;
        $msg6->msgtype = 'ST_FINISH_MASTER';
        $msg6->msgdate = $today;
        $msg6->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msg6, false);

        // Create message type responsable master finish master.
        $msg7 = new stdClass();
        $msg7->categoryid = $category1->id;
        $msg7->msgto = $user1->id;
        $msg7->msgtarget = $user2->id;
        $msg7->msgtype = 'RM_FINISH_MASTER';
        $msg7->msgdate = $today;
        $msg7->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msg7, false);

        // Create message type inactivity 6.
        $msg8 = new stdClass();
        $msg8->categoryid = $category1->id;
        $msg8->msgto = $user1->id;
        $msg8->msgtarget = $user2->id;
        $msg8->msgtype = 'RM_INACTIVITY6';
        $msg8->msgdate = $today;
        $msg8->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msg8, false);

        // Create message type inactivity 18.
        $msg9 = new stdClass();
        $msg9->categoryid = $category1->id;
        $msg9->msgto = $user1->id;
        $msg9->msgtarget = $user2->id;
        $msg9->msgtype = 'RM_INACTIVITY18';
        $msg9->msgdate = $today;
        $msg9->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msg9, false);

        // Create message type inactivity 24.
        $msg0 = new stdClass();
        $msg0->categoryid = $category1->id;
        $msg0->msgto = $user1->id;
        $msg0->msgtarget = $user2->id;
        $msg0->msgtype = 'RM_INACTIVITY24';
        $msg0->msgdate = $today;
        $msg0->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msg0, false);

        // Create message type user locked.
        $msga = new stdClass();
        $msga->categoryid = $category1->id;
        $msga->msgto = $user1->id;
        $msga->msgtarget = $user2->id;
        $msga->msgtype = 'USER_LOCKED';
        $msga->msgdate = $today;
        $msga->sended = 0;

        $DB->insert_record('local_eudest_msgs', $msga, false);

        // Test Sended messages before use the function.
        $messages4 = $DB->get_records('local_eudest_msgs', array());
        $this->assertCount(7, $messages4);

        $this->invoke_method($instance1, 'eude_send_scheduled_messages', array());

        // Test Sended messages after use the function.
        $messages5 = $DB->get_records('local_eudest_msgs', array());
        $this->assertCount(0, $messages5);

    }

    /**
     * Tests if configuration can be saved.
     */
    public function test_eude_save_configuration() {
        global $DB;

        $this->resetAfterTest(true);

        $instance1 = new local_eudest();

        $eudeconfig = new stdClass();
        $eudeconfig->id = 0;
        $eudeconfig->last_enrolid = 12;
        $eudeconfig->last_inactivity_date = time();
        $eudeconfig->last_califications_date = time();
        $this->assertNotEmpty($eudeconfig);
        $this->set_protected($instance1, 'eudeconfig', $eudeconfig);

        $this->invoke_method($instance1, 'eude_save_configuration', array());
        $config = $DB->get_record('local_eudest_config', array());
        $identif = $config->id;
        $this->assertEquals($config->last_enrolid, 12);

        $eudeconfig2 = new stdClass();
        $eudeconfig2->id = $eudeconfig->id;
        $eudeconfig2->last_enrolid = 14;
        $eudeconfig2->last_inactivity_date = time();
        $eudeconfig2->last_califications_date = time();
        $this->assertNotEmpty($eudeconfig2);
        $this->set_protected($instance1, 'eudeconfig', $eudeconfig2);

        $this->invoke_method($instance1, 'eude_save_configuration', array());
        $config2 = $DB->get_records('local_eudest_config', array());
        $this->assertEquals($config2[$identif]->last_enrolid, 12);
        $this->assertEquals($config2[$identif + 1]->last_enrolid, 14);

    }

    /**
     * Tests Get category.
     */
    public function test_getcategory() {

        $this->resetAfterTest(true);

        $instance1 = new local_eudest();

        $category1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $this->assertNotEmpty($category1->id);

        $category = $this->invoke_method($instance1, 'get_category', array($category1->id));
        $this->assertNotEmpty($category);
        $this->assertequals($category->name, 'Category 1');

    }
}
