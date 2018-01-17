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
 * This file contains a class definition for the LineItem container resource
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
 * A resource implementing LineItem container.
 *
 * @package    ltiservice_gradebookservices
 * @since      Moodle 3.0
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lineitems extends \mod_lti\local\ltiservice\resource_base {

    /**
     * Class constructor.
     *
     * @param ltiservice_gradebookservices\local\service\gradebookservices $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'LineItem.collection';
        $this->template = '/{context_id}/lineitems';
        $this->variables[] = 'LineItems.url';
        $this->formats[] = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
        $this->formats[] = 'application/vnd.ims.lis.v2.lineitem+json';
        $this->methods[] = 'GET';
        $this->methods[] = 'POST';

    }

    /**
     * Execute the request for this resource.
     *
     * @param mod_lti\local\ltiservice\response $response  Response object for this request.
     */
    public function execute($response) {
        global $DB;

        $params = $this->parse_template();
        $contextid = $params['context_id'];
        $isget = $response->get_request_method() === 'GET';
        if ($isget) {
            $contenttype = $response->get_accept();
        } else {
            $contenttype = $response->get_content_type();
        }
        $container = empty($contenttype) || ($contenttype === $this->formats[0]);
        // We will receive typeid when working with LTI 1.x, if not the we are in LTI 2.
        if (isset($_GET['typeid'])) {
            $typeid = $_GET['typeid'];
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
                        if (!$this->check_type($typeid, $contextid, 'LineItem.collection:get', $response->get_request_data())) {
                            throw new \Exception(null, 401);
                        }
                        break;
                    case 'POST':
                        if (!$this->check_type($typeid, $contextid, 'LineItem.collection:post', $response->get_request_data())) {
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
                $response->set_reason("Not Found: Course ". $contextid." doesn't exist.");
                throw new \Exception(null, 404);
            }
            switch ($response->get_request_method()) {
                case 'GET':
                    $resourceid = optional_param('resource_id', null, PARAM_TEXT);
                    $ltilinkid = optional_param('lti_link_id', null, PARAM_TEXT);
                    if (isset($_GET['limit'])) {
                        gradebookservices::validate_paging_query_parameters($_GET['limit']);
                    }
                    $limitnum = optional_param('limit', 0, PARAM_INT);
                    if (isset($_GET['from'])) {
                        gradebookservices::validate_paging_query_parameters($limitnum, $_GET['from']);
                    }
                    $limitfrom = optional_param('from', 0, PARAM_INT);
                    $itemsandcount = $this->get_service()->get_lineitems($contextid, $resourceid, $ltilinkid, $limitfrom,
                            $limitnum, $typeid);
                    $items = $itemsandcount[1];
                    $totalcount = $itemsandcount[0];
                    $json = $this->get_request_json($contextid, $items, $resourceid, $ltilinkid, $limitfrom,
                            $limitnum, $totalcount, $typeid, $response);
                    $response->set_content_type($this->formats[0]);
                    break;
                case 'POST':
                    $json = $this->post_request_json($response->get_request_data(), $contextid, $typeid);
                    $response->set_code(201);
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
     * Generate the JSON for a GET request.
     *
     * @param string $contextid      Course ID
     * @param array  $items          Array of lineitems
     * @param string $resourceid     Resource identifier used for filtering, may be null
     * @param string $ltilinkid Resource Link identifier used for filtering, may be null
     * @param int    $limitfrom      Offset of the first line item to return
     * @param int    $limitnum       Maximum number of line items to return, ignored if zero or less
     * @param int    $totalcount     Number of total lineitems before filtering for paging
     * @param int    $typeid         Maximum number of line items to return, ignored if zero or less
     * return string
     */
    private function get_request_json($contextid, $items, $resourceid, $ltilinkid, $limitfrom, $limitnum, $totalcount, $typeid, $response) {

        if (isset($limitnum) && $limitnum > 0) {
            if ($limitfrom >= $totalcount || $limitfrom < 0) {
                $outofrange = true;
            } else {
                $outofrange = false;
            }
            $limitprev = $limitfrom - $limitnum >=0 ? $limitfrom - $limitnum : 0;
            $limitcurrent= $limitfrom;
            $limitlast = $totalcount-$limitnum+1 >= 0 ? $totalcount-$limitnum+1: 0;
            $limitfrom += $limitnum;
            if (is_null($typeid)) {
                if (($limitfrom <= $totalcount -1) && (!$outofrange)) {
                    $nextpage = $this->get_endpoint() . "?limit=" . $limitnum . "&from=" . $limitfrom;
                } else {
                    $nextpage= null;
                }
                $firstpage = $this->get_endpoint() . "?limit=" . $limitnum . "&from=0";
                $canonicalpage = $this->get_endpoint() . "?limit=" . $limitnum . "&from=" . $limitcurrent;
                $lastpage = $this->get_endpoint() . "?limit=" . $limitnum . "&from=" . $limitlast;
                if (($limitcurrent> 0) && (!$outofrange)) {
                    $prevpage = $this->get_endpoint() . "?limit=" . $limitnum . "&from=" . $limitprev;
                } else {
                    $prevpage = null;
                }
            } else {
                if (($limitfrom <= $totalcount -1) && (!$outofrange)) {
                    $nextpage = $this->get_endpoint() . "?typeid=" . $typeid . "&limit=" . $limitnum . "&from=" . $limitfrom;
                } else {
                    $nextpage= null;
                }
                $firstpage = $this->get_endpoint() . "?typeid=" . $typeid . "&limit=" . $limitnum . "&from=0";
                $canonicalpage = $this->get_endpoint() . "?typeid=" . $typeid . "&limit=" . $limitnum . "&from=" . $limitcurrent;
                $lastpage = $this->get_endpoint() . "?typeid=" . $typeid . "&limit=" . $limitnum . "&from=" . $limitlast;
                if (($limitcurrent> 0) && (!$outofrange)) {
                    $prevpage = $this->get_endpoint() . "?typeid=" . $typeid . "&limit=" . $limitnum . "&from=" . $limitprev;
                } else {
                    $prevpage = null;
                }
            }
            if (isset($resourceid)) {
                if (($limitfrom <= $totalcount -1) && (!$outofrange)) {
                    $nextpage .= "&resource_id={$resourceid}";
                }
                $firstpage .= "&resource_id={$resourceid}";
                $canonicalpage .= "&resource_id={$resourceid}";
                $lastpage .= "&resource_id={$resourceid}";
                if (($limitcurrent> 0) && (!$outofrange)) {
                    $prevpage .= "&resource_id={$resourceid}";
                }
            }
            if (isset($ltilinkid)) {
                if (($limitfrom <= $totalcount -1) && (!$outofrange)) {
                    $nextpage .= "&lti_link_id={$ltilinkid}";
                }
                $firstpage .= "&lti_link_id={$ltilinkid}";
                $canonicalpage .= "&lti_link_id={$ltilinkid}";
                $lastpage .= "&lti_link_id={$ltilinkid}";
                if (($limitcurrent > 0) && (!$outofrange)) {
                    $prevpage .= "&lti_link_id={$ltilinkid}";
                }
            }
        }

        $json = <<< EOD
  [
EOD;
        $endpoint = parent::get_endpoint();
        $sep = '        ';
        foreach ($items as $item) {
            $json .= $sep . gradebookservices::item_to_json($item, $endpoint, $typeid);
            $sep = ",\n        ";
        }
        $json .= <<< EOD

  ]
EOD;
        if (isset($canonicalpage) && ($canonicalpage)) {
            $links = 'Link: <' . $firstpage . '>; rel=“first”';
            if (!(is_null($prevpage))) {
                $links .= ', <' . $prevpage . '>; rel=“prev”';
            }
            $links .= ', <' . $canonicalpage. '>; rel=“canonical”';
            if (!(is_null($nextpage))) {
                $links .= ', <' . $nextpage . '>; rel=“next”';
            }
            $links .= ', <' . $lastpage . '>; rel=“last”';
            $response->add_additional_header($links);
        }
        return $json;
    }

    /**
     * Generate the JSON for a POST request.
     *
     * @param string $body       POST body
     * @param string $contextid  Course ID
     *
     * return string
     */
    private function post_request_json($body, $contextid, $typeid) {
        global $CFG, $DB;

        $json = json_decode($body);
        if (empty($json) ||
                !isset($json->scoreMaximum) ||
                !isset($json->label)) {
            throw new \Exception(null, 400);
        }
        if (is_numeric($json->scoreMaximum)) {
            $max = $json->scoreMaximum;
        } else {
            throw new \Exception(null, 400);
        }
        require_once($CFG->libdir.'/gradelib.php');
        $resourceid = (isset($json->resourceId)) ? $json->resourceId : '';
        $ltilinkid = (isset($json->ltiLinkId)) ? $json->ltiLinkId : null;
        if ($ltilinkid != null) {
            if (is_null($typeid)) {
                if (!gradebookservices::check_lti_id($ltilinkid, $contextid, $this->get_service()->get_tool_proxy()->id)) {
                    throw new \Exception(null, 403);
                }
            } else {
                if (!gradebookservices::check_lti_1x_id($ltilinkid, $contextid, $typeid)) {
                    throw new \Exception(null, 403);
                }
            }
        }
        $tag = (isset($json->tag)) ? $json->tag : '';
        if (is_null($typeid)) {
            $toolproxyid = $this->get_service()->get_tool_proxy()->id;
            $baseurl = null;
        } else {
            $toolproxyid = null;
            $baseurl = lti_get_type_type_config($typeid)->lti_toolurl;
        }
        try {
            $gradebookservicesid = $DB->insert_record('ltiservice_gradebookservices', array(
                    'toolproxyid' => $toolproxyid,
                    'typeid' => $typeid,
                    'baseurl' => $baseurl,
                    'ltilinkid' => $ltilinkid,
                    'tag' => $tag
            ));
        } catch (\Exception $ex) {
            debugging('Error adding an entry in ltiservice_gradebookservices:' . $ex->getMessage());
            throw new \Exception(null, 500);
        }

        $params = array();
        $params['itemname'] = $json->label;
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $max;
        $params['grademin']  = 0;
        $item = new \grade_item(array('id' => 0, 'courseid' => $contextid));
        \grade_item::set_properties($item, $params);
        $item->itemtype = 'mod';
        $item->itemmodule = 'lti';
        $item->itemnumber = $gradebookservicesid;
        $item->idnumber = $resourceid;
        if (isset($json->ltiLinkId) && is_numeric($json->ltiLinkId)) {
            $item->iteminstance = $json->ltiLinkId;
        }
        $id = $item->insert('mod/ltiservice_gradebookservices');
        if (is_null($typeid)) {
            $json->id = parent::get_endpoint() . "/{$id}/lineitem";
            $json->results = parent::get_endpoint() . "/{$id}/results";
            $json->scores = parent::get_endpoint() . "/{$id}/scores";
        } else {
            $json->id = parent::get_endpoint() . "/{$id}/lineitem?typeid={$typeid}";
            $json->results = parent::get_endpoint() . "/{$id}/results?typeid={$typeid}";
            $json->scores = parent::get_endpoint() . "/{$id}/scores?typeid={$typeid}";
        }
        return json_encode($json, JSON_UNESCAPED_SLASHES);

    }

    /**
     * get permissions from the config of the tool for that resource
     *
     * @return Array with the permissions related to this resource by the $lti_type or null if none.
     */
    public function get_permissions($typeid) {
        $tool = lti_get_type_type_config($typeid);
        if ($tool->ltiservice_gradesynchronization == '1') {
            return array('LineItem.collection:get');
        } else if ($tool->ltiservice_gradesynchronization == '2') {
            return array('LineItem.collection:get', 'LineItem.collection:post');
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
        global $COURSE;

        if (strpos($value, '$LineItems.url') !== false) {
            $this->params['context_id'] = $COURSE->id;
            $value = str_replace('$LineItems.url', parent::get_endpoint(), $value);
        }

        return $value;

    }
}
