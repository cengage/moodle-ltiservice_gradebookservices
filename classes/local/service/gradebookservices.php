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
        // Lineitems should be after lineitem.
        if (empty($this->resources)) {
            $this->resources = array();
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\lineitem($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\result($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\lineitems($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\results($this);
            $this->resources[] = new \ltiservice_gradebookservices\local\resource\scores($this);

        }

        return $this->resources;

    }

    /**
     * Return an array of options to add to the add/edit external tool.
     * The array will have elements with this attributes:
     *
     * - type ( only 'select', 'text', 'passwordunmask' or 'checkbox' are
     * allowed by the moment) view lib/pear/HTML/QuickForm for all types.
     * - array of type specific parameters:
     *  - if select it needs:
     *      - name.
     *      - label.
     *      - array of options.
     *  - if text it needs:
     *      - name.
     *      - label.
     *      - parameters (example: array('size' => '64')).
     *  - if checkbox it needs:
     *      - name.
     *      - main label (left side of the form).
     *      - after checkbox lable.
     * - setType value or null, ('int', 'text'...) If null, no default value.
     * - setDefault or null ('2', ...) If null, no default value.
     * - HelpButton $identifier usually the same than the name and it will be
     *  in the texts file with _help at the end, If null, no help button.
     * - HelpButton $component component to find the languages files. If null, no help button.
     *
     * @return an array of options to add to the add/edit external tool or null if no options to add.
     *
     */

    public function get_configuration_options() {

        $configurationoptions = array();

        $optionsgs = array();
        $optionsgs[0] = get_string('nevergs', 'ltiservice_gradebookservices');
        $optionsgs[1] = get_string('partialgs', 'ltiservice_gradebookservices');
        $optionsgs[2] = get_string('alwaysgs', 'ltiservice_gradebookservices');

        $gradesynchronization = array();
        $gradesynchronization[0] = 'select';
        $parametersgs = array();
        $parametersgs[0] = 'ltiservice_gradesynchronization';
        $parametersgs[1] = get_string('grade_synchronization', 'ltiservice_gradebookservices');
        $parametersgs[2] = $optionsgs;
        $gradesynchronization[1] = $parametersgs;
        $gradesynchronization[2] = 'int';
        $gradesynchronization[3] = '0';
        $gradesynchronization[4] = 'grade_synchronization';
        $gradesynchronization[5] = 'ltiservice_gradebookservices';

        $configurationoptions[1] = $gradesynchronization;

        return $configurationoptions;
    }

    /**
     * Return an array with the names of the parameters that the service will be saving in the configuration
     *
     * @return  an array with the names of the parameters that the service will be saving in the configuration
     *
     */
    public function get_configuration_parameter_names() {
        return array('ltiservice_gradesynchronization');
    }

    /**
     * Return an array of key/values to add to the launch parameters.
     *
     * @param $messagetype. 'basic-lti-launch-request' or 'ContentItemSelectionRequest'.
     * @param $courseid. the course id.
     * @param $userid. The user id.
     * @param $typeid. The tool lti type id.
     * @param $modlti. The id of the lti activity.
     *
     * The type is passed to check the configuration
     * and not return parameters for services not used.
     *
     * @return an array of key/value pairs to add as launch parameters.
     */
    public function get_launch_parameters($messagetype, $courseid, $user, $typeid, $modlti = null) {
        global $DB;

        $launchparameters = array();
        $tool = lti_get_type_type_config($typeid);
        // only inject parameters if the service is enabled for this tool
        if ($tool->ltiservice_gradesynchronization == '1' || $tool->ltiservice_gradesynchronization == '2') {
            // check for used in context is only needed because there is no explicit site tool - course relation
            if ($this->is_used_in_context($typeid, $courseid)) {
                $endpoint = $this->get_service_path() . "/{$courseid}/lineitems";
                if (is_null($modlti)) {
                    $id = null;
                } else {
                    $conditions = array('courseid' => $courseid, 'itemtype' => 'mod', 'itemmodule' => 'lti', 'iteminstance' => $modlti);
                    $numberoflineitems = $DB->count_records('grade_items', $conditions);
                    if ($numberoflineitems == 1) {
                        $id = $DB->get_field('grade_items', 'id', $conditions);
                    } else {
                        $id = null;
                    }
                }
                $launchparameters['custom_lineitems_url'] = $endpoint . "?typeid={$typeid}";
                if (!is_null($id)) {
                    $launchparameters['custom_results_url'] = $endpoint . "/{$id}/results?typeid={$typeid}";
                    $launchparameters['custom_lineitem_url'] = $endpoint . "/{$id}/lineitem?typeid={$typeid}";
                    $launchparameters['custom_scores_url'] = $endpoint . "/{$id}/scores?typeid={$typeid}";
                    if (!is_null($user)) {
                        $launchparameters['custom_result_url'] = $endpoint . "/{$id}/results/{$user}?typeid={$typeid}";
                    }
                }
            }
        }
        return $launchparameters;
    }

    /**
     * Fetch the lineitem instances.
     *
     * @param string $courseid       ID of course
     * @param string $resourceid     Resource identifier used for filtering, may be null
     * @param string $ltilinkid Resource Link identifier used for filtering, may be null
     * @param int    $limitfrom      Offset for the first line item to include in a paged set
     * @param int    $limitnum       Maximum number of line items to include in the paged set
     *
     * @return array
     */
    public function get_lineitems($courseid, $resourceid, $ltilinkid, $limitfrom, $limitnum, $typeid) {
        global $DB;

        // Select all lti potential linetiems in site.
        $params = array('courseid' => $courseid, 'itemtype' => 'mod', 'itemmodule' => 'lti');

        $optionalfilters = "";
        if (isset($resourceid)) {
            $optionalfilters .= " AND (i.idnumber = :resourceid)";
            $params['resourceid'] = $resourceid;
        }
        if (isset($ltilinkid)) {
            $optionalfilters .= " AND (i.iteminstance = :ltilinkid)";
            $params['ltilinkid'] = $ltilinkid;
        }

        $sql = "SELECT i.*
        FROM {grade_items} i
        WHERE (i.courseid = :courseid)
        AND (i.itemtype = :itemtype)
        AND (i.itemmodule = :itemmodule)
        {$optionalfilters}
        ORDER by i.id";

        try {
            $lineitems = $DB->get_records_sql($sql, $params);
        } catch (\Exception $e) {
            throw new \Exception(null, 500);
        }

        // For each one, check the gbs id, and check that toolproxy matches. If so, add the
        // tag to the result and add it to a final results array.
        $lineitemstoreturn = array();
        if ($lineitems) {
            foreach ($lineitems as $lineitem) {
                $gbs = $this->find_ltiservice_gradebookservice_for_lineitem($lineitem->id);
                if ($gbs) {
                    if (is_null($typeid)) {
                        if ($this->get_tool_proxy()->id == $gbs->toolproxyid) {
                            $lineitem->tag = $gbs->tag;
                            array_push($lineitemstoreturn, $lineitem);
                        }
                    } else {
                        if ($typeid == $gbs->typeid) {
                            $lineitem->tag = $gbs->tag;
                            array_push($lineitemstoreturn, $lineitem);
                        }
                    }
                } else {
                    // We will need to check if the activity related belongs to our tool proxy.
                    $ltiactivity = $DB->get_record('lti', array('id' => $lineitem->iteminstance));
                    if (($ltiactivity) && (isset($ltiactivity->typeid))) {
                        if ($ltiactivity->typeid != 0) {
                            $tool = $DB->get_record('lti_types', array('id' => $ltiactivity->typeid));
                        } else {
                            $tool = lti_get_tool_by_url_match($ltiactivity->toolurl, $courseid);
                            if (!$tool) {
                                $tool = lti_get_tool_by_url_match($ltiactivity->securetoolurl, $courseid);
                            }
                        }
                        if (is_null($typeid)) {
                            if (($tool) && ($this->get_tool_proxy()->id == $tool->toolproxyid)) {
                                $lineitem->tag = null;
                                array_push($lineitemstoreturn, $lineitem);
                            }
                        } else {
                            if (($tool) && ($tool->id == $typeid)) {
                                $lineitem->tag = null;
                                array_push($lineitemstoreturn, $lineitem);
                            }
                        }
                    }
                }
            }
            $lineitemsandtotalcount = array();
            array_push($lineitemsandtotalcount, count($lineitemstoreturn));
            // Return the right array based in the paging parameters limit and from.
            if (($limitnum) && ($limitnum > 0)) {
                $lineitemstoreturn = array_slice($lineitemstoreturn, $limitfrom, $limitnum);
            }
            array_push($lineitemsandtotalcount, $lineitemstoreturn);
        }
        return $lineitemsandtotalcount;
    }

    /**
     * Fetch a lineitem instance.
     *
     * Returns the lineitem instance if found, otherwise false.
     *
     * @param string   $courseid   ID of course
     * @param string   $itemid     ID of lineitem
     *
     * @return object
     */
    public function get_lineitem($courseid, $itemid, $typeid) {
        global $DB;

        $gbs = $this->find_ltiservice_gradebookservice_for_lineitem($itemid);
        if (!$gbs) {
            $lineitem = $DB->get_record('grade_items', array('id' => $itemid));
            // We will need to check if the activity related belongs to our tool proxy.
            $ltiactivity = $DB->get_record('lti', array('id' => $lineitem->iteminstance));
            if (($ltiactivity) && (isset($ltiactivity->typeid))) {
                if ($ltiactivity->typeid != 0) {
                    $tool = $DB->get_record('lti_types', array('id' => $ltiactivity->typeid));
                } else {
                    $tool = lti_get_tool_by_url_match($ltiactivity->toolurl, $courseid);
                    if (!$tool) {
                        $tool = lti_get_tool_by_url_match($ltiactivity->securetoolurl, $courseid);
                    }
                }
                if (is_null($typeid)) {
                    if (($tool) && ($this->get_tool_proxy()->id == $tool->toolproxyid)) {
                        $lineitem->tag = null;
                    } else {
                         return false;
                    }
                } else {
                    if (($tool) && ($tool->id == $typeid)) {
                        $lineitem->tag = null;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } else {
            if (is_null($typeid)) {
                $sql = "SELECT i.*,s.tag
                        FROM {grade_items} i,{ltiservice_gradebookservices} s
                        WHERE (i.courseid = :courseid)
                                AND (i.id = :itemid)
                                AND (s.id = :gbsid)
                                AND (s.toolproxyid = :tpid)";
                $params = array('courseid' => $courseid, 'itemid' => $itemid, 'tpid' => $this->get_tool_proxy()->id,
                        'gbsid' => $gbs->id);
            } else {
                $sql = "SELECT i.*,s.tag
                        FROM {grade_items} i,{ltiservice_gradebookservices} s
                        WHERE (i.courseid = :courseid)
                                AND (i.id = :itemid)
                                AND (s.id = :gbsid)
                                AND (s.typeid = :typeid)";
                $params = array('courseid' => $courseid, 'itemid' => $itemid, 'typeid' => $typeid,
                        'gbsid' => $gbs->id);
            }
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
    public static function set_grade_item($item, $result, $userid, $typeid) {
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
    public static function item_to_json($item, $endpoint, $typeid) {

        $lineitem = new \stdClass();
        if (is_null($typeid)) {
            $lineitem->id = "{$endpoint}/{$item->id}/lineitem";
        } else {
            $lineitem->id = "{$endpoint}/{$item->id}/lineitem?typeid={$typeid}";
        }
        $lineitem->label = $item->itemname;
        $lineitem->scoreMaximum = intval($item->grademax); // TODO: is int correct?!?
        $lineitem->resourceId = (!empty($item->idnumber)) ? $item->idnumber : '';
        if (is_null($typeid)) {
            $lineitem->results = "{$endpoint}/{$item->id}/results";
            $lineitem->scores = "{$endpoint}/{$item->id}/scores";
        } else {
            $lineitem->results = "{$endpoint}/{$item->id}/results?typeid={$typeid}";
            $lineitem->scores = "{$endpoint}/{$item->id}/scores?typeid={$typeid}";
        }
        $lineitem->tag = (!empty($item->tag)) ? $item->tag : '';
        if (isset($item->iteminstance)) {
            $lineitem->ltiLinkId = strval($item->iteminstance);
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
    public static function result_to_json($grade, $endpoint, $typeid) {

        $endpoint = substr($endpoint, 0, strripos($endpoint, '/'));
        if (is_null($typeid)) {
            $id = "{$endpoint}/results/{$grade->userid}/result";
        } else {
            $id = "{$endpoint}/results/{$grade->userid}/result?typeid={$typeid}";
        }
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
            $result->timestamp = date('c', $grade->timemodified);
        }
        $json = json_encode($result, JSON_UNESCAPED_SLASHES);

        return $json;

    }

    /**
     * Check if an LTI id is valid.
     *
     * @param string $linkid             The lti id
     * @param string  $course            The course
     * @param string  $toolproxy         The tool proxy id
     *
     * @return boolean
     */
    public static function check_lti_id($linkid, $course, $toolproxy) {
        global $DB;
        // Check if lti type is zero or not (comes from a backup).
        $sqlparams1 = array();
        $sqlparams1['linkid'] = $linkid;
        $sqlparams1['course'] = $course;
        $ltiactivity = $DB->get_record('lti', array('id' => $linkid, 'course' => $course));
        if ($ltiactivity->typeid == 0) {
            $tool = lti_get_tool_by_url_match($ltiactivity->toolurl, $course);
            if (!$tool) {
                $tool = lti_get_tool_by_url_match($ltiactivity->securetoolurl, $course);
            }
            return (($tool) && ($toolproxy == $tool->toolproxyid));
        } else {
            $sqlparams2 = array();
            $sqlparams2['linkid'] = $linkid;
            $sqlparams2['course'] = $course;
            $sqlparams2['toolproxy'] = $toolproxy;
            $sql = 'SELECT lti.* FROM {lti} lti JOIN {lti_types} typ on lti.typeid=typ.id where
            lti.id=? and lti.course=?  and typ.toolproxyid=?';
            return $DB->record_exists_sql($sql, $sqlparams2);
        }
    }

    /**
     * Check if an LTI id is valid when we are in a LTI 1.x case
     *
     * @param string $linkid             The lti id
     * @param string  $course            The course
     * @param string  $typeid            The lti type id
     *
     * @return boolean
     */
    public static function check_lti_1x_id($linkid, $course, $typeid) {
        global $DB;
        // Check if lti type is zero or not (comes from a backup).
        $sqlparams1 = array();
        $sqlparams1['linkid'] = $linkid;
        $sqlparams1['course'] = $course;
        $ltiactivity = $DB->get_record('lti', array('id' => $linkid, 'course' => $course));
        if ($ltiactivity) {
            if ($ltiactivity->typeid == 0) {
                $tool = lti_get_tool_by_url_match($ltiactivity->toolurl, $course);
                if (!$tool) {
                    $tool = lti_get_tool_by_url_match($ltiactivity->securetoolurl, $course);
                }
                return (($tool) && ($typeid == $tool->id));
            } else {
                $sqlparams2 = array();
                $sqlparams2['linkid'] = $linkid;
                $sqlparams2['course'] = $course;
                $sqlparams2['typeid'] = $typeid;
                $sql = 'SELECT lti.* FROM {lti} lti JOIN {lti_types} typ on lti.typeid=typ.id where
            lti.id=? and lti.course=?  and typ.id=?';
                return $DB->record_exists_sql($sql, $sqlparams2);
            }
        } else {
            return false;
        }
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
     * Find the right element in the ltiservice_gradebookservice table for a lineitem
     *
     * @param string $lineitemid            The lineitem
     * @return the gradebookservice id or false if none
     */
    public static function find_ltiservice_gradebookservice_for_lineitem($lineitemid) {
        global $CFG, $DB;

        if (!$lineitemid) {
            return false;
        }
        $gradeitem = $DB->get_record('grade_items', array('id' => $lineitemid));
        if ($gradeitem) {
            if ($gradeitem->iteminstance) {
                if (isset($gradeitem->itemnumber)) {
                    $gbs1 = $DB->get_record('ltiservice_gradebookservices',
                            array('id' => $gradeitem->itemnumber, 'ltilinkid' => $gradeitem->iteminstance));
                    if ($gbs1) {
                        return $gbs1;
                    } else {
                        $gbs2 = $DB->get_record('ltiservice_gradebookservices',
                                array('previousid' => $gradeitem->itemnumber, 'ltilinkid' => $gradeitem->iteminstance));
                        if ($gbs2) {
                            return $gbs2;
                        } else {
                            return false;
                        }
                    }
                } else {
                    return false;
                }
            } else {
                $gbs3 = $DB->get_record('ltiservice_gradebookservices', array('id' => $gradeitem->itemnumber));
                if ($gbs3) {
                    return $gbs3;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Validates specific ISO 8601 format of the timestamps.
     *
     * @param string $ldate The timestamp to check.
     * @return boolean true or false if the date matches the format.
     *
     */

    public static function validate_iso8601_date($date) {
        if (preg_match('/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])' .
                '(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))' .
                '([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)' .
                '?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/', $date) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validates paging query parameters for boundary conditions.
     *
     * @param string $limit maximum number of line items to include in the response, must be greater than one if provided
     * @param string $from offset for the first line item to include in this paged set, must be zero or greater and
     *                    requires a limit
     * @throws \Exception if the paging query parameters are invalid
     */
    public static function validate_paging_query_parameters($limit, $from=null) {

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
