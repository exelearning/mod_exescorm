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
        get_string('exescorm:sendtemplate', 'mod_exescorm'), get_string('exescorm:sendtemplate_desc', 'mod_exescorm'), 0));

    // The eXescorm package validation rules.
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
        get_string('defaultdisplaysettings', 'mod_exescorm'), ''));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/displaycoursestructure',
        get_string('displaycoursestructure', 'mod_exescorm'), get_string('displaycoursestructuredesc', 'mod_exescorm'),
        ['value' => 0, 'adv' => false], $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/popup',
        get_string('display', 'mod_exescorm'), get_string('displaydesc', 'mod_exescorm'),
        ['value' => 0, 'adv' => false], exescorm_get_popup_display_array()));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/framewidth',
        get_string('width', 'mod_exescorm'), get_string('framewidth', 'mod_exescorm'),
        ['value' => '100', 'adv' => true]));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/frameheight',
        get_string('height', 'mod_exescorm'), get_string('frameheight', 'mod_exescorm'),
        ['value' => '500', 'adv' => true]));

    $settings->add(new admin_setting_configcheckbox('exescorm/winoptgrp_adv',
         get_string('optionsadv', 'mod_exescorm'), get_string('optionsadv_desc', 'mod_exescorm'), 1));

    foreach (exescorm_get_popup_options_array() as $key => $value) {
        $settings->add(new admin_setting_configcheckbox('exescorm/'.$key,
            get_string($key, 'mod_exescorm'), '', $value));
    }

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/skipview',
        get_string('skipview', 'mod_exescorm'), get_string('skipviewdesc', 'mod_exescorm'),
        ['value' => 2, 'adv' => true], exescorm_get_skip_view_array()));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/hidebrowse',
        get_string('hidebrowse', 'mod_exescorm'), get_string('hidebrowsedesc', 'mod_exescorm'),
        ['value' => 0, 'adv' => true], $yesno));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/hidetoc',
        get_string('hidetoc', 'mod_exescorm'), get_string('hidetocdesc', 'mod_exescorm'),
        ['value' => 0, 'adv' => true], exescorm_get_hidetoc_array()));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/nav',
        get_string('nav', 'mod_exescorm'), get_string('navdesc', 'mod_exescorm'),
        ['value' => EXESCORM_NAV_UNDER_CONTENT, 'adv' => true], exescorm_get_navigation_display_array()));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/navpositionleft',
        get_string('fromleft', 'mod_exescorm'), get_string('navpositionleft', 'mod_exescorm'),
        ['value' => -100, 'adv' => true]));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/navpositiontop',
        get_string('fromtop', 'mod_exescorm'), get_string('navpositiontop', 'mod_exescorm'),
        ['value' => -100, 'adv' => true]));

    $settings->add(new admin_setting_configtext_with_advanced('exescorm/collapsetocwinsize',
        get_string('collapsetocwinsize', 'mod_exescorm'), get_string('collapsetocwinsizedesc', 'mod_exescorm'),
        ['value' => 767, 'adv' => true]));

    $settings->add(new admin_setting_configselect_with_advanced('exescorm/displayattemptstatus',
        get_string('displayattemptstatus', 'mod_exescorm'), get_string('displayattemptstatusdesc', 'mod_exescorm'),
        ['value' => 1, 'adv' => false], exescorm_get_attemptstatus_array()));

    // Default grade settings.
    $settings->add(new admin_setting_heading('exescorm/gradesettings', get_string('defaultgradesettings', 'mod_exescorm'), ''));
    $settings->add(new admin_setting_configselect('exescorm/grademethod',
        get_string('grademethod', 'mod_exescorm'), get_string('grademethoddesc', 'mod_exescorm'),
        EXESCORM_GRADEHIGHEST, exescorm_get_grade_method_array()));

    for ($i = 0; $i <= 100; $i++) {
        $grades[$i] = "$i";
    }

    $settings->add(new admin_setting_configselect('exescorm/maxgrade',
        get_string('maximumgrade'), get_string('maximumgradedesc', 'mod_exescorm'), 100, $grades));

    $settings->add(new admin_setting_heading('exescorm/othersettings', get_string('defaultothersettings', 'mod_exescorm'), ''));

    // Default attempts settings.
    $settings->add(new admin_setting_configselect('exescorm/maxattempt',
        get_string('maximumattempts', 'mod_exescorm'), '', '0', exescorm_get_attempts_array()));

    $settings->add(new admin_setting_configselect('exescorm/whatgrade',
        get_string('whatgrade', 'mod_exescorm'), get_string('whatgradedesc', 'mod_exescorm'),
        EXESCORM_HIGHESTATTEMPT, exescorm_get_what_grade_array()));

    $settings->add(new admin_setting_configselect('exescorm/forcecompleted',
        get_string('forcecompleted', 'mod_exescorm'), get_string('forcecompleteddesc', 'mod_exescorm'), 0, $yesno));

    $forceattempts = exescorm_get_forceattempt_array();
    $settings->add(new admin_setting_configselect('exescorm/forcenewattempt',
        get_string('forcenewattempts', 'mod_exescorm'), get_string('forcenewattempts_help', 'mod_exescorm'), 0, $forceattempts));

    $settings->add(new admin_setting_configselect('exescorm/autocommit',
    get_string('autocommit', 'mod_exescorm'), get_string('autocommitdesc', 'mod_exescorm'), 0, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/masteryoverride',
        get_string('masteryoverride', 'mod_exescorm'), get_string('masteryoverridedesc', 'mod_exescorm'), 1, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/lastattemptlock',
        get_string('lastattemptlock', 'mod_exescorm'), get_string('lastattemptlockdesc', 'mod_exescorm'), 0, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/auto',
        get_string('autocontinue', 'mod_exescorm'), get_string('autocontinuedesc', 'mod_exescorm'), 0, $yesno));

    $settings->add(new admin_setting_configselect('exescorm/updatefreq',
                                                    get_string('updatefreq', 'mod_exescorm'),
                                                    get_string('updatefreqdesc', 'mod_exescorm'),
                                                    0, exescorm_get_updatefreq_array()));

    // Admin level settings.
    $settings->add(new admin_setting_heading('exescorm/adminsettings', get_string('adminsettings', 'mod_exescorm'), ''));

    $settings->add(new admin_setting_configcheckbox('exescorm/exescormstandard',
                                                    get_string('exescormstandard', 'mod_exescorm'),
                                                    get_string('exescormstandarddesc', 'mod_exescorm'), 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowtypeexternal',
                                                    get_string('allowtypeexternal', 'mod_exescorm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowtypelocalsync',
                                                    get_string('allowtypelocalsync', 'mod_exescorm'), '', 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowtypeexternalaicc',
        get_string('allowtypeexternalaicc', 'mod_exescorm'), get_string('allowtypeexternalaicc_desc', 'mod_exescorm'), 0));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowaicchacp', get_string('allowtypeaicchacp', 'mod_exescorm'),
                                                    get_string('allowtypeaicchacp_desc', 'mod_exescorm'), 0));

    $settings->add(new admin_setting_configtext('exescorm/aicchacptimeout',
        get_string('aicchacptimeout', 'mod_exescorm'), get_string('aicchacptimeout_desc', 'mod_exescorm'),
        30, PARAM_INT));

    $settings->add(new admin_setting_configtext('exescorm/aicchacpkeepsessiondata',
        get_string('aicchacpkeepsessiondata', 'mod_exescorm'), get_string('aicchacpkeepsessiondata_desc', 'mod_exescorm'),
        1, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('exescorm/aiccuserid', get_string('aiccuserid', 'mod_exescorm'),
                                                    get_string('aiccuserid_desc', 'mod_exescorm'), 1));

    $settings->add(new admin_setting_configcheckbox('exescorm/forcejavascript', get_string('forcejavascript', 'mod_exescorm'),
                                                    get_string('forcejavascript_desc', 'mod_exescorm'), 1));

    $settings->add(new admin_setting_configcheckbox('exescorm/allowapidebug',
                                                    get_string('allowapidebug', 'mod_exescorm'), '', 0));

    $settings->add(new admin_setting_configtext('exescorm/apidebugmask', get_string('apidebugmask', 'mod_exescorm'), '', '.*'));

    $settings->add(new admin_setting_configcheckbox('exescorm/protectpackagedownloads',
                                                    get_string('protectpackagedownloads', 'mod_exescorm'),
                                                    get_string('protectpackagedownloads_desc', 'mod_exescorm'), 0));

}
