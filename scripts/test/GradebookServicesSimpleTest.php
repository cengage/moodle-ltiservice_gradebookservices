<?php
require_once 'HTTP/Request2.php';

// IMPORTANT:
// THIS IS NOT A MOODLE FILE. This is a php utility that should be launched
// from the command line to test externally the Gradebookservices API
// If this is inside your lms code is because the gradebookservices zip file installer
// Has not been correctly generated, and you can delete this script/test folder.

//////////////////
//1. CONFIGURATION
//////////////////

//////////////////
//1.0 CONNECTION
//////////////////

//Connection parameters

$base_url="YOURMOODLEURL/mod/lti/services.php/";
$secret = 'THETOOLPROXYSECRET';
$consumer_key = 'THETOOLPROXYCONSUMERKEY';

//////////////////
//1.1 WHAT TO CALL
// Set to true the service you want to call
//////////////////

//LINEITEMS
$query_lineitems_get = false;
$query_lineitems_post = false;
//LINEITEM
$query_lineitem_get = false;
$query_lineitem_put = false;
$query_lineitem_delete = false;
//SCORES
$query_scores_post = false;
//RESULT
$query_result_get = false;
//RESULTS
$query_results_get = true;

//These 2 will return always 405
$query_scores_get = false;

//////////////////
//1.2 PARAMETERS
// Change the parameters to build the queries
//////////////////

//IDS
$course_id = '3';
$lineitem_id = '58';
$use_lti_link_id_post=true; //To decide if we will create a decoupled or coupled lineitem
$lti_link_id_post=1;
$use_lti_link_id_put=false; //To decide if we will create a decoupled or coupled lineitem
$lti_link_id_put=1;
$score_score_of_post = "5";
$score_id='2';
$result_id = '2';

//LINEITEMS
$item_label_post='New Line';
$item_label_put='New Line Item updated PUT';
$line_item_score_maximum_post='10.0';
$resource_id_post='0000000000001';
$resource_id_put='0000000000002';

// Include leading ? if provided. Can comment out to omit.
// ?limit=5
// ?limit=5&from=1      (from record offset, if provided, limit must be specified)
// ?resource_id=999     (filter for line items associated with specific TP resource ID)
// ?lti_link_id=1  (filter for line items associated with specific TC resource link ID)
//$line_items_query_string='?lti_link_id=1&limit=2&from=1&resource_id=0000000000001';
//$line_items_query_string='?limit=1';
//SCORES
$score_result_user_id='4';
$score_score_given_post = '5';
$score_score_maximum_post = '10';
$score_comment_post = "Bad job";
//Initialized, Started, InProgress, Submitted, Completed
$score_activity_progress_post = "Completed";
$score_timestamp_post=date('c',time());
// Include leading ? if provided. Can comment out to omit.
// ?limit=5
// ?limit=5&from=2      (from record offset, if provided, limit must be specified)
//$scores_query_string='?limit=2&from=1';


//SCORE_PROGRESSES
//These are the possible values
//FullyGraded, Pending, PendingManual, Failed, NotReady
$score_progress_post = "FullyGraded";

// RESULTS
// Include leading ? if provided. Can comment out to omit.
// ?limit=5
// ?limit=5&from=2      (from record offset, if provided, limit must be specified)
//$results_query_string='?from=2';
//$results_query_string='?limit=3&from=2';
//$results_query_string='?limit=3';

//////////////////
//1.3  JSON BODIES
//This should be generated with the previous parameters. Usually
// there is no need to change them manually unless you want to test an error.
//////////////////

//Lineitems
$postdata_false = null; //Don't change this one

$postdata_lineitems_post = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';

$postdata_lineitems_lti_link_id_post = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","ltiLinkId":"'.$lti_link_id_post.'","tag":"lmsint-grade"}';

$postdata_lineitem_put = '{"scoreMaximum":'.
$line_item_score_maximum_put.',"label":"'.$item_label_put.'","resourceId":"'.$resource_id_put.'","tag":"lmsint-grademod"}';

$postdata_lineitem_lti_link_id_put = '{"scoreMaximum":'.
        $line_item_score_maximum_put.',"label":"'.$item_label_put.'","resourceId":"'.$resource_id_put.'","ltiLinkId":"'.$lti_link_id_put.'","tag":"lmsint-grade"}';

$postdata_lineitems_delete = null;

//Scores
$postdata_scores_post = '{"scoreGiven":'.$score_score_given_post.',"scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$score_result_user_id.'"}';

//Result and results are just a get


//////////////////
//1.4 URLS
//This should be generated with the previous parameters. Usually
// there is no need to change them manually unless you want to test an error.
//////////////////

//the first number will be the course id. The second the lineitem id that we want to manage.
$url_lineitems = $base_url . $course_id . '/lineitems';
if (!empty($line_items_query_string)) {
  $url_lineitems = $url_lineitems . $line_items_query_string;
}
$url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem';
$url_scores = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/scores';
if (!empty($scores_query_string)) {
  $url_scores = $url_scores . $scores_query_string;
}
$url_result = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/results/'. $result_id .'/result';
$url_results = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/results';
if (!empty($results_query_string)) {
  $url_results = $url_results . $results_query_string;
}


//////////////////
//1.5 CONTENT TYPES
//////////////////

//These doesn't need to change.
$lineitem_content = 'application/vnd.ims.lis.v2.lineitem+json';
$lineitemcontainer_content = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
$scorecontainer_content = 'application/vnd.ims.lis.v1.scorecontainer+json';
$result_content = 'application/vnd.ims.lis.v2.result+json';
$resultcontainer_content = 'application/vnd.ims.lis.v1.resultcontainer+json';

//////////////////
//1.6 HTTP METHODS
//////////////////


$http_method_get = 'GET';
$http_method_post = 'POST';
$http_method_put = 'PUT';
$http_method_delete = 'DELETE';


//////////////////
//2. CALLS
//////////////////



//POST lineitems
if($query_lineitems_post){
echo "--------------------------------POST LINEITEMS----------------------------------\n";
echo "URL: {$url_lineitems}";
echo "\n";
echo "\n";
echo $postdata_lineitems_post;
echo "\n";
echo "\n";
if($use_lti_link_id_post){
    call_service($url_lineitems,$http_method_post,$lineitem_content,$postdata_lineitems_lti_link_id_post, $consumer_key, $secret);
}else{
call_service($url_lineitems,$http_method_post,$lineitem_content,$postdata_lineitems_post, $consumer_key, $secret);
}
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}
//GET lineitems
if($query_lineitems_get){
echo "--------------------------------GET LINEITEMS-----------------------------------\n";
echo "URL: {$url_lineitems}";
echo "\n";
echo "\n";
call_service($url_lineitems, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}


//PUT lineitem
if($query_lineitem_put){
//echo $postdata_lineitem_put;
echo "--------------------------------PUT LINEITEM------------------------------------\n";
echo "URL: {$url_lineitem}";
echo "\n";
echo "\n";
echo $postdata_lineitem_put;
echo "\n";
echo "\n";
if($use_lti_link_id_put){
    call_service($url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_lti_link_id_put, $consumer_key, $secret);
}else{
call_service($url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);
}
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}
//GET lineitem
if($query_lineitem_get){
echo "--------------------------------GET LINEITEM------------------------------------\n";
echo "URL: {$url_lineitem}";
echo "\n";
echo "\n";
call_service($url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}
//DELETE lineitem
if($query_lineitem_delete){
//echo $postdata_lineitem_delete;
echo "--------------------------------DELETE LINEITEM---------------------------------\n";
echo "URL: {$url_lineitem}";
echo "\n";
echo "\n";
call_service($url_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}

//GET scores
if($query_scores_get){
echo "--------------------------------GET SCORES--------------------------------------\n";
echo "URL: {$url_scores}";
echo "\n";
echo "\n";
call_service($url_scores, $http_method_get, $scorecontainer_content, $postdata_false, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}
//POST scores
if($query_scores_post){
//echo $postdata_scores_post;
echo "--------------------------------POST SCORES-------------------------------------\n";
echo "URL: {$url_scores}";
echo "\n";
echo "\n";
echo $postdata_scores_post;
echo "\n";
echo "\n";
call_service($url_scores,$http_method_post,$score_content,$postdata_scores_post, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}

//GET result
if($query_result_get){
echo "--------------------------------GET RESULT---------------------------------------\n";
echo "URL: {$url_result}";
echo "\n";
echo "\n";
call_service($url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}

//GET results
if($query_results_get){
echo "--------------------------------GET RESULTS--------------------------------------\n";
echo "URL: {$url_results}";
echo "\n";
echo "\n";
call_service($url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);
echo "\n";
echo "--------------------------------------------------------------------------------\n";
echo "\n";
}


//////////////////
//3. AUXILIAR FUNCTIONS
//////////////////


function call_service($url, $method, $content_type, $body, $key, $secret) {

    $request = new HTTP_Request2();
    $request-> setUrl($url);
    $request-> setMethod($method);
    if ($method == 'GET') {
        $request-> setHeader('accept', $content_type);
    } else {
        $request-> setHeader('content-type', $content_type);
        $request-> setBody($body);
    }
    $request-> setHeader('cache-control', 'no-cache');
    $request-> setHeader('authorization', generateOauth($url, $method, $body, $key, $secret));

    try {
        $response = $request->send();
        echo $response->getBody() . "\n";
        //if ($response->getStatus() != 200 ){
            echo $response->getStatus();
        //}
        //////////////// EXTRA INFO ///////////////////
            /*
             echo "Response status: " . $response->getStatus() . "\n";
             echo "Human-readable reason phrase: " . $response->getReasonPhrase() . "\n";
             echo "Response HTTP version: " . $response->getVersion() . "\n";
             echo "Response headers:\n";
             foreach ($response->getHeader() as $k => $v) {
             echo "\t{$k}: {$v}\n";
             }
             echo "Value of a specific header (Content-Type): " . $response->getHeader('content-type') . "\n";
             echo "Cookies set in response:\n";
             foreach ($response->getCookies() as $c) {
             echo "\tname: {$c['name']}, value: {$c['value']}" .
             (empty($c['expires'])? '': ", expires: {$c['expires']}") .
             (empty($c['domain'])? '': ", domain: {$c['domain']}") .
             (empty($c['path'])? '': ", path: {$c['path']}") .
             ", secure: " . ($c['secure']? 'yes': 'no') . "\n";
             
             }
             echo "Response body:\n" . $response->getBody();
             */
    } catch (HttpException $ex) {
        echo $ex;
    }

}



function generateOauth($url, $method, $body, $consumer_key, $secret) {

    if (empty($body)){
        $hash1 = base64_encode(sha1(false, true));
    }else{
        $hash1 = base64_encode(sha1($body, true));
    }

    if (strpos($hash1, '+') !== false) {
      echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
      echo "IMPORTANT: Your hash " . $hash1 . "has a + on it, and this method is not encoding it correctly, please change any of the parameters like label or resource id and try again \n";
      echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    }

    $parameters_body = array(
        "oauth_version" => '1.0',
        "oauth_nonce" => generate_nonce(),
        "oauth_timestamp" => time(),
        "oauth_signature_method" => 'HMAC-SHA1',
        "oauth_consumer_key" => $consumer_key,
        "oauth_body_hash" => $hash1
    );

    $parameters_body = array_merge( OAuthUtil::parse_parameters(parse_url($url, PHP_URL_QUERY)), $parameters_body);

    $base_string_body = get_signature_base_string_body($url, $method, $parameters_body);
    $key_parts = array($secret, '');
    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);
    $signature = base64_encode(hash_hmac('sha1', $base_string_body, $key, true));


    $OauthgString_body = 'OAuth oauth_body_hash="'.$parameters_body["oauth_body_hash"].
    '",oauth_consumer_key="'.$parameters_body["oauth_consumer_key"].
    '",oauth_signature_method="HMAC-SHA1",oauth_timestamp="'.$parameters_body["oauth_timestamp"].
    '",oauth_nonce="'.$parameters_body["oauth_nonce"].
    '",oauth_version="1.0",oauth_signature="'.$signature.
    '"';


    //echo "Parameters ";
    //print_r($parameters_body);
    //echo "\n";
    //echo "Signature " . $signature . "\n";
    //echo "Signature String: " . $base_string_body . "\n";
    //echo "\n\nOauth String: \n";
    //echo $OauthgString_body;
    //echo "\n";
    //echo "\n";

    if (strpos($signature, '+') !== false) {
        return generateOauth($url, $method, $body, $consumer_key, $secret);
    } else {
        return $OauthgString_body;
    }



}


function get_signable_parameters($parameters) {

    return OAuthUtil::build_http_query($parameters);
}

function get_signable_parameters_body($parameters) {

    return OAuthUtil::build_http_query($parameters);
}

function get_normalized_http_url($url) {

    $parts = parse_url($url);

    $port = @$parts['port'];
    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $path = @$parts['path'];

    $port or $port = ($scheme == 'https') ? '443' : '80';

    if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
        $host = "$host:$port";
    }
    return "$scheme://$host$path";
}

function get_normalized_http_method($method) {
    return strtoupper($method);
}



function get_signature_base_string_body($url, $method, $parameters) {
    $parts = array(
        get_normalized_http_method($method),
        get_normalized_http_url($url),
        get_signable_parameters($parameters)
    );

    $parts = OAuthUtil::urlencode_rfc3986($parts);

    return implode('&', $parts);
}





function generate_nonce() {
    $mt = microtime();
    $rand = mt_rand();

    return md5($mt.$rand); // md5s look nicer than numbers
}




class OAuthUtil {
    public static
    function urlencode_rfc3986($input) {
        if (is_array($input)) {
            return array_map(array('OAuthUtil', 'urlencode_rfc3986'), $input);
        } else if (is_scalar($input)) {
            return str_replace(
                '+',
                ' ',
                str_replace('%7E', '~', rawurlencode($input))
            );
        } else {
            return '';
        }
    }


    // This decode function isn't taking into consideration the above
    // modifications to the encoding process. However, this method doesn't
    // seem to be used anywhere so leaving it as is.
    public static
    function urldecode_rfc3986($string) {
        return urldecode($string);
    }

    // Utility function for turning the Authorization: header into
    // parameters, has to do some unescaping
    // Can filter out any non-oauth parameters if needed (default behaviour)
    public static
    function split_header($header, $only_allow_oauth_parameters = true) {
        $pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
        $offset = 0;
        $params = array();
        while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0) {
            $match = $matches[0];
            $header_name = $matches[2][0];
            $header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];
            if (preg_match('/^oauth_/', $header_name) || !$only_allow_oauth_parameters) {
                $params[$header_name] = OAuthUtil::urldecode_rfc3986($header_content);
            }
            $offset = $match[1] + strlen($match[0]);
        }

        if (isset($params['realm'])) {
            unset($params['realm']);
        }

        return $params;
    }

    // helper to try to sort out headers for people who aren't running apache
    public static
    function get_headers() {
        if (function_exists('apache_request_headers')) {
            // we need this to get the actual Authorization: header
            // because apache tends to tell us it doesn't exist
            return apache_request_headers();
        }
        // otherwise we don't have apache and are just going to have to hope
        // that $_SERVER actually contains what we need
        $out = array();
        foreach($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == "HTTP_") {
                // this is chaos, basically it is just there to capitalize the first
                // letter of every word that is not an initial HTTP and strip HTTP
                // code from przemek
                $key = str_replace(
                    " ",
                    "-",
                    ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
                );
                $out[$key] = $value;
            }
        }
        return $out;
    }

    // This function takes a input like a=b&a=c&d=e and returns the parsed
    // parameters like this
    // array('a' => array('b','c'), 'd' => 'e')
    public static
    function parse_parameters($input) {
        if (!isset($input) || !$input) return array();

        $pairs = explode('&', $input);

        $parsed_parameters = array();
        foreach($pairs as $pair) {
            $split = explode('=', $pair, 2);
            $parameter = OAuthUtil::urldecode_rfc3986($split[0]);
            $value = isset($split[1]) ? OAuthUtil::urldecode_rfc3986($split[1]) : '';

            if (isset($parsed_parameters[$parameter])) {
                // We have already recieved parameter(s) with this name, so add to the list
                // of parameters with this name

                if (is_scalar($parsed_parameters[$parameter])) {
                    // This is the first duplicate, so transform scalar (string) into an array
                    // so we can add the duplicates
                    $parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
                }

                $parsed_parameters[$parameter][] = $value;
            } else {
                $parsed_parameters[$parameter] = $value;
            }
        }
        return $parsed_parameters;
    }

    public static
    function build_http_query($params) {
        if (!$params) return '';

        // Urlencode both keys and values
        $keys = OAuthUtil::urlencode_rfc3986(array_keys($params));
        $values = OAuthUtil::urlencode_rfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = array();
        foreach($params as $parameter => $value) {
                if (is_array($value)) {
                    // If two or more parameters share the same name, they are sorted by their value
                    // Ref: Spec: 9.1.1 (1)
                    natsort($value);
                    foreach($value as $duplicate_value) {
                        $pairs[] = $parameter.
                        '='.$duplicate_value;
                    }
                } else {
                    $pairs[] = $parameter.
                    '='.$value;
                }
            }
            //  For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
            // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }
}
