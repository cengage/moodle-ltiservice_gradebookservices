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

            switch ($response->get_request_method()) {
                case 'GET':
                    $resourceid = optional_param('resource_id', null, PARAM_TEXT);
                    $resourcelinkid = optional_param('resource_link_id', null, PARAM_TEXT);
                    if (isset($_GET['limit'])) {
                        gradebookservices::validate_paging_query_parameters($_GET['limit']);
                    }
                    $limitnum = optional_param('limit', 0, PARAM_INT);
                    if (isset($_GET['from'])) {
                        gradebookservices::validate_paging_query_parameters($limitnum, $_GET['from']);
                    }
                    $limitfrom = optional_param('from', 0, PARAM_INT);

                    $items = $this->get_service()->get_lineitems($contextid, $resourceid, $resourcelinkid, $limitfrom,
                        $limitnum);

                    $json = $this->get_request_json($contextid, $items, $resourceid, $resourcelinkid, $limitfrom,
                        $limitnum);

                    $response->set_content_type($this->formats[0]);
                    break;
                case 'POST':
                    $json = $this->post_request_json($response->get_request_data(), $contextid);
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
     * @param string $resourcelinkid Resource Link identifier used for filtering, may be null
     * @param int    $limitfrom      Offset of the first line item to return
     * @param int    $limitnum       Maximum number of line items to return, ignored if zero or less
     *
     * return string
     */
    private function get_request_json($contextid, $items, $resourceid, $resourcelinkid, $limitfrom, $limitnum) {

        if (isset($limitnum) && $limitnum > 0) {
            if (count($items) == $limitnum) {
                $limitfrom += $limitnum;
                $nextpage = $this->get_endpoint() . "?limit=" . $limitnum . "&from=" . $limitfrom;
                if (isset($resourceid)) {
                    $nextpage .= "&resource_id={$resourceid}";
                }
                if (isset($resourcelinkid)) {
                    $nextpage .= "&resource_link_id={$resourcelinkid}";
                }
            }
        }

        $json = <<< EOD
{
  "lineItems" : [
EOD;
        $endpoint = parent::get_endpoint();
        $sep = '        ';
        foreach ($items as $item) {
            $json .= $sep . gradebookservices::item_to_json($item, $endpoint);
            $sep = ",\n        ";
        }
        $json .= <<< EOD

  ]
EOD;
        if (isset($nextpage) && ($nextpage)) {
            $json .= ",\n";
            $json .= <<< EOD
  "nextPage" : "{$nextpage}"

EOD;
        }
            $json .= <<< EOD
}
EOD;

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
    private function post_request_json($body, $contextid) {
        global $CFG, $DB;

        $json = json_decode($body);
        if (empty($json)) {
            throw new \Exception(null, 400);
        }

        require_once($CFG->libdir.'/gradelib.php');
        $label = (isset($json->label)) ? $json->label : 'Item ' . time();
        $resourceid = (isset($json->resourceId)) ? $json->resourceId : '';
        $resourcelinkid = (isset($json->resourceLinkId)) ? $json->resourceLinkId : null;
        if ($resourcelinkid != null) {
            if (!gradebookservices::check_lti_id($resourcelinkid, $contextid, $this->get_service()->get_tool_proxy()->id)) {
                throw new \Exception(null, 404);
            }
        }
        $lineitemtoolproviderid = (isset($json->lineItemToolProviderId)) ? $json->lineItemToolProviderId : '';
        $max = 1;
        if (isset($json->scoreMaximum)) {
            $max = $json->scoreMaximum;
        }

        try {
            $gradebookservicesid = $DB->insert_record('ltiservice_gradebookservices', array(
                'toolproxyid' => $this->get_service()->get_tool_proxy()->id,
                    'resourcelinkid' => $resourcelinkid,
                    'lineitemtoolproviderid' => $lineitemtoolproviderid
            ));
        } catch (\Exception $e) {
            throw new \Exception(null, 500);
        }

        $params = array();
        $params['itemname'] = $label;
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $max;
        $params['grademin']  = 0;
        $item = new \grade_item(array('id' => 0, 'courseid' => $contextid));
        \grade_item::set_properties($item, $params);
        $item->itemtype = 'mod';
        $item->itemmodule = 'lti';
        $item->itemnumber = $gradebookservicesid;
        $item->idnumber = $resourceid;
        if (isset($json->resourceLinkId) && is_numeric($json->resourceLinkId)) {
            $item->iteminstance = $json->resourceLinkId;
        }
        $id = $item->insert('mod/ltiservice_gradebookservices');
        $json->id = parent::get_endpoint() . "/{$id}/lineitem";
        $json->results = parent::get_endpoint() . "/{$id}/results";
        $json->scores = parent::get_endpoint() . "/{$id}/scores";

        return json_encode($json, JSON_UNESCAPED_SLASHES);

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

        $this->params['context_id'] = $COURSE->id;

        $value = str_replace('$LineItems.url', parent::get_endpoint(), $value);

        return $value;

    }
}
