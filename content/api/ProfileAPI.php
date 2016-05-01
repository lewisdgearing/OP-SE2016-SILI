<?php
 /**
  * SILI Profile API
  *
  * Profile API contains functions to mainly the Profile Table
  * and/or functions related to profiles.
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

/**
 *
 * Generate a random profileID
 *
 * Generates a random profileID checking that it does not 
 * already exist in the database
 * 
 * @return   string The profileID of the user
 *
 */
function GenerateProfileID() //Generate a Unique ProfileID
{
	global $db;
	$profileID = "";

	//Generate ProfileID
	do {
	  	$bytes = openssl_random_pseudo_bytes(10, $cstrong);
	   	$hex = bin2hex($bytes);
	   	
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE profileID = ?", Array($hex));
	   	//Check the generated id doesnt already exist
		if (count($queryResult) == 0)
		{
			$profileID = $hex;
		}
	} while ($profileID == "");
	
return $profileID;
}

/**
 *
 * Create profile Record for suer
 *
 * @param    int  $userID of the new user 
 * @param 	 string $firstName The users first name
 * @param 	 string $lastName The users last name
 * @param 	 string $userName The users chosen userName
 *
 */
function CreateProfile($userID, $firstName, $lastName, $userName)
{
	global $db;
	//Generate ProfileID
	$profileID = GenerateProfileID();

	//add user to profile table
	$data = Array(
			"profileID" => $profileID,
            "userID" => $userID,
            "firstName" => $firstName,
			"lastName" => $lastName,
			"userName" => $userName,
			"joinDate" => date("Y-m-d H:i:s")
	);
	$queryResult = $db->insert("Profile", $data);
}

/**
 *
 * Find the profileID of a user based on the userID given
 *
 * @param    int $userID of user whos profileID is needed
 * @return   int The profileID of the user requested
 *
 */
function GetUserProfileID($userID)
{
	global $db;
	$profileID = 0;
	if ($userID != 0)
	{
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE userID = ?", Array($userID));
		if(count($queryResult) == 1)
		{
			$profileID = $queryResult[0]["profileID"];
		}
	}

	return $profileID;
}

/**
 *
 * Obtain the profileID based on a given userName
 *
 * @param    string  $profileName
 * @return   string $profileID
 *
 */
function GetProfileID($userName)
{
	global $db;
	$profileID = 0;
	if(strlen($userName) != 0)
	{
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE userName = ?", Array($userName));
		if(count($queryResult) == 1)
		{
			$profileID = $queryResult[0]["profileID"];
		}
	}

	return $profileID;
}

/**
 *
 * Find the userName of a user based on the profileID given
 *
 * @param    int $profileID of user whos userName is needed
 * @return   string The userName of the user requested
 *
 */
function GetUserName($profileID)
{
	global $db;
	$userName = 0;
	$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE profileID = ?", Array($profileID));
	if(count($queryResult) == 1)
	{
		$userName = $queryResult[0]["userName"];
	}

	return $userName;
}

/**
 *
 * Find the userID of a user based on the profileID given
 *
 * @param    int $profileID of user whos userID is needed
 * @return   int $userID of the user
 *
 */
function GetProfileUserID($profileID)
{
	global $db;
	$userID = 0;
	$queryResult = $db->rawQuery("SELECT userID FROM Profile WHERE profileID = ?", Array($profileID));
	if(count($queryResult) == 1)
	{
		$userID = $queryResult[0]["userID"];	
	}	

	return $userID;
}

/**
 *
 * Returns the given users profile
 *
 * Returns a users profile based on either the profileID given
 *
 * @param    string $profileID of the current logged in user 
 * @param    string $requestedProfileID of the current logged in user 
 * @param 	 string $filter comma seperated list of what fields are required
 * @return   array of the users profile or any errors that occur
 *
 */
function GetUserProfile($profileID, $requestedProfileID, $filter= "")
{
	global $db, $profileImagePath;
	
	if($requestedProfileID === 0)
	{
		return null;
	}

	if($filter == "")
	{
		$filter = array("profieID", "firstName", "lastName", "fullName", "fullNameUserName", "userName", "profileImage", "profileLink", "userBio", "email", "dob"," gender", "location", "joinDate", "listensTo", "audience", "listening");
	}
	else
	{
		$filter = explode(",", str_replace(" ", "", $filter));
	}

	if(count($filter) == 0)
	{
		return null;
	}

	$profile = array();

	$queryResult = $db->rawQuery("SELECT profileID, firstName, lastName, userName, profileImage, userBio, dob, gender, location, joinDate FROM Profile WHERE profileID = ?", Array($requestedProfileID));

	if (count($queryResult) == 1)
	{	
		if (true)//$queryResult[0]["profileImage"] == "")
		{
			$profileImage = "identicon/" . $queryResult[0]["userName"] . ".png";
		}
		else
		{
			$profileImage = $profileImagePath . $queryResult[0]["profileImage"];
		}
		
		//Additional Fields not returned in the query or need additonal formating
		$fields = array();
		$fields["profileLink"] = "profile/" . $queryResult[0]["userName"];
		$fields["fullName"] = $queryResult[0]["firstName"] . " " . $queryResult[0]["lastName"];
		$fields["listensTo"] = getCount($requestedProfileID, "listening");
		$fields["audience"] = getCount($requestedProfileID, "audience");
		$fields["listening"] = getListeningStatus($profileID, $requestedProfileID);
		$fields["profileImage"] = $profileImage;
		$fields["email"] = GetUserEmail(GetUserProfileID($requestedProfileID));
		$fields["dob"] =  date("d/m/Y", strtotime($queryResult[0]["dob"]));
		$fields["fullNameUserName"] = $queryResult[0]["firstName"] . " " . $queryResult[0]["lastName"] . " (" . $queryResult[0]["userName"] . ")";

		foreach ($filter as $value) 
		{
			if(array_key_exists($value, $fields))
			{
				$profile["$value"] = $fields["$value"];
			}
			elseif (array_key_exists($value, $queryResult[0])) 
			{
				$profile["$value"] = $queryResult[0]["$value"];
			}
			else
			{
				$profile["$value"] = null;
			}
		}
		
	}
	
	return $profile;
}

/**
 *
 * Checks if the given username exists
 *
 * If profileID is given it wont check that profileID's userName,
 * e.g. profileID = "abc" has a username = "bob" then 
 * UserNameCheck("bob", "abc") will return false where as
 * UserNameCheck("bob") will retun true
 *
 * @param    string $userName the userName to be checked
 * @param 	 string $profileID to not check
 * @return   bool if the userName exists
 *
 */
function UserNameCheck($userName, $profileID = 0)
{
	global $db;
	$result = false;
	if($profileID == 0)
	{
		$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE userName = ?", Array($userName));
	}
	else
	{
		$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE userName = ? AND profileID != ?", Array($userName, $profileID));
	}
	
	if(count($queryResult) == 1)
	{
		$result = true;
	}	

	return $result;
}

/**
 *
 * Returns the given users Account Settings
 *
 * Returns users Account Settings/Profile for displaying on the 
 * Account Settings page.
 * Array Contents: (firstName, lastName, email, userName, userBio, dob, gender,
 * location, joinDate, profileImage)
 * 
 *
 * @param    int $profileID of the current logged in user
 * @return   array of the users account settings or any errors that occur
 *
 */
function UserAccountSettings($profileID)
{
	global $db, $errorCodes;
	
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
	else {		
		$result["userProfile"] = GetUserProfile($profileID, $profileID, "firstName, lastName, userName, email, userBio, dob, gender, location, joinDate, profileImage");
	}
	
	if (count($errors) != 0) //If no errors user is logged in
	{	
		$result["errors"] = $errors;
	}

	return $result;
}

/**
 *
 * Returns the given users profile
 *
 * Returns a users profile based on either the profileID given or the username given in
 * the request 
 * Array Contents: (firstName, lastName, userName, userBio, location, profileImage)
 *
 * @param    int $profileID of the current logged in user 
 * @return   array of the users profile or any errors that occur
 *
 */
function UserProfile($profileID)
{
	global $db, $errorCodes, $request;
	
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

	if (count($request) >= 3)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedUserName = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
			$requestedProfileID = GetProfileID($requestedUserName);	
		}
	}

	if (!isset($requestedProfileID))
	{
		$requestedProfileID = $profileID;
	}
	
	if (count($errors) == 0)
	{
		$result["userProfile"] = GetUserProfile($profileID, $requestedProfileID, "profileID, firstName, lastName, userName, userBio, location, profileImage, listensTo, audience, listening");
	}
	else
	{
		$result["errors"] = $errors;
	}
		
	return $result;
}

/**
 *
 * Returns the status if a user is listening to another user
 * 
 * @param    int $profileID of the current logged in user 
 * @param    int $usersProfileID of the other user whos profile is being viewed
 * @return   bool status or null if own profile
 *
 */
function getListeningStatus($profileID, $usersProfileID)
{
	global $db;

	//check its not themself they are viewing
	if ($profileID == $usersProfileID)
	{
		return null;
	}

	$result = false;

	$queryResult = $db->rawQuery("SELECT profileID FROM Listeners WHERE profileID = ? AND listenerProfileID = ?", Array($profileID, $usersProfileID));
	if (count($queryResult) == 1)
	{
		$result = true;
	}

	return $result;
}

/**
 *
 * Returns the number of people who are listening/audience of a user
 * 
 * Calcualtes the Number of people who listen/listen to a paticular user
 * based on the type given
 *
 * @param    int $profileID of the user whos count is needed 
 * @param    string $type of count required listening/audience
 * @return   int number of people who listening/audience|null if wrong/null type
 *
 */
function getCount($profileID, $type)
{
	global $db;

	if ($type == "audience")
	{
		$countQuery = "SELECT count(*) AS count FROM Listeners WHERE listenerProfileID = ?";
	}
	elseif ($type == "listening")
	{
		$countQuery = "SELECT count(*) AS count FROM Listeners WHERE profileID = ?";
	}
	else
	{
		return null;
	}

	$queryResult = $db->rawQuery($countQuery, Array($profileID));
	$count = $queryResult[0]["count"];

	return $count;
}

/**
 *
 * Returns an array of Users based on the 
 * 
 * Searches the Profile table for users whos firstName/lastName/userName 
 * are like the request given.
 *
 * @return   array of users found
 *
 */
function UserSearch($profileID)
{
	global $db, $errorCodes, $request;
	
	$result = array();
	$errors = array();
	if (count($request) >= 3)
	{
		$searchResults = array();
		$searchParam = filter_var($request[2], FILTER_SANITIZE_STRING) . "%";
		
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE userName LIKE ? OR firstName LIKE ? OR lastName  LIKE ?", Array($searchParam, $searchParam, $searchParam));
			
			foreach ($queryResult as $user) 
			{
				$userResult = GetUserProfile($profileID, $user["profileID"], "fullNameUserName, profileImage, profileLink");
				$userResult["name"] = $userResult["fullNameUserName"];
				unset($userResult["fullNameUserName"]);
				array_push($searchResults, $userResult);
			 } 	

			 $result = $searchResults;	
	}
	else
	{
		array_push($errors, $errorCodes["P001"]);
	}

	if (count($errors) != 0)
	{
		$result["errors"] = $errors;
	}
	
	return $result;
}

/**
 *
 * Start Listening to a user
 * 
 * Creates a record to listen to another user, the profileID of the person to listen to
 * is given in the request.
 *
 * @param    int $profileID of the currentUser
 * @return   arrray result if it was successful or failed
 *
 */
function ListenToUser($profileID)
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
		$listenerProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);
		if ($profileID == $listenerProfileID) 
		{
			array_push($errors, $errorCodes["P002"]);
		}
	}
	else
	{
		array_push($errors, $errorCodes["P003"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		$status = getListeningStatus($profileID, $listenerProfileID);
		if($status == false)
		{
			//Follow User
			$data = Array(
				"profileID" => $profileID,
               	"listenerProfileID" => $listenerProfileID,
               	"dateFollowed" => date("Y-m-d H:i:s")
			);
			$id = $db->insert("Listeners", $data);
		}
		else
		{
			array_push($errors, $errorCodes["P004"]);
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
 * Stop Listening to a user
 * 
 * Deletes record to listen to another user, the userID of the person to listen to
 * is given in the request.
 *
 * @param    int $profileID of the currentUser
 * @return   arrray result if it was successful or failed
 *
 */
function StopListenToUser($profileID)
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
		$listenerProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);
		if ($profileID == $listenerProfileID) 
		{
			array_push($errors, $errorCodes["P002"]);
		}
	}
	else
	{
		array_push($errors, $errorCodes["P003"]);
	}
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		$status = getListeningStatus($profileID, $listenerProfileID);
		if($status == true)
		{
		
			$db->where("profileID", $profileID);
			$db->where("listenerProfileID", $listenerProfileID);
			$db->delete("Listeners");

		}
		else
		{
			array_push($errors, $errorCodes["P005"]);
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
 * Returns the people who the user currently listens to
 * 
 * Returns an array of users who listen to the profileID that was provided
 *
 * @param    int $profileID of the user whos listners are wanted
 * @return   arrray of users who listen to the requested user
 *
 */
function GetListeners($profileID)
{
	global $db, $errorCodes, $request;
	
	$result = array();
	$errors = array();
	$users = array();

	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	

	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}

	$requestedProfileID = $profileID;

	$timestamp = microtime();
	$offset = 0;
	
	if (count($request) == 3)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);	
		}
	}
	elseif (count($request) >= 5)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);	
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

	if (!isset($requestedProfileID))
	{
		return null;
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{		
		$offset *= 10;
		$queryResult = $db->rawQuery("SELECT listenerprofileID FROM Listeners WHERE profileID = ? AND dateFollowed >= ? ORDER BY dateFollowed LIMIT ?,10", Array($requestedProfileID, $timestamp, $offset));
		if (count($queryResult) > 0)
		{
			foreach ($queryResult as $user) 
			{
				$listener = GetUserProfile($profileID, $user["profileID"], "firstName, lastName, userName, profileImage");
				array_push($users, $listener);
			}				
 
		}

		$result["totalPages"] = CalculateUserPages($requestedProfileID, $timestamp, "listeners");
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
 * Returns the people who currenly listen to a user
 * 
 * Returns an array of users who listen to the profileID that was provided
 *
 * @param    int $profileID of the user whos listners are wanted
 * @return   arrray of users who listen to the requested user
 *
 */
function GetAudience($profileID)
{
	global $db, $errorCodes, $request;
	
	$result = array();
	$errors = array();
	$users = array();

	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	

	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	
	$requestedProfileID = $profileID;

	$timestamp = microtime();
	$offset = 0;
	
	if (count($request) == 3)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);	
		}
	}
	elseif (count($request) >= 5)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);	
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

	if (!isset($requestedProfileID))
	{
		return null;
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		$offset *= 10;
		$queryResult = $db->rawQuery("SELECT profileID FROM Listeners WHERE listenerprofileID = ? AND dateFollowed >= ? ORDER BY dateFollowed LIMIT ?,10", Array($requestedProfileID, $timestamp, $offset));
		if (count($queryResult) > 0)
		{
			foreach ($queryResult as $user) 
			{
				$audience = GetUserProfile($profileID, $user["profileID"], "firstName, lastName, userName, profileImage");
				array_push($users, $audience);
			 }
		}	

		$result["totalPages"] = CalculateUserPages($requestedProfileID, $timestamp, "audience");
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
 * Returns the total number of users that follow the given user
 *
 *
 * @param    int  $profileID 
 * @param    int  $timestamp the time we are calcuating users from
 * @param    string $view the type of view (listeners|audience)
 * @return   int the number of pages there will be
 *
 */
function CalculateUserPages($profileID, $timestamp, $view)
{
	global $db;
	$totalSays = 0;

	if ($view == "listeners")
	{
		$countQuery = "SELECT count(*) AS totalUsers FROM Listeners WHERE profileID = ? AND dateFollowed >= ?";
		$queryResult = $db->rawQuery($countQuery, Array($profileID, $timestamp));
	} 
	elseif ($view == "audience")
	{
		$countQuery = "SELECT count(*) AS totalUsers FROM Listeners WHERE listenerprofileID = ? AND dateFollowed >= ?";
		$queryResult = $db->rawQuery($countQuery, Array($profileID, $timestamp));
	}
	else
	{
		return null;
	}


	if (count($queryResult) >= 1)
	{
		$totalSays = $queryResult[0]["totalUsers"];
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
 * Update a Users Core Profile
 * 
 * Updates the users core profile (firstName, lastName, userName, dob, gender,)
 *
 * @param    int $userID of the current User
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdateProfile($profileID)
{
	global $db, $errorCodes;
	
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
	
	if ((!isset($_POST['firstName']) || strlen($_POST['firstName']) == 0))
	{
		array_push($errors, $errorCodes["P006"]);
	}
	
	if ((!isset($_POST['lastName']) || strlen($_POST['lastName']) == 0))
	{
		array_push($errors, $errorCodes["P007"]);
	}
	
	if ((!isset($_POST['userName']) || strlen($_POST['userName']) == 0))
	{
		array_push($errors, $errorCodes["P008"]);
	}
	else
	{
		$userNameCheck = "^([a-zA-Z])[a-zA-Z_-]*[\w_-]*[\S]$|^([a-zA-Z])[0-9_-]*[\S]$|^[a-zA-Z]*[\S]{5,20}$";
		if (!preg_match("/$userNameCheck/", $_POST['userName'])) //check it meets the complexity requirements set above
		{
			array_push($errors, $errorCodes["P009"]);
		}
		else
		{
			if(UserNameCheck($_POST['userName'], $profileID))
			{
				array_push($errors, $errorCodes["P012"]);
			}
		}
	}
	
	if ((!isset($_POST['dob']) || strlen($_POST['dob']) == 0))
	{
		array_push($errors, $errorCodes["P010"]);
	}
	
	if ((!isset($_POST['gender']) || strlen($_POST['gender']) == 0))
	{
		array_push($errors, $errorCodes["P011"]);
	}
	
		
	if (count($errors) == 0) 
	{
		$firstName =  filter_var($_POST['firstName'], FILTER_SANITIZE_STRING);
		$lastName = filter_var($_POST['lastName'], FILTER_SANITIZE_STRING);
		$userName =  strtoupper(filter_var($_POST['userName'], FILTER_SANITIZE_STRING));
		$dob = filter_var($_POST['dob'], FILTER_SANITIZE_STRING);
		$dob = str_replace('/', '-', $dob);
		$dob = date("Y-m-d", strtotime($dob));
		$gender = substr($_POST['gender'], 0, 1);
		$location =  filter_var($_POST['location'], FILTER_SANITIZE_STRING);
		
		$data = Array(
		    "firstName" => $firstName,
		    "lastName" => $lastName,
		    "userName" => $userName,
		    "dob" => $dob,
		    "gender" => $gender,
			"location" => $location,
		);
		$db->where("profileID", $profileID);
		$db->update("Profile", $data);
	}
	
	if (count($errors) != 0) //If no errors user is logged in
	{
		$result["errors"] = $errors;
	}
	return $result;
}

/**
 *
 * Change a users password
 * 
 * Updates the users password (currentPassword, newPassword, confirmNewPassword)
 * ensuring that the new passsword meets the complexty requirments and matches the
 * confirmation.
 *
 * @param    int $profileID of the current Profile
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdatePassword($profileID)
{
	global $db, $errorCodes;
	
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
	
	
	if (!isset($_POST['currentPassword']) || strlen($_POST['currentPassword']) == 0)
	{
		array_push($errors, $errorCodes["P013"]);
	}
	
	if (isset($_POST['newPassword']) && strlen($_POST['newPassword']) > 0)
	{
		$newPassword = $_POST['newPassword'];
		
		$passwordCheck = "^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).{8,}$";
		if (!preg_match("/$passwordCheck/", $newPassword )) //check it meets the complexity requirements set above
		{
			array_push($errors, $errorCodes["P014"]);
		}
		else 
		{
			if (isset($_POST['confirmNewPassword']) && strlen($_POST['confirmNewPassword']) > 0) //check if the confirmation password has been submitted
			{
				$confirmNewPassword = $_POST['confirmNewPassword'];
				if ($confirmNewPassword != $newPassword) //check the both passwords match
				{
					array_push($errors, $errorCodes["P022"]);
				}
			}
			else 
			{
				array_push($errors, $errorCodes["P024"]);
			}
		}
	}	
	else
	{
		array_push($errors, $errorCodes["P023"]);
	}
		
	if (count($errors) == 0) 
	{
		$userPassword = $_POST['currentPassword'];
		$userID = GetProfileUserID($profileID);

		if(PasswordValidate($userID, $userPassword))
		{
			$hashedPassword = PasswordHash($newPassword);
			ChangePassword($userID, $hashedPassword);

		}
		else
		{
			array_push($errors, $errorCodes["P015"]);
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
 * Update a users bio
 * 
 * Updates the users bio (userBio)
 *
 * @param    int $userID of the current User
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdateBio($profileID)
{
	global $db, $errorCodes;
	
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
	
	if (!isset($_POST['userBio']) || strlen($_POST['userBio']) > 500)
	{
		array_push($errors, $errorCodes["P016"]);
	}
			
	if (count($errors) == 0) 
	{
		$userBio = htmlentities($_POST['userBio']);

		$data = Array(
			"userBio" => $userBio,
		);
		$db->where("profileID", $profileID);
		$db->update("Profile", $data);
	}
	
	if (count($errors) != 0) //If no errors user is logged in
	{
		$result["errors"] = $errors;
	}
	return $result;
}

/**
 *
 * Change a users email
 * 
 * Updates the users email (currentPassword, newEmail, confirmNewEmail)
 * ensuring that it is not currently registed and the users password 
 * matches.
 *
 * @param    int $profileID of the current Profile
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdateEmail($profileID)
{
	global $db, $errorCodes;
	
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
	
	
	if (!isset($_POST['currentPassword']) || strlen($_POST['currentPassword']) == 0)
	{
		array_push($errors, $errorCodes["P012"]);
	}
	
	if (isset($_POST['newEmail']) && strlen($_POST['newEmail']) > 0)
	{
		$newEmailAddress = filter_var($_POST['newEmail'],FILTER_SANITIZE_EMAIL);
		
		if (!filter_var($newEmailAddress, FILTER_VALIDATE_EMAIL)) //Check if its a vaild email format 
		{
			array_push($errors, $errorCodes["P018"]);
		}
		else
		{
			if ((!isset($_POST['confirmNewEmail'])) || (strlen($_POST['confirmNewEmail']) == 0)) //Check if the confirmation email has been submitted 
			{
				array_push($errors, $errorCodes["P019"]);
			}
			else
			{
				$confirmNewEmail = filter_var($_POST['confirmNewEmail'],FILTER_SANITIZE_EMAIL);
				if ($newEmailAddress != $confirmNewEmail) //Check if both email addresses match
				{
					array_push($errors, $errorCodes["P020"]);
				}
				else
				{
					if(GetUserID($_POST['newEmail']))
					{
						array_push($errors, $errorCodes["P021"]);		
					}
				}
			}
		}
	}	
	else
	{
		array_push($errors, $errorCodes["P017"]);
	}
		
	if (count($errors) == 0) 
	{
		$userPassword = $_POST['currentPassword'];
		$userID = GetProfileUserID($profileID);

		if(PasswordValidate($userID, $userPassword))
		{
			ChangeEmail($userID, $newEmailAddress);
		}
		else
		{
			array_push($errors, $errorCodes["P015"]);
		}
	}
	
	if (count($errors) != 0) //If no errors user is logged in
	{
		$result["errors"] = $errors;
	}
	return $result;
}

/**
 *
 * Update a users profile Picture
 * 
 *
 * @param    int $userID of the current User
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdateProfileImage($profileID)
{
	global $db, $errorCodes, $profileImagePath;
	
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
	
	if (isset($_POST['profileImage']))
	{
		$profileImageBase64 = explode(',', $_POST['profileImage']);
		$profileImage = $profileImageBase64[1];
		$img = imagecreatefromstring(base64_decode($profileImage));
		if (!$img) {
      		array_push($errors, $errorCodes["G000"]);
    	}
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
	}
			
	if (count($errors) == 0) 
	{
		$profileImageFileName = sha1($profileImage) . "_" . time() . ".jpg";

		$data = Array(
			"profileImage" => $profileImageFileName,
		);
		$db->where("profileID", $profileID);
		$db->update("Profile", $data);

		imagejpeg($img, "../profilePics/" . $profileImageFileName);
	}
	
	if (count($errors) != 0) //If no errors user is logged in
	{
		$result["errors"] = $errors;
	}
	return $result;
}
?>