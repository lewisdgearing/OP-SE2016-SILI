<?php
$status = session_status();
if($status == PHP_SESSION_NONE){
    //There is no active session
    session_start();
}

$controller = true;

//Include all the API file
require_once ("../librarys/MysqliDb.php");
include("../config/dbconnect.inc.php");
include("../config/errorHandling.php");
include("../config/APIrequests.php");
include("../config/config.inc.php");
include("../api/UserAPI.php");
include("../api/SayAPI.php");
include("../api/ProfileAPI.php");
include("../api/MessageAPI.php");


//Check if the request is coming from one of the scripts
if (is_ajax())
{

	//Get UserID from session
	$userID = 0;
	$profileID = 0;
	if(isset($_SESSION['userID']))
	{
		$userID = $_SESSION['userID'];
		$profileID = GetUserProfileID($userID);
	}

	//Check if a request for an API was actually made
	if(isset($_GET['request']))
	{	
		$request = explode("/", filter_var($_GET['request'], FILTER_SANITIZE_STRING));

		//Check if the request is a vaild request
		if (array_key_exists($request[0], $reqArray) && count($request) > 1) {
			$requestedAPI = $request[0];
			$requestedFunction = $request[1];
			if($requestedFunction == "" || is_numeric($requestedFunction))
			{
				$requestedFunction = 0;
			}
			if (array_key_exists($requestedFunction, $reqArray[$requestedAPI])) 
			{
				if (array_key_exists($_SERVER['REQUEST_METHOD'], $reqArray[$requestedAPI][$requestedFunction])) 
				{
					$result = $reqArray[$requestedAPI][$requestedFunction][$_SERVER['REQUEST_METHOD']]($profileID, $userID);
					
					//Output Request json result
					header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
					header("Cache-Control: post-check=0, pre-check=0", false);
					header("Pragma: no-cache");
					header('Content-Type: application/json');
					if(array_key_exists('errors', $result))
					{
						http_response_code(400);
					} 
					else
					{
						http_response_code(200);
					}
					echo json_encode($result);	
					exit;
				}
			}
		}
	} 
	
	//No Request provided or Not Found
	http_response_code(404);
	include("../../404.html");
	exit;
} 
else //Not json Forbiden
{
	http_response_code(403);
	include("../../403.html");
	exit;
}

// Function to check if the request is an ajax request
function is_ajax()
{
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
?>