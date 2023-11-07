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

use mod_exescorm\exeonline\exescorm_redirector;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

class mod_exescorm_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE, $OUTPUT, $PAGE;
        $cfgexescorm = get_config('exescorm');

        $mform = $this->_form;

        if (!$CFG->slasharguments) {
            $mform->addElement('static', '', '',
                                $OUTPUT->notification(get_string('slashargs', 'mod_exescorm'), 'notifyproblem'));
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Summary.
        $this->standard_intro_elements();

        // Package.
        $mform->addElement('header', 'packagehdr', get_string('packagehdr', 'mod_exescorm'));
        $mform->setExpanded('packagehdr', true);

        $editmode = !empty($this->_instance);
        // Package types.
        $exescormtypes = [
            EXESCORM_TYPE_LOCAL => get_string('typelocal', 'mod_exescorm'),
        ];
        $defaulttype = EXESCORM_TYPE_LOCAL;
        if (!empty($cfgexescorm->exeonlinebaseuri) && !empty($cfgexescorm->hmackey1)) {
            if ($editmode) {
                $exescormtypes[EXESCORM_TYPE_EXESCORMNET] = get_string('typeexescormedit', 'mod_exescorm');
            } else {
                $exescormtypes[EXESCORM_TYPE_EXESCORMNET] = get_string('typeexescormcreate', 'mod_exescorm');
                $defaulttype = EXESCORM_TYPE_EXESCORMNET;
            }
        }
        if ($cfgexescorm->allowtypeexternal) {
            $exescormtypes[EXESCORM_TYPE_EXTERNAL] = get_string('typeexternal', 'mod_exescorm');
        }

        if ($cfgexescorm->allowtypelocalsync) {
            $exescormtypes[EXESCORM_TYPE_LOCALSYNC] = get_string('typelocalsync', 'mod_exescorm');
        }

        if ($cfgexescorm->allowtypeexternalaicc) {
            $exescormtypes[EXESCORM_TYPE_AICCURL] = get_string('typeaiccurl', 'mod_exescorm');
        }

        $nonfilepickertypes = [
                EXESCORM_TYPE_EXESCORMNET,
            ];
        // Reference.
        $mform->addElement('select', 'exescormtype', get_string('exescormtype', 'mod_exescorm'), $exescormtypes);
        $mform->setDefault('exescormtype', $defaulttype);
        $mform->setType('exescormtype', PARAM_ALPHA);
        $mform->addHelpButton('exescormtype', 'exescormtype', 'exescorm');
        $mform->addElement('text', 'packageurl', get_string('packageurl', 'mod_exescorm'), array('size' => 60));
        $mform->setType('packageurl', PARAM_RAW);
        $mform->addHelpButton('packageurl', 'packageurl', 'exescorm');
        $mform->hideIf('packageurl', 'exescormtype', 'in', [EXESCORM_TYPE_LOCAL, EXESCORM_TYPE_EXESCORMNET]);
        // Workarround to hide static element.
        $group = [];
        $staticelement = $mform->createElement('static', 'onlinetypehelp', '',
                                                get_string('exescorm:onlinetypehelp', 'mod_exescorm'));
        $staticelement->updateAttributes(['class' => 'font-weight-bold']);
        $group[] =& $staticelement;
        $mform->addGroup($group, 'typehelpgroup', '', ' ', false);
        $mform->hideIf('typehelpgroup', 'exescormtype', 'noteq', EXESCORM_TYPE_EXESCORMNET);
        // New local package upload.
        $filemanageroptions = array();
        $filemanageroptions['accepted_types'] = array('.zip', '.xml');
        $filemanageroptions['maxbytes'] = 0;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['subdirs'] = 0;

        $mform->addElement('filemanager', 'packagefile', get_string('package', 'mod_exescorm'), null, $filemanageroptions);
        $mform->addHelpButton('packagefile', 'package', 'exescorm');
        $mform->hideIf('packagefile', 'exescormtype', 'in', $nonfilepickertypes);

        // Update packages timing.
        $mform->addElement('select', 'updatefreq', get_string('updatefreq', 'mod_exescorm'), exescorm_get_updatefreq_array());
        $mform->setType('updatefreq', PARAM_INT);
        $mform->setDefault('updatefreq', $cfgexescorm->updatefreq);
        $mform->addHelpButton('updatefreq', 'updatefreq', 'exescorm');
        $mform->hideIf('updatefreq', 'exescormtype', 'eq', EXESCORM_TYPE_EXESCORMNET);

        // Display Settings.
        $mform->addElement('header', 'displaysettings', get_string('appearance'));

        // Framed / Popup Window.
        $mform->addElement('select', 'popup', get_string('display', 'mod_exescorm'), exescorm_get_popup_display_array());
        $mform->setDefault('popup', $cfgexescorm->popup);
        $mform->setAdvanced('popup', $cfgexescorm->popup_adv);

        // Width.
        $mform->addElement('text', 'width', get_string('width', 'mod_exescorm'), 'maxlength="5" size="5"');
        $mform->setDefault('width', $cfgexescorm->framewidth);
        $mform->setType('width', PARAM_INT);
        $mform->setAdvanced('width', $cfgexescorm->framewidth_adv);
        $mform->hideIf('width', 'popup', 'eq', 0);

        // Height.
        $mform->addElement('text', 'height', get_string('height', 'mod_exescorm'), 'maxlength="5" size="5"');
        $mform->setDefault('height', $cfgexescorm->frameheight);
        $mform->setType('height', PARAM_INT);
        $mform->setAdvanced('height', $cfgexescorm->frameheight_adv);
        $mform->hideIf('height', 'popup', 'eq', 0);

        // Window Options.
        $winoptgrp = array();
        foreach (exescorm_get_popup_options_array() as $key => $value) {
            $winoptgrp[] = &$mform->createElement('checkbox', $key, '', get_string($key, 'mod_exescorm'));
            $mform->setDefault($key, $value);
        }
        $mform->addGroup($winoptgrp, 'winoptgrp', get_string('options', 'mod_exescorm'), '<br />', false);
        $mform->hideIf('winoptgrp', 'popup', 'eq', 0);
        $mform->setAdvanced('winoptgrp', $cfgexescorm->winoptgrp_adv);

        // Skip view page.
        $skipviewoptions = exescorm_get_skip_view_array();
        $mform->addElement('select', 'skipview', get_string('skipview', 'mod_exescorm'), $skipviewoptions);
        $mform->addHelpButton('skipview', 'skipview', 'exescorm');
        $mform->setDefault('skipview', $cfgexescorm->skipview);
        $mform->setAdvanced('skipview', $cfgexescorm->skipview_adv);

        // Hide Browse.
        $mform->addElement('selectyesno', 'hidebrowse', get_string('hidebrowse', 'mod_exescorm'));
        $mform->addHelpButton('hidebrowse', 'hidebrowse', 'exescorm');
        $mform->setDefault('hidebrowse', $cfgexescorm->hidebrowse);
        $mform->setAdvanced('hidebrowse', $cfgexescorm->hidebrowse_adv);

        // Display course structure.
        $mform->addElement('selectyesno', 'displaycoursestructure', get_string('displaycoursestructure', 'mod_exescorm'));
        $mform->addHelpButton('displaycoursestructure', 'displaycoursestructure', 'exescorm');
        $mform->setDefault('displaycoursestructure', $cfgexescorm->displaycoursestructure);
        $mform->setAdvanced('displaycoursestructure', $cfgexescorm->displaycoursestructure_adv);

        // Toc display.
        $mform->addElement('select', 'hidetoc', get_string('hidetoc', 'mod_exescorm'), exescorm_get_hidetoc_array());
        $mform->addHelpButton('hidetoc', 'hidetoc', 'exescorm');
        $mform->setDefault('hidetoc', $cfgexescorm->hidetoc);
        $mform->setAdvanced('hidetoc', $cfgexescorm->hidetoc_adv);
        $mform->disabledIf('hidetoc', 'exescormtype', 'eq', EXESCORM_TYPE_AICCURL);

        // Navigation panel display.
        $mform->addElement('select', 'nav', get_string('nav', 'mod_exescorm'), exescorm_get_navigation_display_array());
        $mform->addHelpButton('nav', 'nav', 'exescorm');
        $mform->setDefault('nav', $cfgexescorm->nav);
        $mform->setAdvanced('nav', $cfgexescorm->nav_adv);
        $mform->hideIf('nav', 'hidetoc', 'noteq', EXESCORM_TOC_SIDE);

        // Navigation panel position from left.
        $mform->addElement('text', 'navpositionleft', get_string('fromleft', 'mod_exescorm'), 'maxlength="5" size="5"');
        $mform->setDefault('navpositionleft', $cfgexescorm->navpositionleft);
        $mform->setType('navpositionleft', PARAM_INT);
        $mform->setAdvanced('navpositionleft', $cfgexescorm->navpositionleft_adv);
        $mform->hideIf('navpositionleft', 'hidetoc', 'noteq', EXESCORM_TOC_SIDE);
        $mform->hideIf('navpositionleft', 'nav', 'noteq', EXESCORM_NAV_FLOATING);

        // Navigation panel position from top.
        $mform->addElement('text', 'navpositiontop', get_string('fromtop', 'mod_exescorm'), 'maxlength="5" size="5"');
        $mform->setDefault('navpositiontop', $cfgexescorm->navpositiontop);
        $mform->setType('navpositiontop', PARAM_INT);
        $mform->setAdvanced('navpositiontop', $cfgexescorm->navpositiontop_adv);
        $mform->hideIf('navpositiontop', 'hidetoc', 'noteq', EXESCORM_TOC_SIDE);
        $mform->hideIf('navpositiontop', 'nav', 'noteq', EXESCORM_NAV_FLOATING);

        // Display attempt status.
        $mform->addElement('select', 'displayattemptstatus', get_string('displayattemptstatus', 'mod_exescorm'),
                           exescorm_get_attemptstatus_array());
        $mform->addHelpButton('displayattemptstatus', 'displayattemptstatus', 'exescorm');
        $mform->setDefault('displayattemptstatus', $cfgexescorm->displayattemptstatus);
        $mform->setAdvanced('displayattemptstatus', $cfgexescorm->displayattemptstatus_adv);

        // Availability.
        $mform->addElement('header', 'availability', get_string('availability'));

        $mform->addElement('date_time_selector', 'timeopen',
                        get_string("exescormopen", "mod_exescorm"), array('optional' => true));
        $mform->addElement('date_time_selector', 'timeclose',
                        get_string("exescormclose", "mod_exescorm"), array('optional' => true));

        // Grade Settings.
        $mform->addElement('header', 'gradesettings', get_string('gradenoun', 'mod_exescorm'));

        // Grade Method.
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'mod_exescorm'), exescorm_get_grade_method_array());
        $mform->addHelpButton('grademethod', 'grademethod', 'exescorm');
        $mform->setDefault('grademethod', $cfgexescorm->grademethod);

        // Maximum Grade.
        for ($i = 0; $i <= 100; $i++) {
            $grades[$i] = "$i";
        }
        $mform->addElement('select', 'maxgrade', get_string('maximumgrade'), $grades);
        $mform->setDefault('maxgrade', $cfgexescorm->maxgrade);
        $mform->hideIf('maxgrade', 'grademethod', 'eq', EXESCORM_GRADESCOES);

        // Attempts management.
        $mform->addElement('header', 'attemptsmanagementhdr', get_string('attemptsmanagement', 'mod_exescorm'));

        // Max Attempts.
        $mform->addElement('select', 'maxattempt', get_string('maximumattempts', 'mod_exescorm'), exescorm_get_attempts_array());
        $mform->addHelpButton('maxattempt', 'maximumattempts', 'exescorm');
        $mform->setDefault('maxattempt', $cfgexescorm->maxattempt);

        // What Grade.
        $mform->addElement('select', 'whatgrade', get_string('whatgrade', 'mod_exescorm'),  exescorm_get_what_grade_array());
        $mform->hideIf('whatgrade', 'maxattempt', 'eq', 1);
        $mform->addHelpButton('whatgrade', 'whatgrade', 'exescorm');
        $mform->setDefault('whatgrade', $cfgexescorm->whatgrade);

        // Force new attempt.
        $newattemptselect = exescorm_get_forceattempt_array();
        $mform->addElement('select', 'forcenewattempt', get_string('forcenewattempts', 'mod_exescorm'), $newattemptselect);
        $mform->addHelpButton('forcenewattempt', 'forcenewattempts', 'exescorm');
        $mform->setDefault('forcenewattempt', $cfgexescorm->forcenewattempt);

        // Last attempt lock - lock the enter button after the last available attempt has been made.
        $mform->addElement('selectyesno', 'lastattemptlock', get_string('lastattemptlock', 'mod_exescorm'));
        $mform->addHelpButton('lastattemptlock', 'lastattemptlock', 'exescorm');
        $mform->setDefault('lastattemptlock', $cfgexescorm->lastattemptlock);

        // Compatibility settings.
        $mform->addElement('header', 'compatibilitysettingshdr', get_string('compatibilitysettings', 'mod_exescorm'));

        // Force completed.
        $mform->addElement('selectyesno', 'forcecompleted', get_string('forcecompleted', 'mod_exescorm'));
        $mform->addHelpButton('forcecompleted', 'forcecompleted', 'exescorm');
        $mform->setDefault('forcecompleted', $cfgexescorm->forcecompleted);

        // Autocontinue.
        $mform->addElement('selectyesno', 'auto', get_string('autocontinue', 'mod_exescorm'));
        $mform->addHelpButton('auto', 'autocontinue', 'exescorm');
        $mform->setDefault('auto', $cfgexescorm->auto);

        // Autocommit.
        $mform->addElement('selectyesno', 'autocommit', get_string('autocommit', 'mod_exescorm'));
        $mform->addHelpButton('autocommit', 'autocommit', 'exescorm');
        $mform->setDefault('autocommit', $cfgexescorm->autocommit);

        // Mastery score overrides status.
        $mform->addElement('selectyesno', 'masteryoverride', get_string('masteryoverride', 'mod_exescorm'));
        $mform->addHelpButton('masteryoverride', 'masteryoverride', 'exescorm');
        $mform->setDefault('masteryoverride', $cfgexescorm->masteryoverride);

        // Hidden Settings.
        $mform->addElement('hidden', 'datadir', null);
        $mform->setType('datadir', PARAM_RAW);
        $mform->addElement('hidden', 'pkgtype', null);
        $mform->setType('pkgtype', PARAM_RAW);
        $mform->addElement('hidden', 'launch', null);
        $mform->setType('launch', PARAM_RAW);
        $mform->addElement('hidden', 'redirect', null);
        $mform->setType('redirect', PARAM_RAW);
        $mform->addElement('hidden', 'redirecturl', null);
        $mform->setType('redirecturl', PARAM_RAW);

        $this->standard_coursemodule_elements();

        // A EXESCORM module should define this within itself and is not needed here.
        if ($mform->elementExists('completionpassgrade')) {
            $mform->removeElement('completionpassgrade');
        }

        // Buttons.
        $this->add_action_buttons();
        $mform->hideIf('buttonar', 'exescormtype', 'eq', EXESCORM_TYPE_EXESCORMNET);

        $this->add_edit_online_buttons('editonlinearr');
        $mform->hideIf('editonlinearr', 'exescormtype', 'noteq', EXESCORM_TYPE_EXESCORMNET);
    }


    /**
     * Generate buttons within a group with alternative texts.
     *
     * @param string $groupname
     * @return void
     */
    public function add_edit_online_buttons($groupname) {
        $submitlabel = get_string('exescorm:editonlineanddisplay', 'mod_exescorm');
        $submit2label = get_string('exescorm:editonlineandreturntocourse', 'mod_exescorm');

        $mform = $this->_form;

        // Elements in a row need a group.
        $buttonarray = array();

        // Label for the submit button to return to the course.
        // Ignore this button in single activity format because it is confusing.
        if ($submit2label !== false && $this->courseformat->has_view_page()) {
            $buttonarray[] = $mform->createElement('submit', 'exebutton2', $submit2label);
        }

        if ($submitlabel !== false) {
            $buttonarray[] = $mform->createElement('submit', 'exebutton', $submitlabel);
        }

        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, $groupname, '', array(' '), false);
        $mform->setType($groupname, PARAM_RAW);
    }


    public function data_preprocessing(&$defaultvalues) {
        global $CFG, $COURSE;

        if (isset($defaultvalues['popup']) && ($defaultvalues['popup'] == 1) && isset($defaultvalues['options'])) {
            if (!empty($defaultvalues['options'])) {
                $options = explode(',', $defaultvalues['options']);
                foreach ($options as $option) {
                    list($element, $value) = explode('=', $option);
                    $element = trim($element);
                    $defaultvalues[$element] = trim($value);
                }
            }
        }
        if (isset($defaultvalues['grademethod'])) {
            $defaultvalues['grademethod'] = intval($defaultvalues['grademethod']);
        }
        if (isset($defaultvalues['width']) && (strpos($defaultvalues['width'], '%') === false)
                                           && ($defaultvalues['width'] <= 100)) {
            $defaultvalues['width'] .= '%';
        }
        if (isset($defaultvalues['height']) && (strpos($defaultvalues['height'], '%') === false)
                                           && ($defaultvalues['height'] <= 100)) {
            $defaultvalues['height'] .= '%';
        }
        $exescorms = get_all_instances_in_course('exescorm', $COURSE);
        $courseexescorm = current($exescorms);

        $draftitemid = file_get_submitted_draft_itemid('packagefile');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_exescorm', 'package', 0,
            array('subdirs' => 0, 'maxfiles' => 1));
        $defaultvalues['packagefile'] = $draftitemid;

        if (($COURSE->format == 'singleactivity') &&
            ((count($exescorms) == 0) || ($defaultvalues['instance'] == $courseexescorm->id))
        ) {
            $defaultvalues['redirect'] = 'yes';
            $defaultvalues['redirecturl'] = $CFG->wwwroot.'/course/view.php?id='.$defaultvalues['course'];
        } else {
            $defaultvalues['redirect'] = 'no';
            $defaultvalues['redirecturl'] = $CFG->wwwroot.'/mod/exescorm/view.php?id='.$defaultvalues['coursemodule'];
        }
        if (isset($defaultvalues['version'])) {
            $defaultvalues['pkgtype'] = (substr($defaultvalues['version'], 0, 5) == 'EXESCORM') ? 'exescorm' : 'aicc';
        }
        if (isset($defaultvalues['instance'])) {
            $defaultvalues['datadir'] = $defaultvalues['instance'];
        }
        if (empty($defaultvalues['timeopen'])) {
            $defaultvalues['timeopen'] = 0;
        }
        if (empty($defaultvalues['timeclose'])) {
            $defaultvalues['timeclose'] = 0;
        }

        // Set some completion default data.
        $cvalues = array();
        if (empty($this->_instance)) {
            // When in add mode, set a default completion rule that requires the EXESCORM's status be set to "Completed".
            $cvalues[4] = 1;
        } else if (!empty($defaultvalues['completionstatusrequired']) && !is_array($defaultvalues['completionstatusrequired'])) {
            // Unpack values.
            foreach (exescorm_status_options() as $key => $value) {
                if (($defaultvalues['completionstatusrequired'] & $key) == $key) {
                    $cvalues[$key] = 1;
                }
            }
        }
        if (!empty($cvalues)) {
            $defaultvalues['completionstatusrequired'] = $cvalues;
        }

        if (!isset($defaultvalues['completionscorerequired']) || !strlen($defaultvalues['completionscorerequired'])) {
            $defaultvalues['completionscoredisabled'] = 1;
        }
    }


    public function validation($data, $files) {
        global $CFG, $USER;
        $errors = parent::validation($data, $files);

        $type = $data['exescormtype'];

        if ($type === EXESCORM_TYPE_LOCAL ) {
            if (empty($data['packagefile'])) {
                $errors['packagefile'] = get_string('required');

            } else {
                $draftitemid = file_get_submitted_draft_itemid('packagefile');

                file_prepare_draft_area($draftitemid, $this->context->id, 'mod_exescorm', 'packagefilecheck', null,
                    array('subdirs' => 0, 'maxfiles' => 1));

                // Get file from users draft area.
                $usercontext = context_user::instance($USER->id);
                $fs = get_file_storage();
                $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

                if (count($files) < 1) {
                    $errors['packagefile'] = get_string('required');
                    return $errors;
                }
                $file = reset($files);
                if (!$file->is_external_file() && !empty($data['updatefreq'])) {
                    // Make sure updatefreq is not set if using normal local file.
                    $errors['updatefreq'] = get_string('updatefreq_error', 'mod_exescorm');
                }
                if (strtolower($file->get_filename()) == 'imsmanifest.xml') {
                    if (!$file->is_external_file()) {
                        $errors['packagefile'] = get_string('aliasonly', 'mod_exescorm');
                    } else {
                        $repository = repository::get_repository_by_id($file->get_repository_id(), context_system::instance());
                        if (!$repository->supports_relative_file()) {
                            $errors['packagefile'] = get_string('repositorynotsupported', 'mod_exescorm');
                        }
                    }
                } else if (strtolower(substr($file->get_filename(), -3)) == 'xml') {
                    $errors['packagefile'] = get_string('invalidmanifestname', 'mod_exescorm');
                } else {
                    // Validate this EXESCORM package.
                    $errors = array_merge($errors, exescorm_validate_package($file));
                }
            }
        } else if ($type === EXESCORM_TYPE_EXESCORMNET) {
            if (!empty($data['updatefreq'])) {
                // Make sure updatefreq is not set if using normal local file, as exescormnet received file will be local.
                $errors['updatefreq'] = get_string('updatefreq_error', 'mod_exescorm');
            }
        } else if ($type === EXESCORM_TYPE_EXTERNAL) {
            $reference = $data['packageurl'];
            // Syntax check.
            if (!preg_match('/(http:\/\/|https:\/\/|www).*\/imsmanifest.xml$/i', $reference)) {
                $errors['packageurl'] = get_string('invalidurl', 'mod_exescorm');
            } else {
                // Availability check.
                $result = exescorm_check_url($reference);
                if (is_string($result)) {
                    $errors['packageurl'] = $result;
                }
            }

        } else if ($type === 'packageurl') {
            $reference = $data['reference'];
            // Syntax check.
            if (!preg_match('/(http:\/\/|https:\/\/|www).*(\.zip|\.pif)$/i', $reference)) {
                $errors['packageurl'] = get_string('invalidurl', 'mod_exescorm');
            } else {
                // Availability check.
                $result = exescorm_check_url($reference);
                if (is_string($result)) {
                    $errors['packageurl'] = $result;
                }
            }

        } else if ($type === EXESCORM_TYPE_AICCURL) {
            $reference = $data['packageurl'];
            // Syntax check.
            if (!preg_match('/(http:\/\/|https:\/\/|www).*/', $reference)) {
                $errors['packageurl'] = get_string('invalidurl', 'mod_exescorm');
            } else {
                // Availability check.
                $result = exescorm_check_url($reference);
                if (is_string($result)) {
                    $errors['packageurl'] = $result;
                }
            }

        }

        // Validate availability dates.
        if ($data['timeopen'] && $data['timeclose']) {
            if ($data['timeopen'] > $data['timeclose']) {
                $errors['timeclose'] = get_string('closebeforeopen', 'mod_exescorm');
            }
        }
        if (!empty($data['completionstatusallscos'])) {
            $requirestatus = false;
            foreach (exescorm_status_options(true) as $key => $value) {
                if (!empty($data['completionstatusrequired'][$key])) {
                    $requirestatus = true;
                }
            }
            if (!$requirestatus) {
                $errors['completionstatusallscos'] = get_string('youmustselectastatus', 'mod_exescorm');
            }
        }

        return $errors;
    }

    // Need to translate the "options" and "reference" field.
    public function set_data($defaultvalues) {
        $defaultvalues = (array)$defaultvalues;

        if (isset($defaultvalues['exescormtype']) && isset($defaultvalues['reference'])) {
            switch ($defaultvalues['exescormtype']) {
                case EXESCORM_TYPE_LOCALSYNC :
                case EXESCORM_TYPE_EXTERNAL:
                case EXESCORM_TYPE_AICCURL:
                    $defaultvalues['packageurl'] = $defaultvalues['reference'];
            }
        }
        unset($defaultvalues['reference']);

        if (!empty($defaultvalues['options'])) {
            $options = explode(',', $defaultvalues['options']);
            foreach ($options as $option) {
                $opt = explode('=', $option);
                if (isset($opt[1])) {
                    $defaultvalues[$opt[0]] = $opt[1];
                }
            }
        }

        parent::set_data($defaultvalues);
    }

    public function add_completion_rules() {
        $mform =& $this->_form;
        $items = array();

        // Require score.
        $group = array();
        $group[] =& $mform->createElement('text', 'completionscorerequired', '', array('size' => 5));
        $group[] =& $mform->createElement('checkbox', 'completionscoredisabled', null, get_string('disable'));
        $mform->setType('completionscorerequired', PARAM_INT);
        $mform->addGroup($group, 'completionscoregroup', get_string('completionscorerequired', 'mod_exescorm'), '', false);
        $mform->addHelpButton('completionscoregroup', 'completionscorerequired', 'exescorm');
        $mform->disabledIf('completionscorerequired', 'completionscoredisabled', 'checked');
        $mform->setDefault('completionscorerequired', 0);

        $items[] = 'completionscoregroup';

        // Require status.
        $first = true;
        $firstkey = null;
        foreach (exescorm_status_options(true) as $key => $value) {
            $name = null;
            $key = 'completionstatusrequired['.$key.']';
            if ($first) {
                $name = get_string('completionstatusrequired', 'mod_exescorm');
                $first = false;
                $firstkey = $key;
            }
            $mform->addElement('checkbox', $key, $name, $value);
            $mform->setType($key, PARAM_BOOL);
            $items[] = $key;
        }
        $mform->addHelpButton($firstkey, 'completionstatusrequired', 'exescorm');

        $mform->addElement('checkbox', 'completionstatusallscos', get_string('completionstatusallscos', 'mod_exescorm'));
        $mform->setType('completionstatusallscos', PARAM_BOOL);
        $mform->addHelpButton('completionstatusallscos', 'completionstatusallscos', 'exescorm');
        $mform->setDefault('completionstatusallscos', 0);
        $items[] = 'completionstatusallscos';

        return $items;
    }

    public function completion_rule_enabled($data) {
        $status = !empty($data['completionstatusrequired']);
        $score = empty($data['completionscoredisabled']) && strlen($data['completionscorerequired']);

        return $status || $score;
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        // Convert completionstatusrequired to a proper integer, if any.
        $total = 0;
        if (isset($data->completionstatusrequired) && is_array($data->completionstatusrequired)) {
            foreach ($data->completionstatusrequired as $state => $value) {
                if ($value) {
                    $total |= $state;
                }
            }
            if (!$total) {
                $total = null;
            }
            $data->completionstatusrequired = $total;
        }

        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = isset($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;

            if (!(isset($data->completionstatusrequired) && $autocompletion)) {
                $data->completionstatusrequired = null;
            }
            // Else do nothing: completionstatusrequired has been already converted
            // into a correct integer representation.

            if (!empty($data->completionscoredisabled) || !$autocompletion) {
                $data->completionscorerequired = null;
            }
        }

        // Exescorm hack to get redirected to eXeLearning Online to edit package.
        if ($data->exescormtype === EXESCORM_TYPE_EXESCORMNET ) {
            if (! isset($data->showgradingmanagement)) {
                if (isset($data->exebutton)) {
                    // Return to activity. If it this a new activity we don't have a coursemodule yet. We'll fix it in redirector.
                    $returnto = new moodle_url("/mod/exescorm/view.php", ['id' => $data->coursemodule, 'forceview' => 1]);
                } else {
                    // Return to course.
                    $returnto = course_get_url($data->course, $data->coursesection ?? null, array('sr' => $data->sr));
                }
                // Set this becouse modedit.php expects it.
                $data->submitbutton = true;
                // If send template is true, we'll always make an edition. On new activities,
                // It will send default/uploaded template to eXeLearning.
                $sendtemplate = get_config('exescorm', 'sendtemplate');
                $action = ($sendtemplate || ! empty($data->update)) ? 'edit' : 'add';
                $data->showgradingmanagement = true;
                $data->gradingman = new exescorm_redirector($action, $returnto);
            }
        }

    }
}
