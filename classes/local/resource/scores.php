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
 * This file contains a class definition for the LISResult container resource
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace ltiservice_gradebookservices\local\resource;

use ltiservice_gradebookservices\local\service\gradebookservices;

defined('MOODLE_INTERNAL') || die();

/**
 * A resource implementing LISResult container.
 *
 * @package    ltiservice_gradebookservices
 * @since      Moodle 3.0
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scores extends \mod_lti\local\ltiservice\resource_base {

    /**
     * Class constructor.
     *
     * @param ltiservice_gradebookservices\local\service\gradebookservices $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'Score.collection';
        $this->template = '/{context_id}/lineitems/{item_id}/lineitem/scores';
        $this->variables[] = 'Scores.url';
        $this->formats[] = 'application/vnd.ims.lis.v1.scorecontainer+json';
        $this->formats[] = 'application/vnd.ims.lis.v1.score+json';
        $this->methods[] = 'POST';

    }

    /**
     * Execute the request for this resource.
     *
     * @param mod_lti\local\ltiservice\response $response  Response object for this request.
     */
    public function execute($response) {
        global $CFG, $DB;

        $params = $this->parse_template();
        $contextid = $params['context_id'];
        $itemid = $params['item_id'];

        // GET is disabled by the moment, but we have the code ready
        // for a future implementation.

        $isget = $response->get_request_method() === 'GET';
        if ($isget) {
            $contenttype = $response->get_accept();
        } else {
            $contenttype = $response->get_content_type();
        }
        $container = empty($contenttype) || ($contenttype === $this->formats[0]);
        // We will receive typeid when working with LTI 1.x, if not the we are in LTI 2.
        if (isset($_GET['type_id'])) {
            $typeid = $_GET['type_id'];
        } else {
            $typeid = null;
        }
        try {
            if (is_null($typeid)) {
                if (!$this->check_tool_proxy(null, $response->get_request_data())) {
                    throw new \Exception(null, 401);
                }
            } else {
                switch ($response->get_request_method()) {
                    case 'GET':
                        $response->set_code(405);
                        break;
                    case 'POST':
                        if (!$this->check_type($typeid, $contextid, 'Score.collection:post', $response->get_request_data())) {
                            throw new \Exception(null, 401);
                        }
                        break;
                    default:  // Should not be possible.
                        throw new \Exception(null, 405);
                }
            }
            if (empty($contextid) || !($container ^ ($response->get_request_method() === 'POST')) ||
                (!empty($contenttype) && !in_array($contenttype, $this->formats))) {
                throw new \Exception(null, 400);
            }
            if ($DB->get_record('course', array('id' => $contextid)) === false) {
                throw new \Exception(null, 404);
            }
            if ($DB->get_record('grade_items', array('id' => $itemid)) === false) {
                throw new \Exception(null, 404);
            }
            if (($item = $this->get_service()->get_lineitem($contextid, $itemid, $typeid)) === false) {
                throw new \Exception(null, 403);
            }
            if (is_null($typeid)) {
                if (isset($item->iteminstance) && (!gradebookservices::check_lti_id($item->iteminstance, $item->courseid,
                        $this->get_service()->get_tool_proxy()->id))) {
                            throw new \Exception(null, 403);
                }
            } else {
                if (isset($item->iteminstance) && (!gradebookservices::check_lti_1x_id($item->iteminstance, $item->courseid,
                        $typeid))) {
                            throw new \Exception(null, 403);
                }
            }
            require_once($CFG->libdir.'/gradelib.php');
            switch ($response->get_request_method()) {
                case 'GET':
                    $response->set_code(405);
                    break;
                case 'POST':
                    $json = $this->post_request_json($response, $response->get_request_data(), $item, $contextid, $typeid);
                    $response->set_content_type($this->formats[1]);
                    break;
                default:  // Should not be possible.
                    throw new \Exception(null, 405);
            }
            $response->set_body($json);

        } catch (\Exception $e) {
            $response->set_code($e->getCode());
        }

    }

    /**
     * Generate the JSON for a POST request.
     *
     * @param mod_lti\local\ltiservice\response $response  Response object for this request.
     * @param string $body       POST body
     * @param string $item       Grade item instance
     *
     * return string
     */
    private function post_request_json($response, $body, $item, $contextid, $typeid) {
        $result = json_decode($body);
        if (empty($result) ||
                !isset($result->userId) ||
                !isset($result->timestamp) ||
                !isset($result->gradingProgress) ||
                !isset($result->activityProgress) ||
                !isset($result->timestamp) ||
                isset($result->timestamp) && !gradebookservices::validate_iso8601_date($result->timestamp) ||
                (isset($result->scoreGiven) && !is_numeric($result->scoreGiven)) ||
                (isset($result->scoreMaximum) && !is_numeric($result->scoreMaximum)) ||
                (!gradebookservices::is_user_gradable_in_course($contextid, $result->userId))
                ) {
            throw new \Exception(null, 400);
        }
        $result->timemodified = intval($result->timestamp);

        if (!isset($result->scoreMaximum)) {
            $result->scoreMaximum = 1;
        }
        $response->set_code(200);
        $grade = \grade_grade::fetch(array('itemid' => $item->id, 'userid' => $result->userId));
        if ($grade &&  !empty($grade->timemodified)) {
            if ($grade->timemodified >= strtotime($result->timestamp)) {
                throw new \Exception(null, 403);
            }
        }
        if (isset($result->scoreGiven)) {
            if ($result->gradingProgress == 'FullyGraded') {
                gradebookservices::set_grade_item($item, $result, $result->userId, $typeid);
            } else {
                $this->reset_result($item, $result->userId);
            }
        } else {
            $this->reset_result($item, $result->userId);
        }
    }

    /**
     * Reset a Result.
     *
     * @param object $item       Lineitem instance
     * @param string  $userid    User ID
     */
    private function reset_result($item, $userid) {

        $grade = new \stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        $grade->feedback = null;
        $grade->feedbackformat = FORMAT_MOODLE;
        $status = grade_update('mod/ltiservice_gradebookservices', $item->courseid, $item->itemtype, $item->itemmodule,
                $item->iteminstance, $item->itemnumber, $grade);
        if ($status !== GRADE_UPDATE_OK) {
            throw new \Exception(null, 500);
        }

    }


    /**
     * get permissions from the config of the tool for that resource
     *
     * @return Array with the permissions related to this resource by the $lti_type or null if none.
     */
    public function get_permissions($typeid) {
        $tool = lti_get_type_type_config($typeid);
        if ($tool->ltiservice_gradesynchronization == '1') {
            return array('Score.collection:post');
        } else if ($tool->ltiservice_gradesynchronization == '2') {
            return array('Score.collection:post');
        } else {
            return array();
        }
    }

    /**
     * Parse a value for custom parameter substitution variables.
     *
     * @param string $value String to be parsed
     *
     * @return string
     */
    public function parse_value($value) {
        global $COURSE, $CFG;

        if (strpos($value, '$Scores.url') !== false) {
            require_once($CFG->libdir . '/gradelib.php');

            $resolved = '';
            $this->params['context_id'] = $COURSE->id;
            $id = optional_param('id', 0, PARAM_INT); // Course Module ID.
            if (!empty($id)) {
                $cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
                $id = $cm->instance;
                $item = grade_get_grades($COURSE->id, 'mod', 'lti', $id);
                if ($item && $item->items) {
                    $this->params['item_id'] = $item->items[0]->id;
                    $resolved = parent::get_endpoint();
                }
            }
            $value = str_replace('$Scores.url', $resolved, $value);
        }

        return $value;
    }
}
