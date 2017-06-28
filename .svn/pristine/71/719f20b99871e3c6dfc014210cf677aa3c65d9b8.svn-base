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
require_once(__DIR__ . '/local_eudest_revert.php');

defined('MOODLE_INTERNAL') || die();

require_login();

global $DB;

$url = new moodle_url("/local/eudest/revert.php");
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title( get_string('revert', 'local_eudest') );

$confirm = optional_param('confirm', 0, PARAM_INT);

echo $OUTPUT->header();
if ($confirm && confirm_sesskey()) {
    $categoryid = required_param('categoryid', PARAM_INT);
    $timestart = required_param('timestart', PARAM_INT);
    $username = optional_param('username', '', PARAM_TEXT);
    try {
        echo html_writer::tag('span', 'Reverting: Categoryid->'.$categoryid.',Timestart->'.$timestart.',Username->'.$username);
        echo html_writer::empty_tag('br');
        $revert = new local_eudest_revert();
        $revert->eude_revert($categoryid, $timestart, $username);
        echo html_writer::tag('span', 'REVERTED!!!');
        echo html_writer::empty_tag('br');
    } catch (Exception $ex) {
        echo $ex;
    }
}
echo html_writer::start_tag('form', array('action' => $url, 'style' => 'text-align: right;'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'confirm', 'value' => 1));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
echo html_writer::tag('label', 'Categoryid', array('for' => 'categoryid'));
echo html_writer::empty_tag('input',
        array('type' => 'text', 'name' => 'categoryid', 'id' => 'categoryid', 'required', 'value' => ''));
echo html_writer::empty_tag('br');
echo html_writer::tag('label', 'Timestart', array('for' => 'timestart'));
echo html_writer::empty_tag('input',
        array('type' => 'text', 'name' => 'timestart', 'id' => 'timestart', 'required', 'value' => ''));
echo html_writer::empty_tag('br');
echo html_writer::tag('label', 'Username', array('for' => 'username'));
echo html_writer::empty_tag('input',
        array('type' => 'text', 'name' => 'username', 'id' => 'username', 'value' => ''));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Send'));
echo html_writer::end_tag('form');
echo $OUTPUT->footer();