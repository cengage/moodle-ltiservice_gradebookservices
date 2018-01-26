<?php
require_once 'HTTP/Request2.php';

// IMPORTANT:
// THIS IS NOT A MOODLE FILE. This is a php utility that should be launched 
// from the command line to test externally the Gradebookservices API 
// If this is inside your lms code is because the gradebookservices zip file installer 
// Has not been correctly generated, and you can delete this script/test folder.

// Use the help parameter to get the help
// help: If 'help=true' it will display this help.
//
// Needs to be called with this format
// php-cgi -q GradebookServicesTest.php course=2 lineitem=411 userid=3 userid2=4 ltilinkid=123  test=1
// Where this parameters are mandatory: 
// NOTE: If your specific test doesn't use them, just add some number.
//
// test: the number of the test to launch
// course: the id of the course where we are testing. 
// lineitem: The lineitem id (grade_item id) that we will use for the tests
// userid: One student in the course (we need the numeric id)
// userid2: Other student in the course (we need the numeric id)
// ltilinkid: the id or an lti activity related with our proxy. the id's can be seen in mdl_lti table
//
// Some extra parameters are needed in specific tests:
// lineitemnoproxy: Is only needed for test 4, and it refers to a lineitem that doesn't belongs to our proxy
// These one are just needed for test 15 to 22 :   
// lineitemnogbs: A lineitem in our proxy whithout gradebookservice entry
// lineitemnogbsnoproxy: A lineitem NOT in our proxy whithout gradebookservice entry
// lineitemnogbstype0: A lineitem in our proxy whithout gradebookservice entry and with typeid = 0 (because a backup surely)
// lineitemnogbsnoproxytype0 A lineitem NOT in our proxy whithout gradebookservice entry and with typeid = 0 (because a backup surely)
//
// NOTE: As this script is not encoding the queries, if they have a bad symbol they fail. 
// try to not use symbols that are not allowed in URLs.
// If you can view in the results a message about the Hash having a + symbol on it appears, you can retry, most of the times, just 
// a new timestamp will correct it. If it continues during several retries, you can try to change any of the labels
// in the body or one of the numeric values set to other value that avoids the hash to have that +
// 
//

if (isset($_GET['help']))
{
    $help=$_GET['help'];
}else{
    $help="false";
}

if ($help=="true"){

echo "\nUse the help parameter to get the help \n";
echo "help: If 'help=true' it will display this help.\n\n";


echo "Needs to be called with this format: \n\n";
echo "php-cgi -q GradebookServicesTest.php help=false course=2 lineitem=411 userid=3 userid2=4 ltilinkid=123  test=1 \n\n";
echo "Where this parameters are mandatory: \n";
echo "NOTE: If your specific test doesn't use them, just add some number. \n\n";
echo "help: If help is true it will display this help  \n";
echo "test: the number of the test to launch \n";
echo "course: the id of the course where we are testing.  \n";
echo "lineitem: The lineitem id (grade_item id) that we will use for the tests \n";
echo "userid: One student in the course (we need the numeric id) \n";
echo "userid2: Other student in the course (we need the numeric id) \n";
echo "ltilinkid: the id or an lti activity related with our proxy. the id's can be seen in mdl_lti table \n\n";
echo "Some extra parameters are needed in specific tests: \n";
echo "lineitemnoproxy: Is only needed for test 4, and it refers to a lineitem that doesn't belongs to our proxy \n";
echo "These one are just needed for test 15 to 22 :    \n";
echo "lineitemnogbs: A lineitem in our proxy whithout gradebookservice entry \n";
echo "lineitemnogbsnoproxy: A lineitem NOT in our proxy whithout gradebookservice entry \n";
echo "lineitemnogbstype0: A lineitem in our proxy whithout gradebookservice entry and with typeid = 0 (because a backup surely) \n";
echo "lineitemnogbsnoproxytype0 A lineitem NOT in our proxy whithout gradebookservice entry and with typeid = 0 (because a backup surely) \n";
echo " \n";
echo " \n";
echo "NOTE: As this script is not encoding the queries, if they have a bad symbol they fail.\n";
echo "try to not use symbols that are not allowed in URLs.\n";
echo "If you can view in the results a message about the Hash having a + symbol on it appears, you can retry, most of the times, just\n";
echo "a new timestamp will correct it. If it continues during several retries, you can try to change any of the labels\n";
echo "in the body or one of the numeric values set to other value that avoids the hash to have that + symbol \n";
echo " \n";

}else{

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
//1.1 PARAMETERS FROM ARGS
//////////////////

//IDS
$test_number=$_GET['test'];
$course_id = $_GET['course'];
$lineitem_id = $_GET['lineitem'];
$result_id = $_GET['userid'];
$result_id2 = $_GET['userid2'];
$lti_link_id_put=$_GET['ltilinkid'];
$lti_link_id_post=$_GET['ltilinkid'];


/////////////////////////////
// PAGING AND FILTERING AND OTHER QUERY PARAMETERS
/////////////////////////////

//Uncomment the $line_items_query_string, $scores_query_string and or $results_query_string to test with pagination

//LINEITEMS PAGING
// Include leading ? if provided. Can comment out to omit.
// ?limit=5
// ?limit=5&from=2      (from record offset, if provided, limit must be specified)
// ?resource_id=999     (filter for line items associated with specific TP resource ID)
// ?lti_link_id=1  (filter for line items associated with specific TC resource link ID)
//$line_items_query_string='?lti_link_id=6&limit=4&from=2&resource_id=999';
//$line_items_query_string='?limit=1&from=1';

// SCORES PAGING 
// Include leading ? if provided. Can comment out to omit.
// ?limit=5
// ?limit=5&from=2      (from record offset, if provided, limit must be specified)
//$scores_query_string='?limit=2&from=1';


// RESULTS PAGING
// Include leading ? if provided. Can comment out to omit.

//$results_query_string='?from=2';
//$results_query_string='?limit=3&from=2';
//$results_query_string='?limit=3';

// RESULT (RESULTS FILTERED BY USERID)
// Dont include leading ? just &.

//$results_query_string='&from=2';
//$results_query_string='&limit=3&from=2';
//$results_query_string='&limit=3';


//////////////////////////
//TO CHANGE IN THE METHODS
//////////////////////////

//Initialized, Started, InProgress, Submitted, Completed
$score_activity_progress_post = "Completed";

//FullyGraded, Pending, PendingManual, Failed, NotReady
$score_progress_post = "FullyGraded";
$score_progress_post_non_fully_graded = "Pending";


//////////////////
//1.5 CONTENT TYPES
//////////////////

//These doesn't need to change.
$lineitem_content = 'application/vnd.ims.lis.v2.lineitem+json';
$lineitemcontainer_content = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
$scorecontainer_content = 'application/vnd.ims.lis.v1.scorecontainer+json';
$resultcontainer_content = 'application/vnd.ims.lis.v1.resultcontainer+json';

//////////////////
//1.6 HTTP METHODS
//////////////////


$http_method_get = 'GET';
$http_method_post = 'POST';
$http_method_put = 'PUT';
$http_method_delete = 'DELETE';

/////////////////////////
// Other parameteres
/////////////////////////
//LINEITEMS
$item_label_post='New Line Item';
$item_label_post2='Diferent New Line Item 2';
$item_label_put='Old Line Item updated with a new label check';
$line_item_score_maximum_post='10.0';
$line_item_score_maximum_put='50.0';
$resource_id_post='0000000000001';
$resource_id_post2='0111111111111';
$resource_id_put='0000000000002';


//SCORES
$score_score_given_post = '5';
$score_score_maximum_post = '10';
$score_comment_post = "Very Bad job";
$score_timestamp_post=date('c',time());


//////////////////////////
//1.3  CORRECT JSON BODIES 
//////////////////////////

//Lineitems
$postdata_false = null;

$postdata_lineitems_post = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';

$postdata_lineitems_lti_link_id_post = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","ltiLinkId":"'.$lti_link_id_post.'","tag":"lmsint-grade"}';

$postdata_lineitem_put = '{"scoreMaximum":'.
$line_item_score_maximum_put.',"label":"'.$item_label_put.'","resourceId":"'.$resource_id_put.'","tag":"lmsint-grade"}';

$postdata_lineitem_lti_link_id_put = '{"scoreMaximum":'.
        $line_item_score_maximum_put.',"label":"'.$item_label_put.'","resourceId":"'.$resource_id_put.'","ltiLinkId":"'.$lti_link_id_put.'","tag":"lmsint-grade"}';

$postdata_lineitems_delete = null;

//Scores
$postdata_scores_post = '{"scoreGiven":'.$score_score_given_post.',"scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_user2 = '{"scoreGiven":'.$score_score_given_post.',"scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id2.'"}';

$postdata_scores_post_non_fully_graded = '{"scoreGiven":'.$score_score_given_post.',"scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post_non_fully_graded.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';


//results is just a get



//////////////////
//1.4 URLS
//////////////////

//the first number will be the course id. The second the lineitem id that we want to manage.
$url_lineitems = $base_url . $course_id . '/lineitems';
if (!empty($line_items_query_string)) {
  $url_lineitems = $url_lineitems . $line_items_query_string;
}
$url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem';
$url_scores = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem/scores';
if (!empty($scores_query_string)) {
  $url_scores = $url_scores . $scores_query_string;
}
//result is jusr results but filtered by userid
$url_result = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem/results?user_id=' . $result_id;
if (!empty($result_query_string)) {
    $url_result = $url_result . $result_query_string;
}

$url_results = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem/results';
if (!empty($results_query_string)) {
  $url_results = $url_results . $results_query_string;
}


//////////////////
//3. TESTS
//////////////////




if ($test_number==1){
/*
TEST 1: LINEITEMS: ERROR TESTS with GET
TEST #1.1  Get lineitems from an course that doesn’t exists
EXPECTED: 404
TEST #1.2  Pagination: Try to get the lineitems of the course with an unvalid limit = -2
EXPECTED: 400
TEST #1.3  Pagination: Try to get the lineitems of the course with only a from parameter 
EXPECTED: 400
*/
    $url_lineitems_test_bad_course = $base_url . 10000 . '/lineitems';
    if (!empty($line_items_query_string)) {
        $url_lineitems_test_bad_course = $url_lineitems_test_bad_course . $line_items_query_string;
    }
    echo "TEST 1.1: Get lineitems from an course that doesn’t exists \n";
    echo "EXPECTED RESULT: 404 \n";
    call_service('GET LINEITEMS', $url_lineitems_test_bad_course, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 1.2:  Pagination: Try to get the lineitems of the course with paging error value limit=-2 and from=2 \n";
        echo "EXPECTED RESULT:  400 \n";    
    $line_items_query_string='?limit=-2&from=2';
    $url_lineitems311 = $url_lineitems . $line_items_query_string;        
    call_service('GET LINEITEMS', $url_lineitems311, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    

    echo "TEST 1.3:  Pagination: Try to get the lineitems of the course with only from=2 \n";
        echo "EXPECTED RESULT:  400 \n";    
    $line_items_query_string='?from=2';
    $url_lineitems312 = $url_lineitems . $line_items_query_string;        
    call_service('GET LINEITEMS', $url_lineitems312, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);
}

if ($test_number==2){

/*
TEST 2: LINEITEMS: ERROR TESTS with POST
TEST #1  Try to post a lineitem in a course that doesn’t exists
EXPECTED: 404
TEST #2  Try to post a lineitem with each of the mandatory parameters missing
EXPECTED: EXPECTED RESULT: 400, in 'all', 'scoremaximum' and 'lable', and 201 in the others
TEST #3  Try to post a lineitem with the parametes with a wrong value (scoreMaximum,  ltiLinkId)
EXPECTED: 400 and 403
*/

    $url_lineitems_test2 = $base_url . 10000 . '/lineitems';
    if (!empty($line_items_query_string)) {
        $url_lineitems_test2 = $url_lineitems_test2 . $line_items_query_string;
    }
    $postdata_lineitems_post_error_missing = '{}';
    $postdata_lineitems_post_error_missing_scoremaximum = '{"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';
    $postdata_lineitems_post_error_missing_label = '{"scoreMaximum":'.$line_item_score_maximum_post.',"resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';
    $postdata_lineitems_post_error_missing_tag = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'"}';    
    $postdata_lineitems_post_error_empty_tag = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":""}';    
    $postdata_lineitems_post_error_missing_resourceid = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","tag":"lmsint-grade"}';

    $postdata_lineitems_post_error_bad_value = '{"scoreMaximum":"ijijijisjsjjsjdddddi","label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';    
    
    echo "TEST 2.1: Try to post a lineitem in a course that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('POST LINEITEMS', $url_lineitems_test2, $http_method_post, $lineitem_content, $postdata_lineitems_post, $consumer_key, $secret);    
    
        echo "TEST 2.2 Try to post a lineitem with each of the mandatory parameters missing \n";
        echo "EXPECTED RESULT: 400, in 'all', 'scoremaximum' and 'lable', and 201 in the others \n";
    call_service('POST LINEITEMS Missing all', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_missing, $consumer_key, $secret);    
    call_service('POST LINEITEMS Missing scoremaximum', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_missing_scoremaximum, $consumer_key, $secret);    
    call_service('POST LINEITEMS Missing label', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_missing_label, $consumer_key, $secret);        
    call_service('POST LINEITEMS Missing tag', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_missing_tag, $consumer_key, $secret);    
    call_service('POST LINEITEMS Empty tag', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_missing_tag, $consumer_key, $secret);        
    call_service('POST LINEITEMS Missing resourceid', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_missing_resourceid, $consumer_key, $secret);            


    $postdata_lineitems_lti_link_id_post_error = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","ltiLinkId":"1000000","tag":"lmsint-grade"}';

        echo "TEST 2.3: Try to post a lineitem with the parametes with a wrong value (scoreMaximum) \n";
        echo "EXPECTED RESULT: 400 \n";
    call_service('POST LINEITEMS error scoreMaximum ', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post_error_bad_value, $consumer_key, $secret);
    call_service('POST LINEITEMS error ltiLinkId', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_lti_link_id_post_error, $consumer_key, $secret);    


}

if ($test_number==3){

/* TEST 3: LINEITEMS: SUCCESS TESTS with GET/POST
TEST #1  Try to post a lineitem with the right parameters
EXPECTED: 201 return the lineitem added
TEST #2  Try to get the lineitems of the course
EXPECTED: 200 return the lineitems list
TEST #3  Try to post another lineitem with the right parameters
EXPECTED: 201 return the lineitem added
TEST #4  Try to get the lineitems of the course
EXPECTED: 200 return the lineitems list
TEST #5  Try to post another lineitem with linked to an LTI activity
EXPECTED: 201 return the lineitem added
TEST #6  Try to get the lineitems of the course
EXPECTED: 200 return the lineitems list
TEST #7  Try to get the lineitems of the course with paging limit=2
EXPECTED: 200 return the lineitems list
TEST #8  Try to get the lineitems of the course with paging limit=2 and from =2
EXPECTED: 200 return the lineitems list
TEST #9  Try to get the lineitems of the course filtering by resourceid
EXPECTED: 200 return the lineitems list
TEST #10  Try to get the lineitems of the course filtering by lti activity id
EXPECTED: 200 return the lineitems list
TEST #11  Get lineitems from an empty course
EXPECTED: 200, Empty lineitems list
*/

    echo "TEST 3.1: Try to post a lineitem with the right parameters \n";
        echo "EXPECTED RESULT:  201 return the lineitem added \n";
    call_service('POST LINEITEMS', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post, $consumer_key, $secret);    
    
    echo "TEST 3.2: Try to get the lineitems of the course \n";
        echo "EXPECTED RESULT:  200 return the lineitems list with the lineitem added in test 3.1 \n";
    call_service('GET LINEITEMS', $url_lineitems, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 3.3: Try to post another lineitem with the right parameters \n";
        echo "EXPECTED RESULT:  201 return the lineitem added \n";
    $postdata_lineitems_post33 = '{"scoreMaximum":'.$line_item_score_maximum_post.',"label":"'.$item_label_post2.'","resourceId":"'.$resource_id_post2.'","tag":"lmsint-grade"}';
    call_service('POST LINEITEMS', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_post33, $consumer_key, $secret);    

    echo "TEST 3.4: Try to get the lineitems of the course \n";
        echo "EXPECTED RESULT:  200 return the lineitems list with the lineitem added in test 3.1 and 3.2 \n";    
    call_service('GET LINEITEMS', $url_lineitems, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    
    
        echo "TEST 3.5: Try to post another lineitem with linked to an LTI activity, to do this we need the ltilinkid parameter with the id of a valid lti activity in the course \n";
        echo "EXPECTED RESULT:  201 return the lineitem added \n";
        call_service('POST LINEITEMS', $url_lineitems, $http_method_post, $lineitem_content, $postdata_lineitems_lti_link_id_post, $consumer_key, $secret);

    echo "TEST 3.6: Try to get the lineitems of the course \n";
        echo "EXPECTED RESULT:  200 return the lineitems list with the lineitem added in test 3.1 and 3.2 \n";    
    call_service('GET LINEITEMS', $url_lineitems, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);        
    
    echo "TEST 3.7: Try to get the lineitems of the course with paging limit=2 \n";
        echo "EXPECTED RESULT:  200 return the lineitems list with only the first 2 results \n";    
    $line_items_query_string='?limit=2';
    $url_lineitems37 = $url_lineitems . $line_items_query_string;    
    call_service('GET LINEITEMS', $url_lineitems37, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    

    echo "TEST 3.8: Try to get the lineitems of the course with paging limit=2 and from=2 \n";
        echo "EXPECTED RESULT:  200 return the lineitems list with the second and third result \n";    
    $line_items_query_string='?limit=2&from=2';
    $url_lineitems38 = $url_lineitems . $line_items_query_string;        
    call_service('GET LINEITEMS', $url_lineitems38, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    

    echo "TEST 3.9: Try to get the lineitems of the course filtering by resourceid \n";
        echo "EXPECTED RESULT:  200 return the lineitems list with the lineitems where resourceid = '.$resource_id_post2.' \n";    
    $line_items_query_string='?resource_id='.$resource_id_post2;
    $url_lineitems39 = $url_lineitems . $line_items_query_string;    
    call_service('GET LINEITEMS', $url_lineitems39, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    

    echo "TEST 3.10: Try to get the lineitems of the course filtering by lti activity id \n";
    echo "EXPECTED RESULT:  200 return the lineitems list with the lineitems where lti_link_id='.$lti_link_id_post.' \n";    
    $line_items_query_string='?lti_link_id='.$lti_link_id_post;
    $url_lineitems310 = $url_lineitems . $line_items_query_string;    
    call_service('GET LINEITEMS', $url_lineitems310, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    

    echo "TEST 3.11: Get lineitems from an empty course. The course parameter needs to be from a course without lineitems \n";
    echo "EXPECTED RESULT: 200, Empty lineitems list \n";
    call_service('GET LINEITEMS', $url_lineitems, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);    
}

if ($test_number==4){

/*TEST 4 LINEITEM: ERROR TESTS with GET/PUT/DELETE
TEST #1  Try to get a lineitem in a course that doesn’t exists
EXPECTED: 404
TEST #2  Try to get a lineitem that doesn’t exists
EXPECTED: 404
TEST #3  Try to put a lineitem in a course that doesn’t exists
EXPECTED: 404
TEST #4  Try to put a lineitem with each of the mandatory parameters missing
EXPECTED: EXPECTED RESULT:  400 in 'All', 'Scoremaximum', 'Label, and 200 in the others. In the resourcelink it should had removed it from the answer
TEST #5  Try to put a lineitem with each of the parametes with a wrong value (where that is possible)
EXPECTED: 400
TEST #6  Try to delete a lineitem in a course that doesn’t exists
EXPECTED: 404
TEST #7  Try to delete a lineitem in a that doesn’t exists
EXPECTED: 404
TEST #8  Try to get a lineitem in a that doesn’t belongs to our proxy
EXPECTED: 403
TEST #9  Try to put a lineitem in a that doesn’t  belongs to our proxy
EXPECTED: 403
TEST #10  Try to delete a lineitem in a that doesn’t  belongs to our proxy
EXPECTED: 403
*/
    $lineitemnoproxy_id = $_GET['lineitemnoproxy'];    
    $postdata_lineitem_put_error_missing = '{}';    
    $postdata_lineitem_put_error_missing_scoremaximum = '{"label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';    
    $postdata_lineitem_put_error_missing_label = '{"scoreMaximum":"50","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';    
    $postdata_lineitem_put_error_missing_resourceId = '{"scoreMaximum":"50","label":"'.$item_label_post.'","tag":"lmsint-grade"}';    
    $postdata_lineitem_put_error_missing_tag = '{"scoreMaximum":"50","label":"'.$item_label_post.'ppp","resourceId":"'.$resource_id_post.'"}';    
    $postdata_lineitem_put_error_missing_link_set_before = '{"scoreMaximum":"50","label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';    
    $postdata_lineitem_put_error_bad_value = '{"scoreMaximum":"ijijijisjs87878","label":"'.$item_label_post.'","resourceId":"'.$resource_id_post.'","tag":"lmsint-grade"}';    

    echo "TEST 4.1: Try to get a lineitem in a course that doesn’t exists \n";
        echo "EXPECTED RESULT:  404 \n";
    $url_lineitem_bad_course = $base_url . '10000/lineitems/' . $lineitem_id . '/lineitem';
    call_service('GET LINEITEM',$url_lineitem_bad_course, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

        $url_lineitem_bad_lineitem = $base_url . $course_id . '/lineitems/100000/lineitem';
    echo "TEST 4.2: Try to get a lineitem that doesn’t exists \n";
        echo "EXPECTED RESULT:  404 \n";
    call_service('GET LINEITEM',$url_lineitem_bad_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 4.3: Try to put a lineitem in a course that doesn’t exists \n";
        echo "EXPECTED RESULT:  404 \n";
    $url_lineitem_bad_course = $base_url . '100111/lineitems/' . $lineitem_id . '/lineitem';    
    call_service('PUT LINEITEM',$url_lineitem_bad_course,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);

    echo "TEST 4.4: Try to put a lineitem with each of the mandatory parameters missing\n";
        echo "EXPECTED RESULT:  400 in 'All', 'Scoremaximum', 'Label, and 200 in the others. In the resourcelink it should had removed it from the answer ? \n";
    call_service('PUT LINEITEM missing All',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_missing, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
    call_service('PUT LINEITEM missing Scoremaximum',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_missing_scoremaximum, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
    call_service('PUT LINEITEM missing Label',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_missing_label, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
    call_service('PUT LINEITEM missing resourceid',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_missing_resourceId, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
    call_service('PUT LINEITEM missing tag',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_missing_tag, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
    call_service('PUT LINEITEM with ltilinkid',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_lti_link_id_put, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
    call_service('PUT LINEITEM missing ltilinkid',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_missing_link_set_before, $consumer_key, $secret);
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 4.5: Try to put a lineitem with each of the parametes with a wrong value (where that is possible) \n";
        echo "EXPECTED RESULT:  400 \n";
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put_error_bad_value, $consumer_key, $secret);

    echo "TEST 4.6:Try to delete a lineitem in a course that doesn’t exists \n";
        echo "EXPECTED RESULT:  404 \n";
    $url_lineitem_bad_course = $base_url . '10000/lineitems/' . $lineitem_id . '/lineitem';
    call_service('DELETE LINEITEM',$url_lineitem_bad_course,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);

    echo "TEST 4.7: Try to delete a lineitem in that doesn’t exists or is not in the course \n";
        echo "EXPECTED RESULT:  404 \n";
    call_service('DELETE LINEITEM',$url_lineitem_bad_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);

    echo "TEST 4.8: Try to get a lineitem that doesn’t belongs to our proxy  \n";
        echo "EXPECTED RESULT:  403 \n";
    $url_lineitem_no_proxy = $base_url . $course_id . '/lineitems/' . $lineitemnoproxy_id . '/lineitem';
    call_service('GET LINEITEM',$url_lineitem_no_proxy, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 4.9: Try to put a lineitem that doesn’t belongs to our proxy \n";
        echo "EXPECTED RESULT:  403 \n";
        call_service('PUT LINEITEM',$url_lineitem_no_proxy,$http_method_put,$lineitem_content,$postdata_lineitem_lti_link_id_put, $consumer_key, $secret);

    echo "TEST 4.10: Try to delete a lineitem that doesn’t belongs to our proxy \n";
        echo "EXPECTED RESULT:  403 \n";
    call_service('DELETE LINEITEM',$url_lineitem_no_proxy,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);
}

if ($test_number==5){

/*TEST 5 LINEITEM: SUCCESS TESTS with GET/PUT/DELETE
TEST #1  Try to get the lineitem
EXPECTED: 200 return the lineitem
TEST #2  Try to put a lineitem with the right parameters
EXPECTED: 200 return the lineitem modified
TEST #3  Try to get the lineitem modified before
EXPECTED: 200 return the lineitem
TEST #4  Try to delete the lineitem
EXPECTED: 204 
TEST #5  Try to get the lineitem deleted before
EXPECTED: 404
*/

    echo "TEST 5.1: Try to get the lineitem \n";
        echo "EXPECTED RESULT:   200 return the lineitem \n";
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 5.2: Try to put a lineitem with the right parameters \n";
        echo "EXPECTED RESULT:  200 return the lineitem modified  \n";
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);

    echo "TEST 5.3: Try to get a lineitem with the right parameters \n";
        echo "EXPECTED RESULT:  200 return the lineitem  \n";
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 5.4: Try to delete the lineitem \n";
        echo "EXPECTED RESULT:  204 \n";
    call_service('DELETE LINEITEM',$url_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);

    echo "TEST 5.5: Try to get the lineitem deleted before \n";
        echo "EXPECTED RESULT:  404  \n";
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);
}

if ($test_number==6){

/*TEST 6:
SCORES: ERROR TESTS with GET/POST
TEST #1  Try to post a score in a course that doesn’t exists
EXPECTED: 404
TEST #2  Try to post a score in a lineitem that doesn’t exists
EXPECTED: 404
TEST #3  Try to post a score for a user that doesn’t exists or is not enrolled in the course
EXPECTED: 403
TEST #4  Try to post a score with each of the mandatory parameters missing
EXPECTED: 400 when missing 'All', 'userid', 'activity progress' and 'gradingprogress', 201 with score=0 in GivingScore and 201 with scoreMaximum=1 in scoreMaximum, and 201 in comment (but due to moodle code, the comment won't be removed...)  
TEST #5  Try to post a score with each of the parametes with a wrong value (where that is possible)
EXPECTED: 400
TEST #6  Try to get a scores list
EXPECTED: 405
*/


$url_scores_test1 = $base_url . '10000/lineitems/' . $lineitem_id . '/lineitem/scores';
$url_scores_test2 = $base_url . $course_id .'/lineitems/150000/lineitem/scores';

if (!empty($scores_query_string)) {
  $url_scores = $url_scores . $scores_query_string;
}
    $postdata_scores_post_baduser = '{"scoreGiven":'.$score_score_given_post.',"scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"15000"}';
    
    $postdata_score_post_error_missing = '{}';

$postdata_scores_post_error_missing_userId = '{"scoreGiven":"10","scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'"}';

$postdata_scores_post_error_missing_activityProgress = '{"scoreGiven":"10","scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_error_missing_gradingProgress = '{"scoreGiven":"10","scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_error_missing_scoreGiven = '{"scoreMaximum":'.
$score_score_maximum_post.',"comment":"testcomment","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_error_missing_scoreMaximum = '{"scoreGiven":"0.5","comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_error_missing_comment = '{"scoreGiven":"10","scoreMaximum":'.
$score_score_maximum_post.',"activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_error_missing_timestamp = '{"scoreGiven":"10","scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","userId":"'.$result_id.'"}';
    
    
$postdata_scores_post_error_bad_value1 = '{"scoreGiven":"ASDF","scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';

$postdata_scores_post_error_bad_value2 = '{"scoreGiven":"10","scoreMaximum":ASDF,"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"'.$score_timestamp_post.'","userId":"'.$result_id.'"}';


$postdata_scores_post_error_bad_value3 = '{"scoreGiven":"10","scoreMaximum":'.
$score_score_maximum_post.',"comment":"'.$score_comment_post.'","activityProgress":"'.$score_activity_progress_post.'","gradingProgress":"'.$score_progress_post.'","timestamp":"9827948798573945","userId":"'.$result_id.'"}';




    echo "TEST 6.1: Try to post a score in a course that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('POST SCORES', $url_scores_test1, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);    

    echo "TEST 6.2:  Try to post a score in a lineitem that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('POST SCORES', $url_scores_test2, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);    
    
    echo "TEST 6.3: Try to post a score for a user that doesn’t exists or is not enrolled in the course \n";
        echo "EXPECTED RESULT: 403 \n";
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post_baduser, $consumer_key, $secret);    

    echo "TEST 6.4: Try to post a score with each of the mandatory parameters missing \n";
        echo "EXPECTED RESULT: 400 when missing 'All', 'userid', 'activity progress' and 'gradingprogress', 201 with score=0 in GivingScore and 201 with scoreMaximum=1 in scoreMaximum, and 201 in comment (but due to moodle code, the comment won't be removed...)     \n";
    call_service('POST SCORES missing All', $url_scores, $http_method_post, $score_content, $postdata_score_post_error_missing, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing userid', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_userId, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing activityprogress', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_activityProgress, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing gradingprogress', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_gradingProgress, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing GivingScore', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_scoreGiven, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing scoreMaximum', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_scoreMaximum, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing comment', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_comment, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    call_service('POST SCORES missing timestamp', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_missing_timestamp, $consumer_key, $secret);    
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

    
    echo "TEST 6.5: Try to post a score with each of the parametes with a wrong value (GivingScore,scoreMaximum, timestamp) \n";
        echo "EXPECTED RESULT: 400 \n";
    call_service('POST SCORES bad scoregiven ', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_bad_value1, $consumer_key, $secret);    
    call_service('POST SCORES bad scoreMaximum', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_bad_value2, $consumer_key, $secret);    
    call_service('POST SCORES bad date format', $url_scores, $http_method_post, $score_content, $postdata_scores_post_error_bad_value3, $consumer_key, $secret);        

    
    echo "TEST 6.6: Try to get a scores list \n";
        echo "EXPECTED RESULT: 405 \n";
    call_service('GET SCORES', $url_score, $http_method_get, $scorecontainer_content, $postdata_false, $consumer_key, $secret);

}

if ($test_number==7){

/*TEST 7: SCORES: SUCCESS TESTS with POST
TEST #1  Try to post a score with the right parameters and fully graded
EXPECTED: 201 return the result
TEST #2  Try to get the result for the course/lineitem and student
EXPECTED: 200 return the result
TEST #3  Try to post a score with the right parameters and not fully graded
EXPECTED: 201 return the result
TEST #4  Try to get the result for the course/lineitem and student
EXPECTED: 200 return the result
TEST #5  Try to post some other scores with the right parameters and fully graded to be used later
EXPECTED: 201 return the result
*/

    echo "TEST 7.1: Try to post a score with the right parameters and fully graded \n";
        echo "EXPECTED RESULT: 200/201 return the result \n";
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);

    echo "TEST 7.2: Try to get the result for the course/lineitem and student \n";
        echo "EXPECTED RESULT: 200 return the result \n";
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 7.3: Try to post a score with the right parameters and NOT fully graded \n";
        echo "EXPECTED RESULT:  200/201 return the result \n";
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post_non_fully_graded, $consumer_key, $secret);

    echo "TEST 7.4: Try to get the result for the course/lineitem and student \n";
        echo "EXPECTED RESULT: 200 return the result \n";
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 7.5: Try to post some other scores with the right parameters and fully graded to be used later \n";
        echo "EXPECTED RESULT: 200/ 201 return the result \n";
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post_user2, $consumer_key, $secret);
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);

}

if ($test_number==10){

/* TEST 10: RESULTS: ERROR TESTS with GET
TEST #1  Try to get the results in a course that doesn’t exists
EXPECTED: 404
TEST #2  Try to get the results from a lineitem that doesn’t exists
EXPECTED: 404
TEST #3  Try to get the results from a lineitem that has not results yet
EXPECTED: 200, list of empty results
*/


$url_results_test1 = $base_url . '1500000/lineitems/' . $lineitem_id . '/lineitem/results/';
$url_results_test2 = $base_url . $course_id . '/lineitems/150000/lineitem/results/';

    echo "TEST 10.1: Try to get the result in a course that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('GET RESULT', $url_results_test1, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);
    
    echo "TEST 10.2: Try to get the result from a lineitem that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('GET RESULT', $url_results_test1, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);
    
    echo "TEST 10.3: ry to get the results from a lineitem that has not results yet \n";
        echo "EXPECTED RESULT: 200 return the result \n";
    call_service('GET RESULT', $url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);


}

if ($test_number==11){

/* TEST 11: RESULTS: SUCCESS TESTS with GET
TEST #1  Try to get the results in a course and lineitem with results
EXPECTED: 200 return the results list
*/

    echo "TEST 11.1: Try to get the result for the course/lineitem and student \n";
        echo "EXPECTED RESULT: 200 return the result \n";
    call_service('GET RESULTS', $url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);

}

if ($test_number==12){

/*TEST 12: RESULTS filteres by userid (filtered: ERROR TESTS with GET
TEST #1  Try to get the result in a course that doesn’t exists
EXPECTED: 404
TEST #2  Try to get the result from a lineitem that doesn’t exists
EXPECTED: 404
TEST #3  Try to get the result from a user that doesn’t exists
EXPECTED: 404
TEST #4  Try to get the result from a user that has not results yet
EXPECTED: 200, return empty result.
*/

$url_result_test1 = $base_url . '1500000/lineitems/' . $lineitem_id . '/lineitem/results?user_id='. $result_id;
$url_result_test2 = $base_url . $course_id . '/lineitems/150000/lineitem/results?user_id=/'. $result_id .'';
$url_result_test3 = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem/results?user_id=1500000';

$url_score_test4 = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem/scores/'. $result_id2 .'/score';
$url_result_test4 = $base_url . $course_id . '/lineitems/' . $lineitem_id . '/lineitem/results?user_id='. $result_id2;

    echo "TEST 12.1: Try to get the result in a course that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('GET RESULT', $url_result_test1, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    
    echo "TEST 12.2: Try to get the result from a lineitem that doesn’t exists \n";
        echo "EXPECTED RESULT: 404 \n";
    call_service('GET RESULT', $url_result_test2, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    
    echo "TEST 12.3: Try to get the result from a user that doesn’t exists or is not enrolled in the course \n";
        echo "EXPECTED RESULT: 404\n";
    call_service('GET RESULT', $url_result_test3, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 12.4: Try to get the result from a user that has not results yet \n";
        echo "EXPECTED RESULT: 200 return the result \n";
    call_service('DELETE RESULT', $url_score_test4, $http_method_delete, $score_content, $postdata_score_delete, $consumer_key, $secret);
    call_service('GET RESULT', $url_result_test4, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);


}

if ($test_number==13){

/*TEST 13: RESULTS FILTERES BY USER ID: SUCCESS TESTS with GET
TEST #1  Try to get the result in a course and lineitem and user with results
EXPECTED: 200 return the result
*/
    echo "TEST 13.1: Try to get the result for the course/lineitem and student \n";
        echo "EXPECTED RESULT: 200 return the result \n";
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
}


if ($test_number==14){

/*TEST 14: lineitems GET NO GBS TESTS

TEST #1  Try to get lineitems in a course where one is a non-gbs lineitem related with our tool proxy, where one is a non-gbs lineitem non related with our tool proxy
where one is a non-gbs lineitem related with our tool proxy but with type = 0 and where one is a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 200 but the lineitem not related with the tool proxy should not be there
*/

    echo "TEST 14.1: Try to get lineitems in a course where one is a non-gbs lineitem related with our tool proxy, where one is a non-gbs lineitem non related with our tool proxy where one is a non-gbs lineitem related with our tool proxy but with type = 0 and where one is a non-gbs lineitem non related with our tool proxy with type = 0 \n";
        echo "EXPECTED RESULT: 200 but the lineitem not related with the tool proxy should not be there \n";
    $url_lineitems = $base_url . $course_id . '/lineitems';
    call_service('GET LINEITEMS', $url_lineitems, $http_method_get, $lineitemcontainer_content, $postdata_false, $consumer_key, $secret);

}

if ($test_number==15){

/*TEST 15: lineitem GET NO GBS TESTS
TEST #1  Try to get a non-gbs lineitem related with our tool proxy
EXPECTED: 200 return the lineitem
TEST #2  Try to get a non-gbs lineitem non related with our tool proxy
EXPECTED: 403 
TEST #3  Try to get a non-gbs lineitem related with our tool proxy but with type = 0
EXPECTED: 200 return the lineitem
TEST #4  Try to get a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 403
*/
$lineitemnogbs_id = $_GET['lineitemnogbs'];
$lineitemnogbsnoproxy_id = $_GET['lineitemnogbsnoproxy'];
$lineitemnogbstype0_id = $_GET['lineitemnogbstype0'];
$lineitemnogbsnoproxytype0_id = $_GET['lineitemnogbsnoproxytype0'];


    echo "TEST 15.1: Try to get a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT: 200 return the lineitem \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbs_id . '/lineitem';
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 15.2: Try to get a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT: 403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxy_id . '/lineitem';
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 15.3: Try to get a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT: 200 return the lineitem \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbstype0_id . '/lineitem';
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 15.4: Try to get a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT: 403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxytype0_id . '/lineitem';
    call_service('GET LINEITEM',$url_lineitem, $http_method_get, $lineitem_content, $postdata_false, $consumer_key, $secret);
}



if ($test_number==16){
/*TEST 16: lineitem PUT NO GBS TESTS

TEST #1  Try to put a non-gbs lineitem related with our tool proxy
EXPECTED: 200 updated lineitem
TEST #2  Try to put a non-gbs lineitem non related with our tool proxy
EXPECTED: 403 
TEST #3  Try to put a non-gbs lineitem related with our tool proxy but with type = 0
EXPECTED: 200 updated lineitem
TEST #4  Try to put a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 403
*/
$lineitemnogbs_id = $_GET['lineitemnogbs'];
$lineitemnogbsnoproxy_id = $_GET['lineitemnogbsnoproxy'];
$lineitemnogbstype0_id = $_GET['lineitemnogbstype0'];
$lineitemnogbsnoproxytype0_id = $_GET['lineitemnogbsnoproxytype0'];

    echo "TEST 16.1: Try to put a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT: 200 updated lineitem \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbs_id . '/lineitem';
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);
    
    echo "TEST 16.2: Try to put a non-gbs lineitem non related with our tool proxy \n";
    echo "EXPECTED RESULT: 403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxy_id . '/lineitem';
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);

    echo "TEST 16.3: Try to put a non-gbs lineitem related with our tool proxy but with type = 0 \n";
    echo "EXPECTED RESULT: 200 updated lineitem \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbstype0_id . '/lineitem';
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);

    echo "TEST 16.4: Try to put a non-gbs lineitem non related with our tool proxy with type = 0 \n";
    echo "EXPECTED RESULT: 403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxytype0_id . '/lineitem';
    call_service('PUT LINEITEM',$url_lineitem,$http_method_put,$lineitem_content,$postdata_lineitem_put, $consumer_key, $secret);

}


if ($test_number==17){

/*TEST 17: scores POST NO GBS TESTS
TEST #1  Try to post a score in a non-gbs lineitem related with our tool proxy
EXPECTED: 201/200 updated score
TEST #2  Try to post a score in a  a non-gbs lineitem non related with our tool proxy
EXPECTED: 403 
TEST #3  Try to post a score in a  a non-gbs lineitem related with our tool proxy but with type = 0
EXPECTED: 201/200 updated score
TEST #4  Try to post a score in a  a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 403
*/
$lineitemnogbs_id = $_GET['lineitemnogbs'];
$lineitemnogbsnoproxy_id = $_GET['lineitemnogbsnoproxy'];
$lineitemnogbstype0_id = $_GET['lineitemnogbstype0'];
$lineitemnogbsnoproxytype0_id = $_GET['lineitemnogbsnoproxytype0'];

    echo "TEST 17.1: Try to post a score in a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT: 201/200 updated score \n";
    $url_scores = $base_url . $course_id . '/lineitems/' . $lineitemnogbs_id . '/lineitem/scores';
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);

    echo "TEST 17.2: Try to post a score in a  a non-gbs lineitem non related with our tool proxy \n";
    echo "EXPECTED RESULT: 403 \n";
    $url_scores = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxy_id . '/lineitem/scores';
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);

    echo "TEST 17.3: Try to post a score in a  a non-gbs lineitem related with our tool proxy but with type = 0 \n";
    echo "EXPECTED RESULT: 201/200 updated score \n";
    $url_scores = $base_url . $course_id . '/lineitems/' . $lineitemnogbstype0_id . '/lineitem/scores';
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);

    echo "TEST 17.4:  Try to post a score in a  a non-gbs lineitem non related with our tool proxy with type = 0\n";
    echo "EXPECTED RESULT: 403 \n";
    $url_scores = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxytype0_id . '/lineitem/scores';
    call_service('POST SCORES', $url_scores, $http_method_post, $score_content, $postdata_scores_post, $consumer_key, $secret);
}




if ($test_number==19){

/*TEST 19: results GET NO GBS TESTS
TEST #1  Try to get the results from a non-gbs lineitem related with our tool proxy
EXPECTED: 200 get the results
TEST #2  Try to get the results from a non-gbs lineitem non related with our tool proxy
EXPECTED: 403 
TEST #3  Try to get the results from a non-gbs lineitem related with our tool proxy but with type = 0
EXPECTED: 200 get the results
TEST #4  Try to get the results from a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 403
*/
$lineitemnogbs_id = $_GET['lineitemnogbs'];
$lineitemnogbsnoproxy_id = $_GET['lineitemnogbsnoproxy'];
$lineitemnogbstype0_id = $_GET['lineitemnogbstype0'];
$lineitemnogbsnoproxytype0_id = $_GET['lineitemnogbsnoproxytype0'];

    echo "TEST 19.1: Try to get the results from a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT:  200 get the results \n";
    $url_results = $base_url . $course_id . '/lineitems/' . $lineitemnogbs_id . '/lineitem/results';
    call_service('GET RESULTS', $url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 19.2: Try to get the results from a non-gbs lineitem non related with our tool proxy\n";
    echo "EXPECTED RESULT: 403 \n";
    $url_results = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxy_id . '/lineitem/results';
    call_service('GET RESULTS', $url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 19.3: Try to get the results from a non-gbs lineitem related with our tool proxy but with type = 0 \n";
    echo "EXPECTED RESULT: 200 get the results \n";
    $url_results = $base_url . $course_id . '/lineitems/' . $lineitemnogbstype0_id . '/lineitem/results';
    call_service('GET RESULTS', $url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 19.4: Try to get the results from a non-gbs lineitem non related with our tool proxy with type = 0 \n";
    echo "EXPECTED RESULT: 403 \n";
    $url_results = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxytype0_id . '/lineitem/results';
    call_service('GET RESULTS', $url_results, $http_method_get, $resultcontainer_content, $postdata_false, $consumer_key, $secret);
}


if ($test_number==20){

/*TEST 20: result GET NO GBS TESTS
TEST #1  Try to get the result from a non-gbs lineitem related with our tool proxy
EXPECTED: 200 get the result
TEST #2  Try to get the result from a non-gbs lineitem non related with our tool proxy
EXPECTED: 403 
TEST #3  Try to get the result from a non-gbs lineitem related with our tool proxy but with type = 0
EXPECTED: 200 get the result
TEST #4  Try to get the result from a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 403
*/
$lineitemnogbs_id = $_GET['lineitemnogbs'];
$lineitemnogbsnoproxy_id = $_GET['lineitemnogbsnoproxy'];
$lineitemnogbstype0_id = $_GET['lineitemnogbstype0'];
$lineitemnogbsnoproxytype0_id = $_GET['lineitemnogbsnoproxytype0'];

    echo "TEST 20.1: Try to get the result from a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT:  200 get the result \n";
    $url_result = $base_url . $course_id . '/lineitems/' . $lineitemnogbs_id . '/lineitem/results?user_id'. $result_id;
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);
    
    echo "TEST 20.2: Try to get the result from a non-gbs lineitem non related with our tool proxy \n";
    echo "EXPECTED RESULT:  403 \n";
    $url_result = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxy_id . '/lineitem/results?user_id'. $result_id;
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 20.3: Try to get the result from a non-gbs lineitem related with our tool proxy but with type = 0 \n";
    echo "EXPECTED RESULT:  200 get the result \n";
    $url_result = $base_url . $course_id . '/lineitems/' . $lineitemnogbstype0_id . '/lineitem/results?user_id'. $result_id;
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

    echo "TEST 20.4: Try to get the result from a non-gbs lineitem non related with our tool proxy with type = 0 \n";
    echo "EXPECTED RESULT:  403 \n";
    $url_result = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxytype0_id . '/lineitem/results?user_id'. $result_id;
    call_service('GET RESULT', $url_result, $http_method_get, $result_content, $postdata_false, $consumer_key, $secret);

}




if ($test_number==22){

/*TEST 22: lineitem DELETE NO GBS TESTS
TEST #1  Try to delete a non-gbs lineitem related with our tool proxy
EXPECTED: 403 
TEST #2  Try to delete a non-gbs lineitem non related with our tool proxy
EXPECTED: 403 
TEST #3  Try to delete a non-gbs lineitem related with our tool proxy but with type = 0
EXPECTED: 403 
TEST #4  Try to delete a non-gbs lineitem non related with our tool proxy with type = 0
EXPECTED: 403
*/


    echo "TEST 22.1: Try to delete a non-gbs lineitem related with our tool proxy \n";
    echo "EXPECTED RESULT:  403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbs_id . '/lineitem';
    call_service('DELETE LINEITEM',$url_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);

    echo "TEST 22.2: Try to delete a non-gbs lineitem non related with our tool proxy \n";
    echo "EXPECTED RESULT:  403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxy_id . '/lineitem';
    call_service('DELETE LINEITEM',$url_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);

    echo "TEST 22.3: Try to delete a non-gbs lineitem related with our tool proxy but with type = 0 \n";
    echo "EXPECTED RESULT:  403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbstype0_id . '/lineitem';
    call_service('DELETE LINEITEM',$url_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);
    
    echo "TEST 22.4: Try to delete a non-gbs lineitem non related with our tool proxy with type = 0 \n";
    echo "EXPECTED RESULT:  403 \n";
    $url_lineitem = $base_url . $course_id . '/lineitems/' . $lineitemnogbsnoproxytype0_id . '/lineitem';
    call_service('DELETE LINEITEM',$url_lineitem,$http_method_delete,$lineitem_content,$postdata_lineitems_delete, $consumer_key, $secret);
}


}


//////////////////
//4. AUXILIAR FUNCTIONS
//////////////////


function call_service($service, $url, $method, $content_type, $body, $key, $secret) {

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
        
    echo "--------------------------------" . $service . "-----------------------------------\n";
    echo "URL: {$url}";
    echo "\n";
    echo "\n";
    echo "Body: {$body}";
    echo "\n";
    echo "\n";
    echo $response->getBody() . "\n";
    echo "\n";
        echo $response->getStatus();
    echo "\n";
        if ($response->getStatus() > 210) {
            echo $response->getReasonPhrase();
        echo "\n";
    }
    echo "-----------------------------------------------------------------------------------\n";
    echo "\n";

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
    //    print_r($parameters_body);
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
