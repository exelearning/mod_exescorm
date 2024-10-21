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
 * Define all the backup steps that will be used by the backup_exescorm_activity_task
 */

/**
 * Define the complete exescorm structure for backup, with file and id annotations
 */
class backup_exescorm_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $exescorm = new backup_nested_element('exescorm', ['id'], [
            'name', 'exescormtype', 'reference', 'intro',
            'introformat', 'version', 'maxgrade', 'grademethod',
            'whatgrade', 'maxattempt', 'forcecompleted', 'forcenewattempt',
            'lastattemptlock', 'masteryoverride', 'displayattemptstatus', 'displaycoursestructure', 'updatefreq',
            'sha1hash', 'md5hash', 'revision', 'launch',
            'skipview', 'hidebrowse', 'hidetoc', 'nav', 'navpositionleft', 'navpositiontop',
            'auto', 'popup', 'options', 'width',
            'height', 'timeopen', 'timeclose', 'timemodified',
            'completionstatusrequired', 'completionscorerequired',
            'completionstatusallscos',
            'autocommit']);

        $scoes = new backup_nested_element('scoes');

        $sco = new backup_nested_element('sco', ['id'], [
            'manifest', 'organization', 'parent', 'identifier',
            'launch', 'exescormtype', 'title', 'sortorder']);

        $scodatas = new backup_nested_element('sco_datas');

        $scodata = new backup_nested_element('sco_data', ['id'], [
            'name', 'value']);

        $seqruleconds = new backup_nested_element('seq_ruleconds');

        $seqrulecond = new backup_nested_element('seq_rulecond', ['id'], [
            'conditioncombination', 'ruletype', 'action']);

        $seqrulecondsdatas = new backup_nested_element('seq_rulecond_datas');

        $seqrulecondsdata = new backup_nested_element('seq_rulecond_data', ['id'], [
            'refrencedobjective', 'measurethreshold', 'operator', 'cond']);

        $seqrolluprules = new backup_nested_element('seq_rolluprules');

        $seqrolluprule = new backup_nested_element('seq_rolluprule', ['id'], [
            'childactivityset', 'minimumcount', 'minimumpercent', 'conditioncombination',
            'action']);

        $seqrollupruleconds = new backup_nested_element('seq_rllprlconds');

        $seqrolluprulecond = new backup_nested_element('seq_rllprlcond', ['id'], [
            'cond', 'operator']);

        $seqobjectives = new backup_nested_element('seq_objectives');

        $seqobjective = new backup_nested_element('seq_objective', ['id'], [
            'primaryobj', 'objectiveid', 'satisfiedbymeasure', 'minnormalizedmeasure']);

        $seqmapinfos = new backup_nested_element('seq_mapinfos');

        $seqmapinfo = new backup_nested_element('seq_mapinfo', ['id'], [
            'targetobjectiveid', 'readsatisfiedstatus', 'readnormalizedmeasure', 'writesatisfiedstatus',
            'writenormalizedmeasure']);

        $scotracks = new backup_nested_element('sco_tracks');

        $scotrack = new backup_nested_element('sco_track', ['id'], [
            'userid', 'attempt', 'element', 'value',
            'timemodified']);

        // Build the tree.
        $exescorm->add_child($scoes);
        $scoes->add_child($sco);

        $sco->add_child($scodatas);
        $scodatas->add_child($scodata);

        $sco->add_child($seqruleconds);
        $seqruleconds->add_child($seqrulecond);

        $seqrulecond->add_child($seqrulecondsdatas);
        $seqrulecondsdatas->add_child($seqrulecondsdata);

        $sco->add_child($seqrolluprules);
        $seqrolluprules->add_child($seqrolluprule);

        $seqrolluprule->add_child($seqrollupruleconds);
        $seqrollupruleconds->add_child($seqrolluprulecond);

        $sco->add_child($seqobjectives);
        $seqobjectives->add_child($seqobjective);

        $seqobjective->add_child($seqmapinfos);
        $seqmapinfos->add_child($seqmapinfo);

        $sco->add_child($scotracks);
        $scotracks->add_child($scotrack);

        // Define sources.
        $exescorm->set_source_table('exescorm', ['id' => backup::VAR_ACTIVITYID]);

        // Order is important for several EXESCORM calls (especially exescorm_scoes)
        // in the following calls to set_source_table.
        $sco->set_source_table('exescorm_scoes', ['exescorm' => backup::VAR_PARENTID], 'sortorder, id');
        $scodata->set_source_table('exescorm_scoes_data', ['scoid' => backup::VAR_PARENTID], 'id ASC');
        $seqrulecond->set_source_table('exescorm_seq_ruleconds', ['scoid' => backup::VAR_PARENTID], 'id ASC');
        $seqrulecondsdata->set_source_table(
            'exescorm_seq_rulecond', ['ruleconditionsid' => backup::VAR_PARENTID], 'id ASC'
        );
        $seqrolluprule->set_source_table('exescorm_seq_rolluprule', ['scoid' => backup::VAR_PARENTID], 'id ASC');
        $seqrolluprulecond->set_source_table('exescorm_seq_rllprlcond', ['rollupruleid' => backup::VAR_PARENTID], 'id ASC');
        $seqobjective->set_source_table('exescorm_seq_objective', ['scoid' => backup::VAR_PARENTID], 'id ASC');
        $seqmapinfo->set_source_table('exescorm_seq_mapinfo', ['objectiveid' => backup::VAR_PARENTID], 'id ASC');

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $scotrack->set_source_table('exescorm_scoes_track', ['scoid' => backup::VAR_PARENTID], 'id ASC');
        }

        // Define id annotations.
        $scotrack->annotate_ids('user', 'userid');

        // Define file annotations.
        $exescorm->annotate_files('mod_exescorm', 'intro', null); // This file area hasn't itemid.
        $exescorm->annotate_files('mod_exescorm', 'content', null); // This file area hasn't itemid.
        $exescorm->annotate_files('mod_exescorm', 'package', null); // This file area hasn't itemid.

        // Return the root element (exescorm), wrapped into standard activity structure.
        return $this->prepare_activity_structure($exescorm);
    }
}
