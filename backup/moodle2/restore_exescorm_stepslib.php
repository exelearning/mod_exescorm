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
 * @package    mod_exescorm
 * @subpackage backup-moodle2
 * @copyright  2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_exescorm_activity_task.
 */

/**
 * Structure step to restore one exescorm activity.
 */
class restore_exescorm_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('exescorm', '/activity/exescorm');
        $paths[] = new restore_path_element('exescorm_sco', '/activity/exescorm/scoes/sco');
        $paths[] = new restore_path_element('exescorm_sco_data', '/activity/exescorm/scoes/sco/sco_datas/sco_data');
        $paths[] = new restore_path_element('exescorm_seq_objective',
                                            '/activity/exescorm/scoes/sco/seq_objectives/seq_objective'
                                        );
        $paths[] = new restore_path_element('exescorm_seq_rolluprule',
                                            '/activity/exescorm/scoes/sco/seq_rolluprules/seq_rolluprule'
                                        );
        $paths[] = new restore_path_element('exescorm_seq_rllprlcond',
                                            '/activity/exescorm/scoes/sco/seq_rllprlconds/seq_rllprlcond'
                                        );
        $paths[] = new restore_path_element('exescorm_seq_rulecond',
                                            '/activity/exescorm/scoes/sco/seq_ruleconds/seq_rulecond'
                                        );
        $paths[] = new restore_path_element('exescorm_seq_rulecond_data',
                                            '/activity/exescorm/scoes/sco/seq_rulecond_datas/seq_rulecond_data'
                                        );

        $paths[] = new restore_path_element('exescorm_seq_mapinfo',
                                            '/activity/exescorm/scoes/sco/seq_objectives/seq_objective/seq_mapinfos/seq_mapinfo'
                                        );
        if ($userinfo) {
            $paths[] = new restore_path_element('exescorm_sco_track', '/activity/exescorm/scoes/sco/sco_tracks/sco_track');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_exescorm($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        if (!isset($data->completionstatusallscos)) {
            $data->completionstatusallscos = false;
        }
        // Insert the exescorm record.
        $newitemid = $DB->insert_record('exescorm', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_exescorm_sco($data) {
        global $DB;

        $data = (object)$data;

        $oldid = $data->id;
        $data->exescorm = $this->get_new_parentid('exescorm');

        $newitemid = $DB->insert_record('exescorm_scoes', $data);
        $this->set_mapping('exescorm_sco', $oldid, $newitemid);
    }

    protected function process_exescorm_sco_data($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');

        $newitemid = $DB->insert_record('exescorm_scoes_data', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function process_exescorm_seq_objective($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');

        $newitemid = $DB->insert_record('exescorm_seq_objective', $data);
        $this->set_mapping('exescorm_seq_objective', $oldid, $newitemid);
    }

    protected function process_exescorm_seq_rolluprule($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');

        $newitemid = $DB->insert_record('exescorm_seq_rolluprule', $data);
        $this->set_mapping('exescorm_seq_rolluprule', $oldid, $newitemid);
    }

    protected function process_exescorm_seq_rllprlcond($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');
        $data->ruleconditions = $this->get_new_parentid('exescorm_seq_rolluprule');

        $newitemid = $DB->insert_record('exescorm_seq_rllprlcond', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function process_exescorm_seq_rulecond($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');

        $newitemid = $DB->insert_record('exescorm_seq_ruleconds', $data);
        $this->set_mapping('exescorm_seq_ruleconds', $oldid, $newitemid);
    }

    protected function process_exescorm_seq_rulecond_data($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');
        $data->ruleconditions = $this->get_new_parentid('exescorm_seq_ruleconds');

        $newitemid = $DB->insert_record('exescorm_seq_rulecond', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }



    protected function process_exescorm_seq_mapinfo($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->scoid = $this->get_new_parentid('exescorm_sco');
        $data->objectiveid = $this->get_new_parentid('exescorm_seq_objective');
        $newitemid = $DB->insert_record('exescorm_scoes_data', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function process_exescorm_sco_track($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->exescormid = $this->get_new_parentid('exescorm');
        $data->scoid = $this->get_new_parentid('exescorm_sco');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('exescorm_scoes_track', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    protected function after_execute() {
        global $DB;

        // Add exescorm related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_exescorm', 'intro', null);
        $this->add_related_files('mod_exescorm', 'content', null);
        $this->add_related_files('mod_exescorm', 'package', null);

        // Fix launch param in exescorm table to use new sco id.
        $exescormid = $this->get_new_parentid('exescorm');
        $exescorm = $DB->get_record('exescorm', array('id' => $exescormid));
        $exescorm->launch = $this->get_mappingid('exescorm_sco', $exescorm->launch, '');

        if (!empty($exescorm->launch)) {
            // Check that this sco has a valid launch value.
            $scolaunch = $DB->get_field('exescorm_scoes', 'launch', array('id' => $exescorm->launch));
            if (empty($scolaunch)) {
                // This is not a valid sco - set to empty so we can find a valid launch sco.
                $exescorm->launch = '';
            }
        }

        if (empty($exescorm->launch)) {
            // This exescorm has an invalid launch param - we need to calculate it and get the first launchable sco.
            $sqlselect = 'exescorm = ? AND '.$DB->sql_isnotempty('exescorm_scoes', 'launch', false, true);
            // We use get_records here as we need to pass a limit in the query that works cross db.
            $scoes = $DB->get_records_select('exescorm_scoes', $sqlselect, array($exescormid), 'sortorder', 'id', 0, 1);
            if (!empty($scoes)) {
                $sco = reset($scoes); // We only care about the first record - the above query only returns one.
                $exescorm->launch = $sco->id;
            }
        }
        if (!empty($exescorm->launch)) {
            $DB->update_record('exescorm', $exescorm);
        }
    }
}
