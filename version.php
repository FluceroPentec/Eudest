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
 * This plugin checks for new enrolments and process them.
 *
 * @package    local_eudest
 * @copyright  2016 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2016092000;  // YYYYMMDDHH (year, month, day, 24-hr time).
$plugin->requires = 2015050500; // YYYYMMDDHH (I tried with 2015110900 for 2.9.3 but it doesn't work).
                                // Default plugins has 2015050500 after installation.
$plugin->component = 'local_eudest';
$plugin->maturity = MATURITY_STABLE;
$plugin->cron = 60;
$plugin->release = 'v1.0.0';