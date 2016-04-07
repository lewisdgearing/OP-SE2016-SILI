<?php

if (!isset($internal) && !isset($controller)) //check if its not an internal or controller request
{
	//Trying to direct access
	http_response_code(403);
	exit;
}

function SayIt($userID) //Adds A Say
{
	global $db, $errorCodes;
	// Arrays for jsons
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if ($userID == 0)
	{
		array_push($errors, $errorCodes["S002"]);
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
				"userID" => $userID,
               	"message" => $sayContent
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

function GetSays($userID) //Returns all the says based of the people listened to by the logged in user
{	
	global $db, $errorCodes;
	// Arrays for jsons
	$result = array();
	$says = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if ($userID != 0) 
	{
		$saysQuery = "SELECT sayID FROM Says WHERE (userID IN (SELECT listenerUserID FROM Listeners WHERE userID = ?) OR userID = ? OR sayID IN (SELECT sayID FROM Activity WHERE userID IN (SELECT listenerUserID FROM Listeners WHERE userID = ?) AND activity = \"Re-Say\")) AND sayID NOT IN (SELECT commentID FROM Comments) ORDER BY timePosted DESC";	
		
		$queryResult = $db->rawQuery($saysQuery, Array($userID, $userID, $userID));
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
	global $db, $profileImagePath, $defaultProfileImg, $userID;
	$say = array();

	$queryResult = $db->rawQuery("SELECT LPAD(sayID, 10, '0') as sayIDFill, timePosted, message, profileImage, firstName, lastName, userName, Says.userID as postUserID FROM Says INNER JOIN Profile ON Says.userID=Profile.userID WHERE sayID = ?", Array($sayID));
		
	if (count($queryResult) == 1)
	{

		$sayIDFill = $queryResult[0]["sayIDFill"];
		$timePosted = $queryResult[0]["timePosted"];
		$message = $queryResult[0]["message"];
		$profileImage = $queryResult[0]["profileImage"];
		$firstName = $queryResult[0]["firstName"];
		$lastName = $queryResult[0]["lastName"];
		$userName = $queryResult[0]["userName"];
		$postUserID = $queryResult[0]["postUserID"];
						
		if ($profileImage == "")
		{
			$profileImage = $defaultProfileImg;
		}
		
		$ownSay = GetOwnSayStatus($sayID, $userID);

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
		"booStatus" => GetActivityStatus($userID, $sayID, "Boo"),
		"applaudStatus" => GetActivityStatus($userID, $sayID, "Applaud"),
		"resayStatus" => GetActivityStatus($userID, $sayID, "Re-Say"),
		"ownSay" => $ownSay,
		"activityStatus" => GetActivity($userID, $sayID, "Re-Say", $justMe, $requestedUserID),
		];
	}	
	
	return $say;
}

function GetUserSays($userID) //Get the says of a user
{
	global $db, $errorCodes, $request;
	// Arrays for jsons
	$result = array();
	$says = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}

	if ($userID == 0)
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
			$requestedUserID = $userID;
		}
	}

	if (isset($requestedUserName) && strlen($requestedUserName) > 0)
	{
		$queryResult = $db->rawQuery("SELECT userID FROM Profile WHERE userName = ?", Array($requestedUserName));

		if (count($queryResult) == 1)
		{
			$requestedUserID = $queryResult[0]["userID"];
		}
	}
	
	if (!isset($requestedUserID))
	{
		return null;
	}

	
	if ($requestedUserID != 0) 
	{
		$saysQuery = "SELECT sayID FROM Says WHERE userID = ?  OR sayID IN (SELECT sayID FROM Activity WHERE userID = ? AND activity = \"Re-Say\") ORDER BY timePosted DESC LIMIT 10";	
		
		$queryResult = $db->rawQuery($saysQuery , Array($requestedUserID, $requestedUserID));

		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) 
			{
				$sayID = $value["sayID"];
				array_push($says, FetchSay($sayID, true, $requestedUserID));
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

function GetOwnSayStatus($sayID, $userID)
{
	global $db;
	$status = false;

	$queryResult = $db->rawQuery("SELECT userID FROM Says WHERE sayID = ?" , Array($sayID));
	if (count($queryResult) == 1)
	{
		$postUserID = $queryResult[0]["userID"];

		if ($userID == $postUserID)
		{
			$status = true;
		}
	}
	
	return $status;
}
function GetActivityStatus($userID, $sayID, $action)
{
	global $db;
	$status = false;

	$queryResult = $db->rawQuery("SELECT COUNT(*) as count FROM Activity WHERE activity = ? AND sayID = ? AND userID = ?" , Array($action, $sayID, $userID));
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

function GetActivity($userID, $sayID, $action, $justMe = false, $requestedUserID = 0)
{
	global $db, $profileImagePath, $defaultProfileImg;
	$activity = false;
	if ($justMe)
	{
		$activityQuery = "SELECT userID FROM Activity WHERE userID = ? AND activity = ? AND sayID = ?";	
	}
	else
	{
		$activityQuery = "SELECT userID FROM Activity WHERE userID IN (SELECT listenerUserID FROM Listeners WHERE userID = ?) AND activity = ? AND sayID = ?";
		$requestedUserID = $userID;
	}

	$queryResult = $db->rawQuery($activityQuery , Array($requestedUserID, $action, $sayID));
	if (count($queryResult) >= 1)
	{
	
		$activityUserID = $queryResult[0]["userID"];
	
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE userID = ?" , Array($activityUserID));
	
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

function CommentSayIt($userID)
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
	
	if ($userID == 0)
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
							"userID" => $userID,
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

function GetSay($userID)
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
	
	if ($userID == 0)
	{
		array_push($errors, $errorCodes["G001"]);
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

function GetComments($userID)
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
	
	if ($userID != 0 && isset($sayID))
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

function SayActivity($userID, $action)
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
		if ($action == "Re-Say" && GetOwnSayStatus($sayID, $userID))
		{
			array_push($errors, $errorCodes["G000"]);	
		}
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
	}
	
	if ($userID == 0)
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
		if ($action == "Re-Say" || !GetActivityStatus($userID, $sayID, $reverseAction)) 
		{
			if (!GetActivityStatus($userID, $sayID, $action))
			{
				$data = Array(
					"userID" => $userID,
	               	"sayID" => $sayID,
	               	"activity" => $action
				);
				$db->insert("Activity", $data);
					
				$status = true;
			}
			else
			{

				$db->where("userID", $userID);
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

function Boo($userID)
{
	return SayActivity($userID, "Boo");	
}

function Applaud($userID)
{
	return SayActivity($userID, "Applaud");	
}

function ReSay($userID)
{
	return SayActivity($userID, "Re-Say");	
}
?>