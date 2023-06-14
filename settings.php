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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/exescorm/locallib.php');
    $yesno = [0 => get_string('no'),
                   1 => get_string('yes')];

    // Connection settings.
    $settings->add(new admin_setting_heading('exescorm/connectionsettings',
        get_string('exeonline:connectionsettings', 'mod_exescorm'), ''));

    $settings->add(new admin_setting_configtext('exescorm/exeonlinebaseuri',
        get_string('exeonline:baseuri', 'mod_exescorm'),
        get_string('exeonline:baseuri_desc', 'mod_exescorm'), '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configpasswordunmask('exescorm/hmackey1',
        get_string('exeonline:hmackey1', 'mod_exescorm'),
        get_string('exeonline:hmackey1_desc', 'mod_exescorm'), ''));

    $settings->add(new admin_setting_configduration('exescorm/tokenexpiration',
        get_string('exeonline:tokenexpiration', 'mod_exescorm'),
        get_string('exeonline:tokenexpiration_desc', 'mod_exescorm'), 86400, 1));

    // Exescorm default template.
    $filemanageroptions = [
        'accepted_types' => ['.zip'],
        'maxbytes' => 0,
        'maxfiles' => 1,
        'subdirs' => 0,
    ];

    $settings->add(new admin_setting_configstoredfile('exescorm/template',
        get_string('exescorm:template', 'mod_exescorm'),
        get_string('exescorm:template_desc', 'mod_exescorm'),
        'config', 0, $filemanageroptions
    ));

    $settings->add(new admin_setting_configcheckbox('exescorm/sendtemplate',
        get_string('exescorm:sendtemplate', 'exescorm'), get_string('exescorm:sendtemplate_desc', 'exescorm'), 0));

    // eXescorm package validation rules.
    $mandatoryfilesre = implode("\n", [
        '/^contentv[\d+]\.xml$/',
        '/^content\.xsd$/',
        '/^content\.data$/',
    ]);
    $forbiddenfilesre = implode("\n", [
        '/.*\.php$/',
    ]);
    $settings->add(new admin_setting_configtextarea('exescorm/mandatoryfileslist',
        new lang_string('exescorm:mandatoryfileslist', 'mod_exescorm'),
        new lang_string('exescorm:mandatoryfileslist_desc', 'mod_exescorm'), $mandatoryfilesre, PARAM_RAW, '50', '10'));
    $settings->add(new admin_setting_configtextarea('exescorm/forbiddenfileslist',
        new lang_string('exescorm:forbiddenfileslist', 'mod_exescorm'),
        new lang_string('exescorm:forbiddenfileslist_desc', 'mod_exescorm'), $forbiddenfilesre, PARAM_RAW, '50', '10'));

    // Default display settings.
    $settings->add(new admin_setting_heading('exescorm/displaysettings',
        get_string('defaultdisplaysettings', 'exescorm'), ''));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/displaycoursestructure',
        get_string('displaycoursestructure', 'exescorm'), get_string('displaycoursestructuredesc', 'exescorm'),
        ['value' => 0, 'adv' => false], $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/popup',
        get_string('display', 'exescorm'), get_string('displaydesc', 'exescorm'),
        ['value' => 0, 'adv' => false], exescorm_get_popup_display_array()));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/framewidth',
        get_string('width', 'exescorm'), get_string('framewidth', 'exescorm'),
        ['value' => '100', 'adv' => true]));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/frameheight',
        get_string('height', 'exescorm'), get_string('frameheight', 'exescorm'),
        ['value' => '500', 'adv' => true]));

    $settings->add(new admin_setting_configcheckbox('exescorm/winoptgrp_adv',
         get_string('optionsadv', 'exescorm'), get_string('optionsadv_desc', 'exescorm'), 1));

    foreach (exescorm_get_popup_options_array() as $key => $value) {
        $settings->add(new admin_setting_configcheckbox('exescorm/'.$key,
            get_string($key, 'exescorm'), '', $value));
    }

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/skipview',
        get_string('skipview', 'exescorm'), get_string('skipviewdesc', 'exescorm'),
        ['value' => 2, 'adv' => true], exescorm_get_skip_view_array()));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/hidebrowse',
        get_string('hidebrowse', 'exescorm'), get_string('hidebrowsedesc', 'exescorm'),
        ['value' => 0, 'adv' => true], $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/hidetoc',
        get_string('hidetoc', 'exescorm'), get_string('hidetocdesc', 'exescorm'),
        ['value' => 0, 'adv' => true], exescorm_get_hidetoc_array()));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/nav',
        get_string('nav', 'exescorm'), get_string('navdesc', 'exescorm'),
        ['value' => EXESCORM_NAV_UNDER_CONTENT, 'adv' => true], exescorm_get_navigation_display_array()));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/navpositionleft',
        get_string('fromleft', 'exescorm'), get_string('navpositionleft', 'exescorm'),
        ['value' => -100, 'adv' => true]));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/navpositiontop',
        get_string('fromtop', 'exescorm'), get_string('navpositiontop', 'exescorm'),
        ['value' => -100, 'adv' => true]));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/collapsetocwinsize',
        get_string('collapsetocwinsize', 'exescorm'), get_string('collapsetocwinsizedesc', 'exescorm'),
        ['value' => 767, 'adv' => true]));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/displayattemptstatus',
        get_string('displayattemptstatus', 'exescorm'), get_string('displayattemptstatusdesc', 'exescorm'),
        ['value' => 1, 'adv' => false], exescorm_get_attemptstatus_array()));

    // Default grade settings.
    $settings->add(new admin_setting_heading('exescorm/gradesettings', get_string('defaultgradesettings', 'exescorm'), ''));
    $settings->add(new admin_setting_configselect('exescorm/grademethod',
        get_string('grademethod', 'exescorm'), get_string('grademethoddesc', 'exescorm'),
        EXESCORM_GRADEHIGHEST, exescorm_get_grade_method_array()));

    for ($i = 0; $i <= 100; $i++) {
        $grades[$i] = "$i";
    }

    $settings->add(new admin_setting_configselect('exescorm/maxgrade',
        get_string('maximumgrade'), get_string('maximumgradedesc', 'exescorm'), 100, $grades));

    $settings->add(new admin_setting_heading('exescorm/othersettings', get_string('defaultothersettings', 'exescorm'), ''));

    // Default attempts settings.
    $settings->add(new admin_setting_configselect('exescorm/maxattempt',
        get_string('maximumattempts', 'exescorm'), '', '0', exescorm_get_attempts_array()));

    $settings->add(new admin_setting_configselect('exescorm/whatgrade',
        get_string('whatgrade', 'exescorm'), get_string('whatgradedesc', 'exescorm'),
        EXESCORM_HIGHESTATTEMPT, exescorm_get_what_grade_array()));

    $settings->add(new admin_setting_configselect('exescorm/forcecompleted',
        get_string('forcecompleted', 'exescorm'), get_string('forcecompleteddesc', 'exescorm'), 0, $yesno));

    $forceattempts = exescorm_get_forceattempt_array();
    $settings->add(new admin_setting_configselect('exescorm/forcenewattempt',
        get_string('forcenewattempts', 'exescorm'), get_string('forcenewattempts_help', 'exescorm'), 0, $forceattempts));

    $settings->add(new admin_setting_configselect('exescorm/autocommit',
    get_string('autocommit', 'exescorm'), get_string('autocommitdesc', 'exescorm'), 0, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/masteryoverride',
        get_string('masteryoverride', 'exescorm'), get_string('masteryoverridedesc', 'exescorm'), 1, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/lastattemptlock',
        get_string('lastattemptlock', 'exescorm'), get_string('lastattemptlockdesc', 'exescorm'), 0, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/auto',
        get_string('autocontinue', 'exescorm'), get_string('autocontinuedesc', 'exescorm'), 0, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/updatefreq',
                                                    get_string('updatefreq', 'exescorm'),
                                                    get_string('updatefreqdesc', 'exescorm'),
                                                    0, exescorm_get_updatefreq_array()));

    // Admin level settings.
    $settings->add(new admin_setting_heading('exescorm/adminsettings', get_string('adminsettings', 'exescorm'), ''));

    $settings->add(new admin_setting_configcheckbox('exescorm/exescormstandard',
                                                    get_string('exescormstandard', 'exescorm'),
                                                    get_string('exescormstandarddesc', 'exescorm'), 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowtypeexternal',
                                                    get_string('allowtypeexternal', 'exescorm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowtypelocalsync',
                                                    get_string('allowtypelocalsync', 'exescorm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowtypeexternalaicc',
        get_string('allowtypeexternalaicc', 'exescorm'), get_string('allowtypeexternalaicc_desc', 'exescorm'), 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowaicchacp', get_string('allowtypeaicchacp', 'exescorm'),
                                                    get_string('allowtypeaicchacp_desc', 'exescorm'), 0));

    $settings->add(new admin_setting_configtext('exescorm/aicchacptimeout',
        get_string('aicchacptimeout', 'exescorm'), get_string('aicchacptimeout_desc', 'exescorm'),
        30, PARAM_INT));

    $settings->add(new admin_setting_configtext('exescorm/aicchacpkeepsessiondata',
        get_string('aicchacpkeepsessiondata', 'exescorm'), get_string('aicchacpkeepsessiondata_desc', 'exescorm'),
        1, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('exescorm/aiccuserid', get_string('aiccuserid', 'exescorm'),
                                                    get_string('aiccuserid_desc', 'exescorm'), 1));

    $settings->add(new admin_setting_configcheckbox('exescorm/forcejavascript', get_string('forcejavascript', 'exescorm'),
                                                    get_string('forcejavascript_desc', 'exescorm'), 1));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowapidebug',
                                                    get_string('allowapidebug', 'exescorm'), '', 0));

    $settings->add(new admin_setting_configtext('exescorm/apidebugmask', get_string('apidebugmask', 'exescorm'), '', '.*'));

    $settings->add(new admin_setting_configcheckbox('exescorm/protectpackagedownloads',
                                                    get_string('protectpackagedownloads', 'exescorm'),
                                                    get_string('protectpackagedownloads_desc', 'exescorm'), 0));

}
