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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->libdir . '/classes/date.php');

/**
 * Class for revert process of the schedule task
 *
 * @copyright  2016 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudest_revert {

    /**
     * Name of the plugin
     * @var string $pluginname
     */
    private $pluginname = "local_eudest";

    /**
     * Tag to identify the common course of a master
     * @var string $commoncoursetag
     */
    private $commoncoursetag = ".M00";

    /**
     * Tag to identify the intensive courses
     * @var string $intensivetag
     */
    private $intensivetag = "MI";

    /**
     * Tag to identify a module of a master
     * @var string $moduletag
     */
    private $moduletag = ".M";

    /**
     * Method for revert data
     * @param int $categoryid Id of the category
     * @param int $timestart Time start to find records
     * @param string $username Username of the user
     * @return boolean True if all ok
     */
    public function eude_revert($categoryid, $timestart, $username = false) {
        global $DB;

        // Get id user.
        if ($username) {
            $userid = $this->eude_revert_find_user($username);
            if ($userid == 0) {
                return false;
            }
            $this->eude_revert_user($categoryid, $userid, $timestart);
            return true;
        }

        $sql = "SELECT ue.id, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {role_assignments} ra ON ra.userid = ue.userid
                  JOIN {context} ct ON ra.contextid = ct.id
                  JOIN {role} r ON ra.roleid = r.id
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ct.instanceid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE c.category = :categoryid
                   AND r.shortname like '%student%'
                   AND ue.timestart >= :timestart
              ORDER BY ue.id ASC";
        $records = $DB->get_records_sql($sql, array('categoryid' => $categoryid, 'timestart' => $timestart));
        if (!$records) {
            return false;
        }
        foreach ($records as $record) {
            $this->eude_revert_user($categoryid, $record->userid, $timestart);
        }
        return true;
    }

    /**
     * Reverts all the data of an user
     * @param int $categoryid Id of the category
     * @param int $userid Id of the user
     * @param int $timestart Time start to find the records
     */
    private function eude_revert_user($categoryid, $userid, $timestart) {
        global $DB;
        // Delete events of user master.
        $this->eude_revert_delete_events($userid, $timestart, "[[COURSE]]");

        // Delete scheduled messages.
        $this->eude_revert_delete_messages($categoryid, $userid);

        // Update data and mark for processing.
        $this->eude_revert_recalculate_data($categoryid, $userid);

    }

    /**
     * Finds an user
     * @param string $username Username of the user
     * @return int Id of the user
     */
    private function eude_revert_find_user($username) {
        global $DB;
        $user = $DB->get_record('user', array('username' => $username));
        if ($user) {
            return $user->id;
        }
        return 0;
    }

    /**
     * Delete events in user calendar
     * @param int $userid Id owner of the events
     * @param int $timestart Time start to find records
     * @param string $type Type of the events
     */
    private function eude_revert_delete_events($userid, $timestart, $type) {
        global $DB;
        $sql = "SELECT *
                  FROM {event}
                 WHERE userid = :userid
                   AND eventtype = 'user'
                   AND name like CONCAT('$type', '%')
                   AND timestart >= :timestart
              ORDER BY id ASC";
        $records = $DB->get_records_sql($sql, array('userid' => $userid, 'timestart' => $timestart));
        foreach ($records as $record) {
            $eventid = $record->id;
            $event = calendar_event::load($eventid);
            $event->delete(true);
        }
    }

    /**
     * Deletes schedules messages
     * @param int $categoryid Id of the category
     * @param int $userid Id of the user
     */
    private function eude_revert_delete_messages($categoryid, $userid) {
        global $DB;
        $sqldel1 = "categoryid = :categoryid
                     AND msgto = :msgto";
        $DB->delete_records_select("local_eudest_msgs", $sqldel1, array("categoryid" => $categoryid, "msgto" => $userid));
        $sqldel2 = "categoryid = :categoryid
                 AND msgtarget = :msgtarget";
        $DB->delete_records_select("local_eudest_msgs", $sqldel2, array("categoryid" => $categoryid, "msgtarget" => $userid));
    }

    /**
     * Recalculate data based on the moodle tables
     * @param int $categoryid Id of the category
     * @param int $userid Id of the user
     */
    private function eude_revert_recalculate_data($categoryid, $userid) {
        global $DB;

        // Delete enrolments encapsulations.
        $DB->delete_records("local_eudest_masters", array("categoryid" => $categoryid, "userid" => $userid));

        // Delete enrolments.
        $DB->delete_records("local_eudest_enrols", array("categoryid" => $categoryid, "userid" => $userid));

        // Recalculate dates of enrols.
        $mintime = 0;
        $maxtime = 0;
        $sql = "SELECT ue.*, c.id courseid, c.shortname
                  FROM {user_enrolments} ue
                  JOIN {role_assignments} ra ON ra.userid = ue.userid
                  JOIN {context} ct ON ra.contextid = ct.id
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = ct.instanceid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE ue.userid=:userid
                   AND c.category=:categoryid
              ORDER BY timestart asc";
        $records = $DB->get_records_sql($sql, array('userid' => $userid, 'categoryid' => $categoryid));
        foreach ($records as $record) {
            if ($mintime == 0) {
                $mintime = $record->timestart;
                $maxtime = $record->timeend;
            }
            if ($record->timestart < $mintime) {
                $mintime = $record->timestart;
            }
            if ($record->timeend > $maxtime) {
                $maxtime = $record->timeend;
            }

            // Create enrolment and mark for processing.
            $enrol = new stdClass();
            $enrol->userid = $userid;
            $enrol->courseid = $record->courseid;
            $enrol->shortname = $record->shortname;
            $enrol->startdate = $record->timestart;
            $enrol->enddate = $record->timeend;
            $enrol->categoryid = $categoryid;
            $pendevent = 1;
            if (strrpos($record->shortname, $this->commoncoursetag)) {
                $pendevent = 0;
            }
            $enrol->pend_event = $pendevent;
            $pdteencapsulation = 1;
            $pos = strrpos($record->shortname, $this->moduletag);
            if ($pos == false) {
                $pdteencapsulation = 0;
            }
            $enrol->pend_encapsulation = $pdteencapsulation;
            $pdteconvalidation = 1;
            $pos1 = strrpos($record->shortname, "[-");
            $pos2 = strrpos($record->shortname, "-]");
            if ($pos1 == false || $pos2 == false) {
                $pdteconvalidation = 0;
            }
            $enrol->pend_convalidation = $pdteconvalidation;
            $intensive = 0;
            $sub = explode('.', $record->shortname);
            if ($sub[0] == $this->intensivetag) {
                $intensive = 1;
            }
            $enrol->intensive = $intensive;
            $enrol->masterid = 0;
            $DB->insert_record('local_eudest_enrols', $enrol, false);
        }
    }
}