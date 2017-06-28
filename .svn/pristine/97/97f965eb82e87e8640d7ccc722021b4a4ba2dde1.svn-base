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
 * COMPONENT File to revert enrolments
 *
 * @package    local_eudest
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(__DIR__. '/../local_eudest_revert.php');

/**
 * This class is used to run the unit tests
 *
 * @package    local_eudest
 * @copyright  2015 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudest_revert_testcase extends advanced_testcase {

    /**
     * Tests the revert process
     */
    public function test_eude_revert () {
        global $DB;
        $this->resetAfterTest(true);

        // Creating a instance of the local_eudest_revert class.
        $instance1 = new local_eudest_revert();

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'user3'));

        // Creating several categories for future use.
        $cat1 = $this->getDataGenerator()->create_category(array('name' => 'Category 1'));
        $cat2 = $this->getDataGenerator()->create_category(array('name' => 'Category 2'));
        $cat3 = $this->getDataGenerator()->create_category(array('name' => 'Category 3'));

        // Creating courses related to the categories above.
        $course1 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 1', 'category' => $cat1->id));
        $course2 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 2', 'category' => $cat1->id));
        $course3 = $this->getDataGenerator()->create_course(
                array('shortname' => 'Course 3', 'category' => $cat2->id));

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Initial settings.
        $timestart = time();

        // Enrol user1 in course1, 2 and 3.
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id, 'manual', $timestart);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $studentrole->id, 'manual', $timestart);
        $this->getDataGenerator()->enrol_user($user1->id, $course3->id, $studentrole->id, 'manual', $timestart);

        // Saving enrol data in eudest_enrols.
        $record1 = new stdClass();
        $record1->userid = $user1->id;
        $record1->courseid = $course1->id;
        $record1->shortname = $course1->shortname;
        $record1->categoryid = $cat1->id;
        $record1->startdate = $timestart;
        $record1->enddate = $timestart + 100000;
        $record1->pend_event = 0;
        $record1->pend_encapsulation = 0;
        $record1->pend_convalidation = 0;
        $record1->intensive = 0;
        $record1->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record1);
        $record2 = new stdClass();
        $record2->userid = $user1->id;
        $record2->courseid = $course2->id;
        $record2->shortname = $course2->shortname;
        $record2->categoryid = $cat1->id;
        $record2->startdate = $timestart;
        $record2->enddate = $timestart + 100000;
        $record2->pend_event = 0;
        $record2->pend_encapsulation = 0;
        $record2->pend_convalidation = 0;
        $record2->intensive = 0;
        $record2->masterid = 0;
        $DB->insert_record('local_eudest_enrols', $record2);

        // Create events for user1.
        $event1 = new stdClass();
        $event1->name = '[[COURSE]]E1';
        $event1->description = 'E1 desc';
        $event1->timestart = $timestart + 10000;
        $event1->timeduration = 100000;
        $event1->eventtype = 'user';
        $event1->userid = $user1->id;

        $DB->insert_record('event', $event1);

        // Create events for user2.
        $event2 = new stdClass();
        $event2->name = '[[COURSE]]E2';
        $event2->description = 'E2 desc';
        $event2->timestart = $timestart + 10000;
        $event2->timeduration = 100000;
        $event2->eventtype = 'user';
        $event2->userid = $user2->id;

        $DB->insert_record('event', $event2);

        // Create msgs for user1.
        $msg1 = new stdClass();
        $msg1->categoryid = $cat1->id;
        $msg1->msgto = $user1->id;
        $msg1->msgtarget = $user2->id;
        $msg1->msgtype = 'text';
        $msg1->msgdate = $timestart + 10000;
        $msg1->sended = 1;

        $msg2 = new stdClass();
        $msg2->categoryid = $cat1->id;
        $msg2->msgto = $user2->id;
        $msg2->msgtarget = $user1->id;
        $msg2->msgtype = 'text';
        $msg2->msgdate = $timestart + 10000;
        $msg2->sended = 1;

        $msg3 = new stdClass();
        $msg3->categoryid = $cat1->id;
        $msg3->msgto = $user2->id;
        $msg3->msgtarget = $user3->id;
        $msg3->msgtype = 'text';
        $msg3->msgdate = $timestart + 10000;
        $msg3->sended = 1;

        $DB->insert_record('local_eudest_msgs', $msg1);
        $DB->insert_record('local_eudest_msgs', $msg2);
        $DB->insert_record('local_eudest_msgs', $msg3);

        // Testing the function with user1 and cat1 (Expected result: return true).
        $result = $instance1->eude_revert($cat1->id, $timestart, $user1->username);
        $this->assertTrue($result);
        $eventsuser1 = $DB->get_records('event', array('userid' => $user1->id));
        $eventsuser2 = $DB->get_records('event', array('userid' => $user2->id));
        $this->assertCount(0, $eventsuser1);
        $this->assertCount(1, $eventsuser2);
        $msgcat1 = $DB->get_records('local_eudest_msgs', array('categoryid' => $cat1->id));
        $this->assertCount(1, $msgcat1);
        $updatedeudesenrolscat1 = $DB->get_records('local_eudest_enrols',
                array('userid' => $user1->id, 'categoryid' => $cat1->id, 'pend_event' => 1));
        $this->assertCount(2, $updatedeudesenrolscat1);
        // Testing the function with cat1 (Expected result: return true).
        $result = $instance1->eude_revert($cat1->id, $timestart);
        $this->assertTrue($result);

        // Testing the function with user1 and cat2 (Expected result: return true).
        $result = $instance1->eude_revert($cat2->id, $timestart, $user1->username);
        $this->assertTrue($result);

        // Testing the function with cat2 (Expected result: return true).
        $result = $instance1->eude_revert($cat2->id, $timestart);
        $this->assertTrue($result);

        // Testing the function with cat3 (Expected result: return false).
        $result = $instance1->eude_revert($cat3->id, $timestart);
        $this->assertFalse($result);
    }
}