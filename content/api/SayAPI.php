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
  *
  * @author Probably Lewis
  *
  */

if (!isset($internal) && !isset($controller)) //check if its not an internal or controller request
{
	//Trying to direct access
	http_response_code(403);
	exit;
}

function SayIt($profileID) //Adds A Say
{
	global $db, $errorCodes;
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
		// Check if the Say has been submitted and is longer than 0 chars
		if ((!isset($_POST['sayBox'])) || (strlen($_POST['sayBox']) == 0))
		{
			array_push($errors, $errorCodes["S003"]);
		}
		else
		{
			$sayContent = htmlspecialchars($_POST['sayBox']);

			$data = Array(
				"profileID" => $profileID,
               	"message" => $sayContent,
               	"timePosted" => date("Y-m-d H:i:s")
			);
			$sayID = $db->insert("Says", $data);

			$say = FetchSay($sayID);
		}
	}
	
	// If no errors insert Say message into database
	if (count($errors) == 0)
	{
		$result["message"] = "Say has been added";
		$result["say"] = $say;
		
	}
	else //return the json of errors 
	{	
		$result["message"] = "Say failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

function GetSays($profileID) //Returns all the says based of the people listened to by the logged in user
{	
	global $db, $errorCodes;
	// Arrays for jsons
	$result = array();
	$says = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if ($profileID !== 0) 
	{
		$saysQuery = "SELECT sayID FROM Says WHERE (profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) OR profileID = ? OR sayID IN (SELECT sayID FROM Activity WHERE profileID IN (SELECT listenerProfileID FROM Listeners WHERE profileID = ?) AND activity = \"Re-Say\")) AND sayID NOT IN (SELECT commentID FROM Comments) ORDER BY timePosted DESC";	
		
		$queryResult = $db->rawQuery($saysQuery, Array($profileID, $profileID, $profileID));
		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) {
				$sayID = $value["sayID"];
				array_push($says, FetchSay($sayID));
			}
		}	

		$result["says"] = $says;
	}
	
	return $result;
}

function FetchSay($sayID, $justMe = false, $requestedUserID = 0) //Fetches the Say
{
	global $db, $profileImagePath, $defaultProfileImg, $profileID;
	$say = array();

	$queryResult = $db->rawQuery("SELECT LPAD(sayID, 10, '0') as sayIDFill, timePosted, message, profileImage, firstName, lastName, userName, Says.profileID as postProfileID FROM Says INNER JOIN Profile ON Says.profileID=Profile.profileID WHERE sayID = ?", Array($sayID));
		
	if (count($queryResult) == 1)
	{

		$sayIDFill = $queryResult[0]["sayIDFill"];
		$timePosted = $queryResult[0]["timePosted"];
		$message = $queryResult[0]["message"];
		$profileImage = $queryResult[0]["profileImage"];
		$firstName = $queryResult[0]["firstName"];
		$lastName = $queryResult[0]["lastName"];
		$userName = $queryResult[0]["userName"];
		$postUserID = $queryResult[0]["postProfileID"];
						
		if ($profileImage == "")
		{
			$profileImage = $defaultProfileImg;
		}
		
		$ownSay = GetOwnSayStatus($sayID, $profileID);

		$say = [
		"sayID" => str_replace("=", "", base64_encode($sayIDFill)),
		"timePosted" => strtotime($timePosted) * 1000,
		"message" => $message,
		"profileImage" => $profileImagePath . $profileImage,
		"profileLink" => "profile/" . $userName,
		"firstName" => $firstName,
		"lastName" => $lastName,
		"userName" => $userName,
		"boos" => GetActivityCount($sayID, "Boo"),
		"applauds" => GetActivityCount($sayID, "Applaud"),
		"resays" => GetActivityCount($sayID, "Re-Say"),
		"booStatus" => GetActivityStatus($profileID, $sayID, "Boo"),
		"applaudStatus" => GetActivityStatus($profileID, $sayID, "Applaud"),
		"resayStatus" => GetActivityStatus($profileID, $sayID, "Re-Say"),
		"ownSay" => $ownSay,
		"activityStatus" => GetActivity($profileID, $sayID, "Re-Say", $justMe, $requestedUserID),
		];
	}	
	
	return $say;
}

function GetUserSays($profileID) //Get the says of a user
{
	global $db, $errorCodes, $request;
	// Arrays for jsons
	$result = array();
	$says = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}

	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G001"]);
	}
	
	$requestedUserID = 0;

	if (count($request) >= 3)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedUserName = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));	
		} 
		else
		{
			$requestedProfileID = $profileID;
		}
	}

	if (isset($requestedUserName) && strlen($requestedUserName) > 0)
	{
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE userName = ?", Array($requestedUserName));

		if (count($queryResult) == 1)
		{
			$requestedProfileID = $queryResult[0]["profileID"];
		}
	}
	
	if (!isset($requestedUserID))
	{
		return null;
	}

	
	if ($requestedProfileID !== 0) 
	{
		$saysQuery = "SELECT sayID FROM Says WHERE profileID = ? OR sayID IN (SELECT sayID FROM Activity WHERE profileID = ? AND activity = \"Re-Say\") ORDER BY timePosted DESC LIMIT 10";	
		
		$queryResult = $db->rawQuery($saysQuery , Array($requestedProfileID, $requestedProfileID));

		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) 
			{
				$sayID = $value["sayID"];
				array_push($says, FetchSay($sayID, true, $requestedProfileID));
			}
		}	
	}
		
	$result["says"] = $says;
	
	return $result;
}

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
	
		$activityUserID = $queryResult[0]["userID"];
	
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE profileID = ?" , Array($activityProfileID));
	
		if (count($queryResult) == 1)
		{
			$firstName = $queryResult[0]["firstName"];
			$lastName = $queryResult[0]["lastName"];
			$userName = $queryResult[0]["userName"];
			$profileImage = $queryResult[0]["profileImage"];
					
			if ($profileImage == "")
			{
				$profileImage = $defaultProfileImg;
			}
			
			$activity = [
				"profileImage" => $profileImagePath . $profileImage,
				"firstName" => $firstName,
				"lastName" => $lastName,
				"userName" => $userName,
				"profileLink" => "profile/" . $userName,
			];
		}
	}
	
	
	return $activity;
}

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
		$sayID = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
	}
	else
	{
		array_push($errors, $errorCodes["Co04"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["Co02"]);
	}
	else {
		// Check if the Say has been submitted and is longer than 0 chars
		if ((!isset($_POST['commentBox'])) || (strlen($_POST['commentBox']) == 0))
		{
			array_push($errors, $errorCodes["Co03"]);
		}
		else
		{
			$sayContent = htmlspecialchars($_POST['commentBox']);
			
			$data = Array(
							"profileID" => $profileID,
			               	"message" => $sayContent
						);

			$commentID = $db->insert("Says", $data); //This Say is a comment so therefore this is the comment ID
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
		$result["message"] = "Comment has been added";
		$result["comment"] = $say;
		
	}
	else //return the json of errors 
	{	
		$result["message"] = "Comment failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

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
		$sayID = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
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

	}
	
	// If no errors insert Say message into database
	if (count($errors) == 0)
	{
		$result["say"] = $say;
		
	}
	else //return the json of errors 
	{	
		$result["message"] = "Say Fetch failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

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
		$sayID = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
	}
	else
	{
		array_push($errors, $errorCodes["Co04"]);
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
	return $result;
}

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
		$sayID = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
		if ($action == "Re-Say" && GetOwnSayStatus($sayID, $profileID))
		{
			array_push($errors, $errorCodes["G000"]);	
		}
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G001"]);
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
		$result["message"] = "Action Completed";
		$result["status"] = $status;
		$result["count"] = GetActivityCount($sayID, $action);
	}
	else
	{
		$result["errors"] = $errors;
	}
	
	return $result;
	
}

function Boo($profileID)
{
	return SayActivity($profileID, "Boo");	
}

function Applaud($profileID)
{
	return SayActivity($profileID, "Applaud");	
}

function ReSay($profileID)
{
	return SayActivity($profileID, "Re-Say");	
}
?>