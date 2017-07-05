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
 * Configuration file of the plugin
 * @package    local_eudest
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_eudest', new lang_string('pluginname', 'local_eudest'));
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading('local_eudest_eventstitle', new lang_string('events', 'local_eudest'), ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_genenrolcalendar',
                new lang_string('enrolcalendar', 'local_eudest'), new lang_string('enrolcalendar_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_heading('local_eudest_noticestitle', new lang_string('messages', 'local_eudest'), ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_enrolnotice',
                new lang_string('rmenrolnotice', 'local_eudest'), new lang_string('rmenrolnotice_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_configtextarea('local_eudest_enroltext',
                new lang_string('customizemessage', 'local_eudest'), new lang_string('customizemessage_enroldesc', 'local_eudest'),
                ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_rmfinishnotice',
                new lang_string('rmfinishnotice', 'local_eudest'), new lang_string('rmfinishnotice_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_configtextarea('local_eudest_rmfinishtext',
                new lang_string('customizemessage', 'local_eudest'), new lang_string('customizemessage_rmfinish', 'local_eudest'),
                ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_stfinishnotice',
                new lang_string('studentfinishnotice', 'local_eudest'), new lang_string('studentfinishnotice_desc', 'local_eudest'),
                0, 1));

        $settings->add(new admin_setting_configtextarea('local_eudest_stfinishtext',
                new lang_string('customizemessage', 'local_eudest'), new lang_string('customizemessage_stfinish', 'local_eudest'),
                ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_inac6notice', new lang_string('inac6', 'local_eudest'),
                new lang_string('inac6_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_configtextarea('local_eudest_inac6text',
                new lang_string('customizemessage', 'local_eudest'), new lang_string('customizemessage_inac6desc', 'local_eudest'),
                ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_inac18notice', new lang_string('inac18', 'local_eudest'),
                new lang_string('inac18_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_configtextarea('local_eudest_inac18text',
                new lang_string('customizemessage', 'local_eudest'), new lang_string('customizemessage_inac18desc', 'local_eudest'),
                ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_inac24notice', new lang_string('inac24', 'local_eudest'),
                new lang_string('inac24_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_configtextarea('local_eudest_inac24rmtext',
                new lang_string('customizemessage', 'local_eudest'),
                new lang_string('customizemessage_inac24rmdesc', 'local_eudest'), ''));

        $settings->add(new admin_setting_configtextarea('local_eudest_inac24sttext',
                new lang_string('customizemessage', 'local_eudest'),
                new lang_string('customizemessage_inac24stdesc', 'local_eudest'), ''));

        $settings->add(new admin_setting_heading('local_eudest_overridetitle',
                new lang_string('gradeoverride', 'local_eudest'), ''));

        $settings->add(new admin_setting_configcheckbox('local_eudest_override', new lang_string('override', 'local_eudest'),
                new lang_string('override_desc', 'local_eudest'), 0, 1));

        $settings->add(new admin_setting_configcheckbox('local_eudest_convalidations',
                new lang_string('convalidations', 'local_eudest'), new lang_string('convalidations_desc', 'local_eudest'), 0, 1));
    }
    $ADMIN->add('localplugins', $settings);
}