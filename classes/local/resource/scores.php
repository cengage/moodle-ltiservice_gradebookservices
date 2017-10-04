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
        $this->template = '/{context_id}/lineitems/{item_id}/scores';
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

        try {
            if (!$this->check_tool_proxy(null, $response->get_request_data())) {
                throw new \Exception(null, 401);
            }
            if (empty($contextid) || !($container ^ ($response->get_request_method() === 'POST')) ||
                (!empty($contenttype) && !in_array($contenttype, $this->formats))) {
                throw new \Exception(null, 400);
            }
            if ($DB->get_record('course', array('id' => $contextid)) === false) {
                throw new \Exception(null, 404);
            }
            if (($item = $this->get_service()->get_lineitem($contextid, $itemid, true)) === false) {
                throw new \Exception(null, 404);
            }
            require_once($CFG->libdir.'/gradelib.php');
            switch ($response->get_request_method()) {
                case 'GET':
                    $response->set_code(405);
                    break;
                case 'POST':
                    $json = $this->post_request_json($response, $response->get_request_data(), $item);
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
    private function post_request_json($response, $body, $item) {

        $result = json_decode($body);
        if (empty($result) ||
            !isset($result->userId) ||
            !isset($result->scoreGiven)|| !isset($result->gradingProgress)) {
            throw new \Exception(null, 400);
        }
        $newgrade = true;
        $grade = \grade_grade::fetch(array('itemid' => $item->id, 'userid' => $result->userId));
        if ($grade &&  !empty($grade->timemodified)) {
            $newgrade = false;
        }
        if ($result->gradingProgress != 'FullyGraded') {
            $this->reset_result($item, $result->userId);
        } else {
            gradebookservices::set_grade_item($item, $result, $result->userId);
        }
        if ($newgrade) {
            $response->set_code(201);
        } else {
            $response->set_code(200);
        }
        $lineitem = new lineitem($this->get_service());
        $endpoint = $lineitem->get_endpoint();
        $id = "{$endpoint}/scores/{$result->userId}";
        $result->id = $id;
        $result->scoreOf = $endpoint;
        return json_encode($result, JSON_UNESCAPED_SLASHES);

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
     * Parse a value for custom parameter substitution variables.
     *
     * @param string $value String to be parsed
     *
     * @return string
     */
    public function parse_value($value) {
        global $COURSE, $CFG;

        require_once($CFG->libdir . '/gradelib.php');

        $item = grade_get_grades($COURSE->id, 'mod', 'lti', $id);
        if ($item) {
            $this->params['context_id'] = $COURSE->id;
            $id = optional_param('id', 0, PARAM_INT); // Course Module ID.
            if (!empty($id)) {
                $cm = get_coursemodule_from_id('lti', $id, 0, false);
                if ($cm) {
                    $id = $cm->instance;
                }
            }
            $this->params['item_id'] = $item->items[0]->id;

            $value = str_replace('$Scores.url', parent::get_endpoint(), $value);

            return $value;
        } else {
            return '';
        }

    }
}
