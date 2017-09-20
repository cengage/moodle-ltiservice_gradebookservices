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
 * This file contains a class definition for the LTI Gradebook Services
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_gradebookservices\local\service;

use ltiservice_gradebookservices\local\resource\lineitem;

defined('MOODLE_INTERNAL') || die();

/**
 * A service implementing LTI Gradebook Services.
 *
 * @package    ltiservice_gradebookservices
 * @since      Moodle 3.0
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradebookservices extends \mod_lti\local\ltiservice\service_base {

    /**
     * Class constructor.
     */
    public function __construct() {

        parent::__construct();
        $this->id = 'gradebookservices';
        $this->name = get_string('servicename', 'ltiservice_gradebookservices');

    }

    /**
     * Get the resources for this service.
     *
     * @return array
     */
    public function get_resources() {

        // The containers should be ordered in the array after their elements.
        // Lineitems should be after lineitem and scores should be after score.
        if (empty($this->resources)) {
            $this->resources = array();
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\lineitem($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\result($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\score($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\lineitems($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\results($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\scores($this);

        }

        return $this->resources;

    }

    /**
     * Fetch the lineitem instances.
     *
     * @param string $courseid       ID of course
     * @param string $resourceid     Resource identifier used for filtering, may be null
     * @param string $resourcelinkid Resource Link identifier used for filtering, may be null
     * @param int    $limitfrom      Offset for the first line item to include in a paged set
     * @param int    $limitnum       Maximum number of line items to include in the paged set
     *
     * @return array
     */
    public function get_lineitems($courseid, $resourceid, $resourcelinkid, $limitfrom, $limitnum) {
        global $DB;

        $params = array('courseid' => $courseid, 'itemtype' => 'mod', 'itemmodule' => 'lti',
            'tpid' => $this->get_tool_proxy()->id,
            'tpid2' => $this->get_tool_proxy()->id
        );

        $optional_filters = "";
        if (isset($resourceid)) {
            $optional_filters .= " AND (i.idnumber = :resourceid)";
            $params['resourceid'] = $resourceid;
        }
        if (isset($resourcelinkid)) {
            $optional_filters .= " AND (i.iteminstance = :resourcelinkid)";
            $params['resourcelinkid'] = $resourcelinkid;
        }
        $sql = "SELECT i.*,s.lineitemtoolproviderid
                  FROM {grade_items} i
             LEFT JOIN {lti} m ON i.iteminstance = m.id
             LEFT JOIN {lti_types} t ON m.typeid = t.id
             LEFT JOIN {ltiservice_gradebookservices} s ON i.itemnumber = s.id
                 WHERE (i.courseid = :courseid)
                       AND (((i.itemtype = :itemtype)
                             AND (i.itemmodule = :itemmodule)
                             AND (t.toolproxyid = :tpid))
                            OR ((s.toolproxyid = :tpid2)
                                AND (i.itemnumber = s.id)))
                                {$optional_filters}
                                ORDER by i.id";

        try {
            $lineitems = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        } catch (\Exception $e) {
            throw new \Exception(null, 500);
        }

        return $lineitems;

    }

    /**
     * Fetch a lineitem instance.
     *
     * Returns the lineitem instance if found, otherwise false.
     *
     * @param string   $courseid   ID of course
     * @param string   $itemid     ID of lineitem
     * @param boolean  $any        False if the lineitem should be one created via this web service
     *                             and not one automatically created by LTI 1.1
     *
     * @return object
     */
    public function get_lineitem($courseid, $itemid, $any) {
        global $DB;

        if ($any) {
            $where = "(((i.itemtype = :itemtype)
                             AND (i.itemmodule = :itemmodule)
                             AND (t.toolproxyid = :tpid2))
                             OR ((s.toolproxyid = :tpid) AND (i.itemnumber = s.id)))";
            $params = array('courseid' => $courseid, 'itemid' => $itemid, 'tpid' => $this->get_tool_proxy()->id,
                    'itemtype' => 'mod', 'itemmodule' => 'lti', 'tpid2' => $this->get_tool_proxy()->id);
        } else {
            $where = '(s.toolproxyid = :tpid) AND (i.itemnumber = s.id)';
            $params = array('courseid' => $courseid, 'itemid' => $itemid, 'tpid' => $this->get_tool_proxy()->id);
        }
        $sql = "SELECT i.*,s.lineitemtoolproviderid
                  FROM {grade_items} i
             LEFT JOIN {lti} m ON i.iteminstance = m.id
             LEFT JOIN {lti_types} t ON m.typeid = t.id
             LEFT JOIN {ltiservice_gradebookservices} s ON i.itemnumber = s.id
                 WHERE (i.courseid = :courseid)
                       AND (i.id = :itemid)
                       AND {$where}";
        try {
            $lineitem = $DB->get_records_sql($sql, $params);
            if (count($lineitem) === 1) {
                $lineitem = reset($lineitem);
            } else {
                $lineitem = false;
            }
        } catch (\Exception $e) {
            $lineitem = false;
        }

        return $lineitem;

    }


    /**
     * Set a grade item.
     *
     * @param object  $item               Grade Item record
     * @param object  $result             Result object
     * @param string  $userid             User ID
     */
    public static function set_grade_item($item, $result, $userid) {
        global $DB;

        if ($DB->get_record('user', array('id' => $userid)) === false) {
            throw new \Exception(null, 400);
        }

        $grade = new \stdClass();
        $grade->userid = $userid;
        $grade->rawgrademin = grade_floatval(0);
        $max = null;
        if (isset($result->scoreGiven)) {
            $grade->rawgrade = grade_floatval($result->scoreGiven);
            if (isset($result->scoreMaximum)) {
                $max = $result->scoreMaximum;
            }
        }
        if (!is_null($max) && grade_floats_different($max, $item->grademax) && grade_floats_different($max, 0.0)) {
            $grade->rawgrade = grade_floatval($grade->rawgrade * $item->grademax / $max);
        }
        if (isset($result->comment) && !empty($result->comment)) {
            $grade->feedback = $result->comment;
            $grade->feedbackformat = FORMAT_PLAIN;
        } else {
            $grade->feedback = false;
            $grade->feedbackformat = FORMAT_MOODLE;
        }
        if (isset($result->timestamp)) {
            $grade->timemodified = strtotime($result->timestamp);
        } else {
            $grade->timemodified = time();
        }
        $status = grade_update('mod/ltiservice_gradebookservices', $item->courseid, $item->itemtype, $item->itemmodule,
                               $item->iteminstance, $item->itemnumber, $grade);
        if ($status !== GRADE_UPDATE_OK) {
            throw new \Exception(null, 500);
        }

    }

    /**
     * Get the JSON representation of the grade item.
     *
     * @param object  $item               Grade Item record
     * @param string  $endpoint           Endpoint for lineitems container request
     * @return string
     */
    public static function item_to_json($item, $endpoint) {

        $lineitem = new \stdClass();
        $lineitem->id = "{$endpoint}/{$item->id}/lineitem";
        $lineitem->label = $item->itemname;
        $lineitem->scoreMaximum = intval($item->grademax); // TODO: is int correct?!?
        if (!empty($item->idnumber)) {
            $lineitem->resourceId = $item->idnumber;
        }
        $lineitem->results = "{$endpoint}/{$item->id}/results";
        $lineitem->scores = "{$endpoint}/{$item->id}/scores";
        if (!empty($item->lineitemtoolproviderid)) {
            $lineitem->lineItemToolProviderId = $item->lineitemtoolproviderid;
        }
        if (isset($item->iteminstance)) {
            $lineitem->resourceLinkId = strval($item->iteminstance);
        }
        $json = json_encode($lineitem, JSON_UNESCAPED_SLASHES);

        return $json;

    }

    /**
     * Get the JSON representation of the grade.
     *
     * @param object  $grade              Grade record
     * @param string  $endpoint           Endpoint for lineitem
     *
     * @return string
     */
    public static function result_to_json($grade, $endpoint) {

        $endpoint = substr($endpoint, 0, strripos($endpoint, '/'));
        $id = "{$endpoint}/results/{$grade->userid}/result";
        $result = new \stdClass();
        $result->id = $id;
        $result->userId = $grade->userid;
        if (!empty($grade->finalgrade)) {
            $result->resultScore = $grade->finalgrade;
            $result->resultMaximum = intval($grade->rawgrademax);
            if (!empty($grade->feedback)) {
                $result->comment = $grade->feedback;
            }
            $result->scoreOf = $endpoint;
            $result->timestamp = date('Y-m-d\TH:iO', $grade->timemodified);
        }
        $json = json_encode($result, JSON_UNESCAPED_SLASHES);

        return $json;

    }

    /**
     * Get the JSON representation of the grade.
     *
     * @param object  $grade              Grade record
     * @param string  $endpoint           Endpoint for lineitem
     *
     * @return string
     */
    public static function score_to_json($grade, $endpoint) {

        $endpoint = substr($endpoint, 0, strripos($endpoint, '/'));
        $id = "{$endpoint}/scores/{$grade->userid}/score";
        $result = new \stdClass();
        $result->id = $id;
        $result->userId = $grade->userid;
        $result->scoreGiven = $grade->finalgrade;
        $result->scoreMaximum = intval($grade->rawgrademax);
        if (!empty($grade->feedback)) {
            $result->comment = $grade->feedback;
        }
        // TODO: activityProgress, gradingProgress; might just skip 'em as Moodle corollaries aren't obvious.
        $result->scoreOf = $endpoint;
        $result->timestamp = date('Y-m-d\TH:iO', $grade->timemodified);
        $json = json_encode($result, JSON_UNESCAPED_SLASHES);

        return $json;

    }

    /**
     * Check if an LTI id is valid.
     *
     * @param string $linkid             The lti id
     * @param string  $course             The course
     *
     * @return boolean
     */
    public static function check_lti_id($linkid, $course, $toolproxy) {
        global $DB;

        $sqlparams = array();
        $sqlparams['linkid'] = $linkid;
        $sqlparams['course'] = $course;
        $sqlparams['toolproxy'] = $toolproxy;
        $sql = 'SELECT lti.* FROM {lti} lti JOIN {lti_types} typ on lti.typeid=typ.id where
            lti.id=? and lti.course=?  and typ.toolproxyid=?';
        return $DB->record_exists_sql($sql, $sqlparams);
    }

    /**
     * Sometimes, if a gradebook entry is deleted and it was a lineitem
     * the row in the table ltiservice_gradebookservices can become an orphan
     * This method will clean these orphans. It will happens based in a random number
     * because it is not urgent and we don't want to slow the service
     *
     */
    public static function delete_orphans_ltiservice_gradebookservices_rows() {
        global $DB;
        $sql = 'DELETE FROM {ltiservice_gradebookservices} where id not in
             (SELECT DISTINCT itemnumber FROM {grade_items} gi where gi.itemtype="mod"
             AND gi.itemmodule="lti" AND ((NOT gi.itemnumber=0) AND (NOT gi.itemnumber is null)))';
        try {
            $deleted = $DB->execute($sql);
        } catch (\Exception $e) {
            $deleted = false;
        }
    }

    /**
     * Check if a user can be graded in a course
     *
     * @param string $courseid            The course
     * @param string $user                The user
     *
     */
    public static function is_user_gradable_in_course($courseid, $userid) {
        global $CFG;

        $gradableuser = false;
        $coursecontext = \context_course::instance($courseid);
        if (is_enrolled($coursecontext, $userid, '', false)) {
            $roles = get_user_roles($coursecontext, $userid);
            $gradebookroles = explode(',', $CFG->gradebookroles);
            foreach ($roles as $role) {
                foreach ($gradebookroles as $gradebookrole) {
                    if ($role->roleid = $gradebookrole) {
                        $gradableuser = true;
                    }
                }
            }
        }

        return $gradableuser;
    }

    /**
     * Validates paging query parameters for boundary conditions.
     *
     * @param string $from offset for the first line item to include in this paged set, must be zero or greater and
     *                    requires a limit
     * @param string $limit maximum number of line items to include in the response, must be greater than one if provided
     * @throws \Exception if the paging query parameters are invalid
     */
    public static function validate_paging_query_parameters($from, $limit) {

        if (isset($limit)) {
            if (!is_numeric($limit) || $limit <= 0) {
                throw new \Exception(null, 400);
            }
        }

        if (isset($from)) {
            if (!isset($limit) || !is_numeric($from) || $from < 0) {
                throw new \Exception(null, 400);
            }
        }
    }
}
