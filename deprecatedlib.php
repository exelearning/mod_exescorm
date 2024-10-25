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
 * List of deprecated mod_exescorm functions.
 *
 * @package   mod_exescorm
 * @copyright 2021 Shamim Rezaie <shamim@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Obtains the automatic completion state for this exescorm based on any conditions
 * in exescorm settings.
 *
 * @deprecated since Moodle 3.11
 * @todo MDL-71196 Final deprecation in Moodle 4.3
 * @see \mod_exescorm\completion\custom_completion
 * @param stdClass $course Course
 * @param cm_info|stdClass $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function exescorm_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // No need to call debugging here. Deprecation debugging notice already being called in \completion_info::internal_get_state().

    $result = $type;

    // Get exescorm.
    if (!$exescorm = $DB->get_record('exescorm', ['id' => $cm->instance])) {
        throw new \moodle_exception('cannotfindexescorm');
    }
    // Only check for existence of tracks and return false if completionstatusrequired or completionscorerequired
    // this means that if only view is required we don't end up with a false state.
    if ($exescorm->completionstatusrequired !== null || $exescorm->completionscorerequired !== null) {
        // Get user's tracks data.
        $tracks = $DB->get_records_sql(
            "
            SELECT
                id,
                scoid,
                element,
                value
            FROM
                {exescorm_scoes_track}
            WHERE
                exescormid = ?
            AND userid = ?
            AND element IN
            (
                'cmi.core.lesson_status',
                'cmi.completion_status',
                'cmi.success_status',
                'cmi.core.score.raw',
                'cmi.score.raw'
            )
            ",
            [$exescorm->id, $userid]
        );

        if (!$tracks) {
            return completion_info::aggregate_completion_states($type, $result, false);
        }
    }

    // Check for status.
    if ($exescorm->completionstatusrequired !== null) {

        // Get status.
        $statuses = array_flip(exescorm_status_options());
        $nstatus = 0;
        // Check any track for these values.
        $scostatus = [];
        foreach ($tracks as $track) {
            if (!in_array($track->element, ['cmi.core.lesson_status', 'cmi.completion_status', 'cmi.success_status'])) {
                continue;
            }
            if (array_key_exists($track->value, $statuses)) {
                $scostatus[$track->scoid] = true;
                $nstatus |= $statuses[$track->value];
            }
        }

        if (!empty($exescorm->completionstatusallscos)) {
            // Iterate over all scos and make sure each has a lesson_status.
            $scos = $DB->get_records('exescorm_scoes', ['exescorm' => $exescorm->id, 'exescormtype' => 'sco']);
            foreach ($scos as $sco) {
                if (empty($scostatus[$sco->id])) {
                    return completion_info::aggregate_completion_states($type, $result, false);
                }
            }
            return completion_info::aggregate_completion_states($type, $result, true);
        } else if ($exescorm->completionstatusrequired & $nstatus) {
            return completion_info::aggregate_completion_states($type, $result, true);
        } else {
            return completion_info::aggregate_completion_states($type, $result, false);
        }
    }

    // Check for score.
    if ($exescorm->completionscorerequired !== null) {
        $maxscore = -1;

        foreach ($tracks as $track) {
            if (!in_array($track->element, ['cmi.core.score.raw', 'cmi.score.raw'])) {
                continue;
            }

            if (strlen($track->value) && floatval($track->value) >= $maxscore) {
                $maxscore = floatval($track->value);
            }
        }

        if ($exescorm->completionscorerequired <= $maxscore) {
            return completion_info::aggregate_completion_states($type, $result, true);
        } else {
            return completion_info::aggregate_completion_states($type, $result, false);
        }
    }

    return $result;
}
