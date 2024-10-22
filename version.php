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
 * exescorm version information.
 *
 * @package    mod_exescorm
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2023120400;    // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2020061500;    // Moodle 3.9 to 4.1.
$plugin->component = 'mod_exescorm';   // Full name of the plugin (used for diagnostics).

$plugin->maturity  = MATURITY_BETA;   // This is a stable version.
$plugin->release   = 'v3.0.0';            // This is the first release of this version.

