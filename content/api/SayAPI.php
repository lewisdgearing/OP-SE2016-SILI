<?php
 /**
  * SILI Say API
  *
  * Say API contains functions to mainly the Say Table
  * and/or functions related to Says.
  * 
  * Direct access to this file is not allowed, can only be included
  * in files and the file that is including it must contain 
  *	$internal=true;
  *  
  * @copyright 2016 GLADE
  * @filesource
  * @author Probably Lewis
  *
  */

//Check that only approved methods are trying to access this file (Internal Files/API Controller)
if (!isset($internal) && !isset($controller))
{
	//Trying to direct access
	http_response_code(403);
	exit;
}

include_once("../librarys/Giphy.php");

//EmojiOne Code
require('../librarys/emojione/autoload.php');
$Emojione = new Emojione\Client(new Emojione\Ruleset());
//Set the image type to use
$Emojione->imageType = 'svg'; // or png (default)
$Emojione->ascii = true; // Convert ascii to emojis
//EmojiOne Code End

/**
 *
 * Generate a random sayID
 *
 * Generates a random sayID checking that it does not 
 * already exist in the database
 * 
 * @return   string The sayID for the Say
 *
 */
function GenerateSayID()
{
	global $db;
	$sayID = "";

	//Generate SayID
	do {
	  	$bytes = openssl_random_pseudo_bytes(15, $cstrong);
	   	$hex = bin2hex($bytes);
	   	
		$queryResult = $db->rawQuery("SELECT sayID FROM Says WHERE sayID = ?", Array($hex));
	   	//Check the generated id doesnt already exist
		if (count($queryResult) == 0)
		{
			$sayID = $hex;
		}
	} while ($sayID == "");
	
return $sayID;
}

/**
 *
 * Create record for say
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the say or any errors that have occurred
 *
 */
function SayIt($profileID) //Adds A Say
{
	global $db, $errorCodes, $Emojione;
	
	// Arrays for jsons
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	else 
	{
		if((!isset($_POST['gifBox'])) || (strlen($_POST['gifBox']) == 0))
		{
			// Check if the Say has been submitted and is longer than 0 chars
			if ((!isset($_POST['sayBox'])) || (strlen($_POST['sayBox']) == 0))
			{
				array_push($errors, $errorCodes["S001"]);
			}
		}
		
		if(count($errors) == 0)
		{
			if(isset($_POST['gifBox']))
			{
				$gifID = filter_var($_POST['gifBox'], FILTER_SANITIZE_STRING);
				$sayContent = json_encode(array('giphy' => $gifID));
			}
			else
			{
				$sayContent = htmlspecialchars($Emojione->toShort($_POST['sayBox']));
			}
			
			$sayID = GenerateSayID();

			$data = Array(
				"sayID" => $sayID,
				"profileID" => $profileID,
               	"message" => $sayContent,
               	"timePosted" => date("Y-m-d H:i:s")
			);
			$db->insert("Says", $data);

			$say = FetchSay($sayID);
		}
	}
	
	// If no errors insert Say message into database
	if (count($errors) == 0)
	{
		$result["say"] = $say;
	}
	else //return the json of errors 
	{	
		$result["message"] = "Say failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

/**
 *
 * Return all the says for the current user
 *
 * Returns all the says or resays from users the current user follows along with 
 * the users own says
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the says or any errors that have occurred
 *
 */
function GetSays($profileID) //Returns all the says based of the people listened to by the logged in user
{	
	global $db, $errorCodes, $request;
	
	// Arrays for jsons
	$result = array();
	$errors = array();
	$says = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}

	$timestamp = microtime();
	$offset = 0;

	if (count($request) >= 3)
	{
		if (strlen($request[1]) > 0)
		{
			$offset = filter_var($request[1], FILTER_SANITIZE_NUMBER_INT);	
		}

		if (strlen($request[2]) > 0)
		{
			$timestamp = filter_var($request[2], FILTER_SANITIZE_NUMBER_INT);	
		} 	
	}

	if ($profileID !== 0)
	{
		$offset *= 10;
		$saysQuery = "SELECT sayID FROM Says WHERE deleted = 0 AND timePosted >= ? AND sayID NOT IN (SELECT sayID FROM ReportedSays WHERE reporterProfileID = ?) AND (profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) OR profileID = ? OR sayID IN (SELECT sayID FROM Activity WHERE profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) AND activity = \"Re-Say\")) AND sayID NOT IN (SELECT commentID FROM Comments) ORDER BY timePosted DESC LIMIT ?,10";	
		
		$queryResult = $db->rawQuery($saysQuery, Array($timestamp, $profileID, $profileID, $profileID, $profileID, $offset));
		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) {
				array_push($says, FetchSay($value["sayID"]));
			}
		}	

		//$currentPage = $offset / 10;
		$result["says"] = $says;
		//$result["currentPage"] = $currentPage;
		$result["totalPages"] = CalcuateSaysPages($profileID, $timestamp, "says");
	}
	
	if (count($errors) != 0)
	{
		$result["errors"] = $errors;
		
	}
	
	return $result;
}

/**
 *
 * Returns the total number of says for the given user
 *
 *
 * @param    int  $profileID 
 * @param    int  $timestamp the time we are calcuating says from
 * @param    string $view the type of view (says|profile|comments)
 * @return   int the number of pages there will be
 *
 */
function CalcuateSaysPages($profileID, $timestamp, $view)
{
	global $db;
	$totalSays = 0;

	if ($view == "says")
	{
		$countQuery = "SELECT count(sayID) as totalSays FROM Says WHERE deleted = 0 AND timePosted >= ? AND (profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) OR profileID = ? OR sayID IN (SELECT sayID FROM Activity WHERE profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) AND activity = \"Re-Say\")) AND sayID NOT IN (SELECT commentID FROM Comments)";
		$queryResult = $db->rawQuery($countQuery, Array($timestamp, $profileID, $profileID, $profileID));
	} 
	elseif ($view == "profile")
	{
		$countQuery = "SELECT count(sayID) as totalSays FROM Says WHERE deleted = 0 AND timePosted >= ? AND profileID = ? OR sayID IN (SELECT sayID FROM Activity WHERE profileID = ? AND activity = \"Re-Say\")";
		$queryResult = $db->rawQuery($countQuery, Array($timestamp, $profileID, $profileID));
	}
	elseif ($view == "comments") 
	{
		$countQuery = "";
	}
	else
	{
		return null;
	}


	if (count($queryResult) >= 1)
	{
		$totalSays = $queryResult[0]["totalSays"];
	}

	$nbrPages = floor($totalSays / 10);

	if ($totalSays % 10 > 0)
	{
		$nbrPages += 1;
	}


	return $nbrPages;
}

/**
 *
 * Returns a say
 *
 *
 * @param    int  $profileID of the current logged in user
 * @param    bool $justMe only return the activity by the requestedProfileID
 * @param    string $requestedProfileID profileID of the users whos activity on the say 
 * @return   array Containing the say
 *
 */
function FetchSay($sayID, $justMe = false, $requestedProfileID = 0) //Fetches the Say
{
	global $db, $profileImagePath, $defaultProfileImg, $profileID, $Emojione;
	$say = array();

	$queryResult = $db->rawQuery("SELECT sayID, timePosted, message, profileID FROM Says WHERE sayID = ?", Array($sayID));
		
	if (count($queryResult) == 1)
	{
		$userProfile = GetUserProfile($profileID, $queryResult[0]["profileID"], "firstName, lastName, userName, profileImage, profileLink");

		$say = [
		"sayID" => $queryResult[0]["sayID"],
		"timePosted" => strtotime($queryResult[0]["timePosted"]) * 1000,
		"message" => CheckForGiphy($Emojione->toImage($queryResult[0]["message"])),
		"messageClean" => $queryResult[0]["message"],
		"firstName" => $userProfile["firstName"],
		"lastName" => $userProfile["lastName"],
		"userName" => $userProfile["userName"],
		"profileImage" => $userProfile["profileImage"],
		"profileLink" => $userProfile["profileLink"],
		"boos" => GetActivityCount($queryResult[0]["sayID"], "Boo"),
		"applauds" => GetActivityCount($queryResult[0]["sayID"], "Applaud"),
		"resays" => GetActivityCount($queryResult[0]["sayID"], "Re-Say"),
		"booStatus" => GetActivityStatus($profileID, $queryResult[0]["sayID"], "Boo"),
		"applaudStatus" => GetActivityStatus($profileID, $queryResult[0]["sayID"], "Applaud"),
		"resayStatus" => GetActivityStatus($profileID, $queryResult[0]["sayID"], "Re-Say"),
		"ownSay" => GetOwnSayStatus($queryResult[0]["sayID"], $profileID),
		"activityStatus" => GetActivity($profileID, $queryResult[0]["sayID"], "Re-Say", $justMe, $requestedProfileID),
		];
	}	
	
	return $say;
}

/**
 *
 * Returns the says/resays for current user
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the says or any errors that have occurred
 *
 */
function GetUserSays($profileID) //Get the says of a user
{
	global $db, $errorCodes, $request;
	// Arrays for jsons
	$result = array();
	$errors = array();
	$says = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}

	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	
	$requestedProfileID = 0;
	$timestamp = microtime();
	$offset = 0;

	//Requesting Says of another persons profile
	if (count($request) >= 5)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedUserName = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));	
		} 

		if (strlen($request[3]) > 0)
		{
			$offset = filter_var($request[3], FILTER_SANITIZE_NUMBER_INT);	
		}

		if (strlen($request[4]) > 0)
		{
			$timestamp = filter_var($request[4], FILTER_SANITIZE_NUMBER_INT);	
		} 	
	}
	elseif (count($request) >= 2) //Requesting the current users says
	{
		$requestedProfileID = $profileID;

		if (strlen($request[2]) > 0)
		{
			$offset = filter_var($request[2], FILTER_SANITIZE_NUMBER_INT);	
		}

		if (strlen($request[3]) > 0)
		{
			$timestamp = filter_var($request[3], FILTER_SANITIZE_NUMBER_INT);	
		} 	
	}

	if (isset($requestedUserName) && strlen($requestedUserName) > 0)
	{
		$requestedProfileID = GetProfileID($requestedUserName);
	}
	
	if (!isset($requestedProfileID))
	{
		return null;
	}

	
	if ($requestedProfileID !== 0) 
	{
		$offset *= 10;

		$saysQuery = "SELECT sayID FROM Says WHERE deleted = 0 AND timePosted >= ? AND sayID NOT IN (SELECT sayID FROM ReportedSays WHERE reporterProfileID = ?) AND profileID = ? OR sayID IN (SELECT sayID FROM Activity WHERE profileID = ? AND activity = \"Re-Say\") ORDER BY timePosted DESC LIMIT ?,10";	
		
		$queryResult = $db->rawQuery($saysQuery , Array($timestamp, $profileID, $requestedProfileID, $requestedProfileID, $offset));

		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) 
			{
				$sayID = $value["sayID"];
				array_push($says, FetchSay($sayID, true, $requestedProfileID));
			}
		}	

		$result["says"] = $says;
		$result["totalPages"] = CalcuateSaysPages($requestedProfileID, $timestamp, "profile");
	}

	if (count($errors) != 0)
	{
		$result["errors"] = $errors;
		
	}

	return $result;
}

/**
 *
 * Returns the count of the specified activity 
 *
 * @param    int  $profileID of the current logged in user
 * @param    string $action type of action Boo/Re-Say/Applaud
 * @return   int number of users that have done 
 *
 */
function GetActivityCount($sayID, $action)
{
	global $db;
	$count = 0;
	$queryResult = $db->rawQuery("SELECT COUNT(*) as count FROM Activity WHERE activity = ? AND sayID = ?" , Array($action, $sayID));
	if (count($queryResult) == 1)
	{
		$count = $queryResult[0]["count"];
	}
	return $count;
}

/**
 *
 * Returns if it is the users own say
 *
 * @param    string $sayID the say being checked
 * @param    string $profileID of the current logged in user
 * @return   bool if it is there own say
 *
 */
function GetOwnSayStatus($sayID, $profileID)
{
	global $db;
	$status = false;

	$queryResult = $db->rawQuery("SELECT profileID FROM Says WHERE sayID = ?" , Array($sayID));
	if (count($queryResult) == 1)
	{
		$postProfileID = $queryResult[0]["profileID"];

		if ($profileID == $postProfileID)
		{
			$status = true;
		}
	}
	
	return $status;
}

/**
 *
 * Returns if it is the users has done the activity
 *
 * @param    string $profileID of the current logged in user
 * @param    string $sayID the say being checked
 * @param    string $action type of action Boo/Re-Say/Applaud
 * @return   bool the status of the action
 *
 */
function GetActivityStatus($profileID, $sayID, $action)
{
	global $db;
	$status = false;

	$queryResult = $db->rawQuery("SELECT COUNT(*) as count FROM Activity WHERE activity = ? AND sayID = ? AND profileID = ?" , Array($action, $sayID, $profileID));
	if (count($queryResult) == 1)
	{
		$count = $queryResult[0]["count"];
	
		if ($count == 1)
		{
			$status = true;
		}
		
	}
	
	return $status;
}

/**
 *
 * Returns the activity details if any for the requestd say
 *
 * If just me is set then only return activity for the given requested profileID, otherwise 
 * one will randomly be chosen from the users following
 *
 * @param    string $profileID of the current logged in user
 * @param    string $sayID the say being checked
 * @param    string $action type of action Boo/Re-Say/Applaud
 * @param    bool $justMe only return the activity by the requestedProfileID
 * @param    string $requestedProfileID profileID of the users whos activity on the say 
 * @return   array details of the person if any who did the activity
 *
 */
function GetActivity($profileID, $sayID, $action, $justMe = false, $requestedProfileID = 0)
{
	global $db, $profileImagePath, $defaultProfileImg;
	$activity = false;
	if ($justMe)
	{
		$activityQuery = "SELECT profileID FROM Activity WHERE profileID = ? AND activity = ? AND sayID = ?";	
	}
	else
	{
		$activityQuery = "SELECT profileID FROM Activity WHERE profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) AND activity = ? AND sayID = ?";
		$requestedProfileID = $profileID;
	}

	$queryResult = $db->rawQuery($activityQuery , Array($requestedProfileID, $action, $sayID));
	if (count($queryResult) >= 1)
	{	
		$activity = GetUserProfile($profileID, $queryResult[0]["profileID"], "firstName, lastName, userName, profileImage, profileLink");
	}
	
	
	return $activity;
}

/**
 *
 * Create record for comment
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the comment or any errors that have occurred
 *
 */
function CommentSayIt($profileID)
{
	global $db, $errorCodes, $request;
	// Arrays for jsons
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	else {
		// Check if the Say has been submitted and is longer than 0 chars
		if ((!isset($_POST['commentBox'])) || (strlen($_POST['commentBox']) == 0))
		{
			array_push($errors, $errorCodes["S002"]);
		}
		else
		{
			$sayContent = htmlspecialchars($_POST['commentBox']);
			$commentID = GenerateSayID();

			//Add the comment
			$data = Array(
							"sayID" => $commentID, //This Say is a comment so therefore this is the comment ID
							"profileID" => $profileID,
			               	"message" => $sayContent,
			               	"timePosted" => date("Y-m-d H:i:s")
						);

			$db->insert("Says", $data);

			//Create a link to the orginal say and the comment that was just added
			$data = Array(
							"sayID" => $sayID, // THIS is posted with the form and dealt with higher up
							"commentID" => $commentID
						);

			$db->insert("Comments", $data);

			$say = FetchSay($commentID);
		}
	}
	
	// If no errors insert Comment message into database
	if (count($errors) == 0)
	{
		$result["comment"] = $say;
	}
	else //return the json of errors 
	{	
		$result["message"] = "Comment failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

/**
 *
 * Returns an individual say
 *
 * Based on the say requested return that say
 *
 * @param    string $profileID of the current logged in user
 * @return   array say details or any errors that occour
 *
 */
function GetSay($profileID)
{
	global $db, $errorCodes, $request;
	
	// Arrays for jsons
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	else 
	{
		
		$saysQuery = "SELECT sayID FROM Says WHERE sayID = ?";	
		
		$queryResult = $db->rawQuery($saysQuery , Array($sayID));
		if (count($queryResult) == 1)
		{	
			$sayID = $queryResult[0]["sayID"];
			$say = FetchSay($sayID);
		}	
		$result["say"] = $say;
	}
	
	if (count($errors) != 0)
	{	
		$result["message"] = "Say Fetch failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

/**
 *
 * Returns comments associated to a particular say
 *
 * @param    string $profileID of the current logged in user
 * @return   array comment details or any errors that occour
 *
 */
function GetComments($profileID)
{
	global $db, $errorCodes, $request;
	// Arrays for jsons
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	$comments = array();
	
	if ($profileID !== 0 && isset($sayID))
	{
		$commentsQuery = "SELECT sayID FROM Says WHERE sayID IN (SELECT commentID FROM Comments WHERE sayID = ?) ORDER BY timePosted DESC LIMIT 10";

		$queryResult = $db->rawQuery($commentsQuery, Array($sayID));
		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) {
				$commentID = $value["sayID"];
				array_push($comments, FetchSay($commentID));
			}
		}	
		$result["comments"] = $comments;
	}

	if (count($errors) != 0)
	{	
		$result["message"] = "Say Comments Fetch failed";	
		$result["errors"] = $errors;
	}
	return $result;
}

/**
 *
 * Perform an action
 *
 * Adds/Removes the action
 *
 * @param    string $profileID of the current logged in user
 * @param    string $action the action being performed (Applaud/Re-Say/Boo)
 * @return   array Result of the action
 *
 */
function SayActivity($profileID, $action)
{
	global $db, $errorCodes, $request;
	
	$result = array();
	$errors = array();
	
	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
		
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
		if ($action == "Re-Say" && GetOwnSayStatus($sayID, $profileID))
		{
			array_push($errors, $errorCodes["S003"]);	
		}
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}


	$status = "";
	$reverseAction = "";
	
	if ($action == "Boo") 
	{
		$reverseAction = "Applaud";
	}
	elseif ($action == "Applaud")
	 {
		$reverseAction = "Boo";
	 }

	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		if ($action == "Re-Say" || !GetActivityStatus($profileID, $sayID, $reverseAction)) 
		{
			if (!GetActivityStatus($profileID, $sayID, $action))
			{
				$data = Array(
					"profileID" => $profileID,
	               	"sayID" => $sayID,
	               	"activity" => $action,
	               	"timeOfAction" => date("Y-m-d H:i:s")
				);
				$db->insert("Activity", $data);
					
				$status = true;
			}
			else
			{

				$db->where("profileID", $profileID);
				$db->where("sayID", $sayID);
				$db->where("activity", $action);
				$db->delete("Activity");
				
				$status = false;
			} 
		}
		else
		{
			array_push($errors, $errorCodes["G000"]);		
		}
	}
	
	if (count($errors) == 0)
	{
		$result["status"] = $status;
		$result["applauds"] = GetActivityCount($sayID, "Applaud");
		$result["boos"] = GetActivityCount($sayID, "Boo");
		$result["resays"] = GetActivityCount($sayID, "Re-Say");
	}
	else
	{
		$result["errors"] = $errors;
	}
	
	return $result;
}


/**
 *
 * Returns the people who performed the action
 * 
 * Returns an array of users who performed the action to the sayID that was provided
 *
 * @param    int $profileID of the current logged in user
 * @param    string $action the action being returned (Applaud/Re-Say/Boo)
 * @return   arrray of users who did the action
 *
 */
function GetActivityUsers($profileID, $action)
{
	global $db, $errorCodes, $request, $profileImagePath, $defaultProfileImg;
	
	$result = array();
	$errors = array();
	$users = array();

	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
		
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{			
		$queryResult = $db->rawQuery("SELECT profileID FROM Activity WHERE sayID = ? AND activity = ? LIMIT 10", Array($sayID, $action));
		if (count($queryResult) > 0)
		{
			foreach ($queryResult as $user) 
			{
				$user = GetUserProfile($profileID, $queryResult[0]["profileID"], "firstName, lastName, userName, profileImage, profileLink");
				array_push($users, $user);
			}				
 
		}
		$result["users"] = $users;
	}

	if (count($errors) != 0)
	{
		$result["errors"] = $errors;
	}
	
	return $result;
}

/**
 *
 * Preform Boo Action
 *
 * @param    int $profileID of the current logged in user
 *
 */
function Boo($profileID)
{
	return SayActivity($profileID, "Boo");	
}

/**
 *
 * Preform Applaud Action
 *
 * @param    int $profileID of the current logged in user
 *
 */
function Applaud($profileID)
{
	return SayActivity($profileID, "Applaud");	
}

/**
 *
 * Preform Resay Action
 *
 * @param    int $profileID of the current logged in user
 *
 */
function ReSay($profileID)
{
	return SayActivity($profileID, "Re-Say");	
}

/**
 *
 * Return the users who Applaud a Say
 *
 * @param    int $profileID of the current logged in user
 *
 */
function ApplaudUsers($profileID)
{
	return GetActivityUsers($profileID, "Applaud");	
}

/**
 *
 * Return the users who Boo a Say
 *
 * @param    int $profileID of the current logged in user
 *
 */
function BooUsers($profileID)
{
	return GetActivityUsers($profileID, "Boo");	
}

/**
 *
 * Return the users who ReSay a Say
 *
 * @param    int $profileID of the current logged in user
 *
 */
function ResayUsers($profileID)
{
	return GetActivityUsers($profileID, "Re-Say");	
}

/**
 *
 * Deletes a Say
 *
 * @param    string $profileID of the current logged in user
 * @return   array Result of the action
 *
 */
function DeleteSay($profileID)
{
	global $db, $errorCodes, $request;
	
	$result = array();
	$errors = array();
	
	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
		
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}

	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		if (GetOwnSayStatus($sayID, $profileID))
		{
			$data = Array(
			    "deleted" => true,
				"deletedDate" => date("Y-m-d H:i:s")
			);
			$db->where("sayID", $sayID);
			$db->update("Says", $data);
		}
		else
		{
			array_push($errors, $errorCodes["G000"]);
		}

	}
	
	if (count($errors) != 0)
	{
		$result["errors"] = $errors;
	}
	
	return $result;
	
}

/**
 *
 * Reports a Say
 *
 * @param    string $profileID of the current logged in user
 * @return   array Result of the action
 *
 */
function ReportSay($profileID)
{
	global $db, $errorCodes, $request;
	
	$result = array();
	$errors = array();
	
	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
		
	if (count($request) >= 3)
	{
		$sayID = filter_var($request[2], FILTER_SANITIZE_STRING);
	}
	else
	{
		array_push($errors, $errorCodes["S000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}

	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		if (!GetOwnSayStatus($sayID, $profileID))
		{
			$data = Array(
			    "sayID" => $sayID,
			    "reporterProfileID" => $profileID,
				"reportedDate" => date("Y-m-d H:i:s")
			);
			$db->insert("ReportedSays", $data);
		}
		else
		{
			array_push($errors, $errorCodes["G000"]);
		}

		//Get the Details of the say
		$say = FetchSay($sayID);

		$sayMessage = $say["messageClean"];
		$posterUserName = $say["userName"];
		$reporterUserName = GetUserName($profileID);

		SlackBot_ReportSay($sayID, $sayMessage, $posterUserName, $reporterUserName);
	}
	
	if (count($errors) != 0)
	{
		$result["errors"] = $errors;
	}
	
	return $result;
	
}
?>