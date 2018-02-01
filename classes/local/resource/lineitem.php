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
 * This file contains a class definition for the LineItem resource
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
 * A resource implementing LineItem.
 *
 * @package    ltiservice_gradebookservices
 * @since      Moodle 3.0
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lineitem extends \mod_lti\local\ltiservice\resource_base {

    /**
     * Class constructor.
     *
     * @param ltiservice_gradebookservices\local\service\gradebookservices $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'LineItem.item';
        $this->template = '/{context_id}/lineitems/{item_id}/lineitem';
        $this->variables[] = 'LineItem.url';
        $this->formats[] = 'application/vnd.ims.lis.v2.lineitem+json';
        $this->methods[] = 'GET';
        $this->methods[] = 'PUT';
        $this->methods[] = 'DELETE';

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
        if ($response->get_request_method() === 'GET') {
            $contenttype = $response->get_accept();
        } else {
            $contenttype = $response->get_content_type();
        }
        $isdelete = $response->get_request_method() === 'DELETE';
        // We will receive typeid when working with LTI 1.x, if not then we are in LTI 2.
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
                        if (!$this->check_type($typeid, $contextid, 'LineItem.item:get', $response->get_request_data())) {
                            throw new \Exception(null, 401);
                        }
                        break;
                    case 'PUT':
                        if (!$this->check_type($typeid, $contextid, 'LineItem.item:put', $response->get_request_data())) {
                            throw new \Exception(null, 401);
                        }
                        break;
                    case 'DELETE':
                        if (!$this->check_type($typeid, $contextid, 'LineItem.item:delete', $response->get_request_data())) {
                            throw new \Exception(null, 401);
                        }
                        break;
                    default:  // Should not be possible.
                        throw new \Exception(null, 405);
                }
            }
            if (empty($contextid) || (!empty($contenttype) && !in_array($contenttype, $this->formats))) {
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
            require_once($CFG->libdir.'/gradelib.php');
            switch ($response->get_request_method()) {
                case 'GET':
                    $this->get_request($response, $contextid, $item, $typeid);
                    break;
                case 'PUT':
                    $json = $this->put_request($response->get_request_data(), $item, $typeid);
                    $response->set_body($json);
                    $response->set_code(200);
                    break;
                case 'DELETE':
                    $this->delete_request($item);
                    $response->set_code(204);
                    break;
                default:  // Should not be possible.
                    throw new \Exception(null, 405);
            }

        } catch (\Exception $e) {
            $response->set_code($e->getCode());
        }

    }

    /**
     * Process a GET request.
     *
     * @param mod_lti\local\ltiservice\response $response  Response object for this request.
     * @param boolean $results   True if results are to be included in the response.
     * @param string  $item       Grade item instance.
     */
    private function get_request($response, $contextid, $item, $typeid) {

        $response->set_content_type($this->formats[0]);
        $json = gradebookservices::item_to_json($item, substr(parent::get_endpoint(),
                0, strrpos(parent::get_endpoint(), "/", -10)), $typeid);
        $response->set_body($json);

    }

    /**
     * Process a PUT request.
     *
     * @param string $body       PUT body
     * @param string $olditem    Grade item instance
     */
    private function put_request($body, $olditem, $typeid) {
        global $DB;
        $json = json_decode($body);
        if (empty($json) ||
                !isset($json->scoreMaximum) ||
                !isset($json->label)) {
            throw new \Exception(null, 400);
        }
        $item = \grade_item::fetch(array('id' => $olditem->id, 'courseid' => $olditem->courseid));
        $gbs = gradebookservices::find_ltiservice_gradebookservice_for_lineitem($olditem->id);
        $updategradeitem = false;
        $upgradegradebookservices = false;
        if ($item->itemname !== $json->label) {
            $updategradeitem = true;
        }
        $item->itemname = $json->label;
        if (!is_numeric($json->scoreMaximum)) {
            throw new \Exception(null, 400);
        } else {
            if (grade_floats_different(grade_floatval($item->grademax),
                    grade_floatval($json->scoreMaximum))) {
                $updategradeitem = true;
            }
            $item->grademax = grade_floatval($json->scoreMaximum);
        }
        $resourceid = (isset($json->resourceId)) ? $json->resourceId : '';
        if ($item->idnumber !== $resourceid) {
            $updategradeitem = true;
        }
        $item->idnumber = $resourceid;
        if ($gbs) {
            $tag = (isset($json->tag)) ? $json->tag : null;
            if ($gbs->tag !== $tag) {
                $upgradegradebookservices = true;
            }
            $gbs->tag = $tag;
        }
        if (isset($json->ltiLinkId)) {
            if (is_numeric($json->ltiLinkId)) {
                if (intval($item->iteminstance) !== intval($json->ltiLinkId)) {
                    $updategradeitem = true;
                    if ($gbs) {
                        $upgradegradebookservices = true;
                    }
                }
                $item->iteminstance = intval($json->ltiLinkId);
            } else {
                throw new \Exception(null, 400);
            }
        } else { // This should never happen if $gbs is false, but just in case let's avoid it.
            if ($gbs) {
                if ($item->iteminstance !== null) {
                    $updategradeitem = true;
                    $upgradegradebookservices = true;
                }
                $item->iteminstance = null;
            }
        }
        if ($item->iteminstance != null) {
            if (is_null($typeid)) {
                if (!gradebookservices::check_lti_id($item->iteminstance, $item->courseid,
                        $this->get_service()->get_tool_proxy()->id)) {
                            throw new \Exception(null, 403);
                }
            } else {
                if (!gradebookservices::check_lti_1x_id($item->iteminstance, $item->courseid,
                        $typeid)) {
                            throw new \Exception(null, 403);
                }
            }
        }
        if ($updategradeitem) {
            if (!$item->update('mod/ltiservice_gradebookservices')) {
                throw new \Exception(null, 500);
            }
        }

        $lineitem = new lineitem($this->get_service());
        $endpoint = $lineitem->get_endpoint();

        if ($upgradegradebookservices) {
            try {
                if (is_null($typeid)) {
                    $toolproxyid = $this->get_service()->get_tool_proxy()->id;
                    $baseurl = null;
                } else {
                    $toolproxyid = null;
                    $baseurl = lti_get_type_type_config($typeid)->lti_toolurl;
                }
                $gradebookservicesid = $DB->update_record('ltiservice_gradebookservices', array(
                        'id' => $gbs->id,
                        'itemnumber' => $gbs->itemnumber,
                        'courseid' => $gbs->courseid,
                        'toolproxyid' => $toolproxyid,
                        'typeid' => $typeid,
                        'baseurl' => $baseurl,
                        'ltilinkid' => $item->iteminstance,
                        'tag' => $gbs->tag
                ));
            } catch (\Exception $e) {
                throw new \Exception(null, 500);
            }
        }

        if (is_null($typeid)) {
            $id = "{$endpoint}";
            $json->id = $id;
        } else {
            $id = "{$endpoint}?type_id={$typeid}";
            $json->id = $id;
        }
        return json_encode($json, JSON_UNESCAPED_SLASHES);

    }

    /**
     * Process a DELETE request.
     *
     * @param string $item       Grade item instance
     */
    private function delete_request($item) {
        global $DB;

        $gradeitem = \grade_item::fetch(array('id' => $item->id, 'courseid' => $item->courseid));
        if (($gbs = gradebookservices::find_ltiservice_gradebookservice_for_lineitem($item->id)) == false) {
            throw new \Exception(null, 403);
        }
        if (!$gradeitem->delete('mod/ltiservice_gradebookservices')) {
            throw new \Exception(null, 500);
        } else {
            $sqlparams = array();
            $sqlparams['id'] = $gbs->id;
            if (!$DB->delete_records('ltiservice_gradebookservices', $sqlparams)) {
                throw new \Exception(null, 500);
            }
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
            return array('LineItem.item:get');
        } else if ($tool->ltiservice_gradesynchronization == '2') {
            return array('LineItem.item:get', 'LineItem.item:put', 'LineItem.item:delete');
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
        if (strpos($value, '$LineItem.url') !== false) {
            $resolved = '';
            require_once($CFG->libdir . '/gradelib.php');

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
            $value = str_replace('$LineItem.url', $resolved, $value);
        }
        return $value;
    }
}
