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
  *
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
			"userName" => $userName
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
		$profileID = $queryResult[0]["profileID"];
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
	if(strlen($userName != 0))
	{
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE userName = ?", Array($userName));
		$profileID = $queryResult[0]["profileID"];
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
	$userName = "";
	$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE profileID = ?", Array($profileID));
	$userName = $queryResult[0]["userName"];

	return $userName;
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
function GetUserAccountSettings($profileID)
{
	global $db, $errorCodes, $profileImagePath, $defaultProfileImg;
	
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
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userEmail, userName, userBio, dob, gender, location, joinDate, profileImage FROM Profile INNER JOIN UserLogin ON UserLogin.userID=Profile.userID WHERE Profile.profileID = ?", Array($profileID));
		if (count($queryResult) == 1)
		{
			$firstName = $queryResult[0]["firstName"];
			$lastName = $queryResult[0]["lastName"];
			$email = $queryResult[0]["userEmail"];
			$userName = $queryResult[0]["userName"]; 
			$userBio = $queryResult[0]["userBio"];
			$dob = $queryResult[0]["dob"];
			$gender = $queryResult[0]["gender"];
			$location = $queryResult[0]["location"];
			$joinDate = $queryResult[0]["joinDate"];
			$profileImage = $queryResult[0]["profileImage"];
					
			if ($profileImage == "")
			{
				$profileImage = $defaultProfileImg;
			}
			
			$profile = [
			"firstName" => $firstName,
			"lastName" => $lastName,
			"userName" => $userName,
			"email" => $email,
			"userBio" =>  $userBio,
			"dob" =>  date("d/m/Y", strtotime($dob)),
			"gender" =>  $gender,
			"location" =>  $location,
			"joinDate" => strtotime($joinDate) * 1000,
			"profileImage" => $profileImagePath . $profileImage,
			];
			
			
		}		
	}
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["userProfile"] = $profile;
	}
	else
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
function GetUserProfile($profileID)
{
	global $db, $errorCodes, $profileImagePath, $defaultProfileImg, $request;
	
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

	$requestedUserName = GetUserName($profileID);

	if (count($request) >= 3)
	{
		if (strlen($request[2]) > 0)
		{
			$requestedUserName = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));	
		}
	}

	if (!isset($requestedUserName))
	{
		return null;
	}

	$queryResult = $db->rawQuery("SELECT profileID, firstName, lastName, userName, userBio, location, profileImage FROM Profile WHERE userName = ?", Array($requestedUserName));

	if (count($queryResult) == 1)
	{
		$requestedProfileID = $queryResult[0]["profileID"];
		$firstName = $queryResult[0]["firstName"];
		$lastName = $queryResult[0]["lastName"];
		$userName = $queryResult[0]["userName"];
		$userBio = $queryResult[0]["userBio"];
		$location = $queryResult[0]["location"];
		$profileImage = $queryResult[0]["profileImage"];
			
						
		if ($profileImage == "")
		{
			$profileImage = $defaultProfileImg;
		}
		
		$profile = [
		"profileID" => $requestedProfileID,
		"firstName" => $firstName,
		"lastName" => $lastName,
		"userName" => $userName,
		"userBio" =>  $userBio,
		"location" =>  $location,
		"profileImage" => $profileImagePath . $profileImage,
		"listensTo" => getCount($requestedProfileID, "listening"),
		"audience" => getCount($requestedProfileID, "audience"),
		"listening" => getListeningStatus($profileID, $requestedProfileID),
		];
		
		
	}
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["userProfile"] = $profile;
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
function UserSearch()
{
	global $db, $errorCodes, $profileImagePath, $defaultProfileImg, $request;
	
	$result = array();
	$errors = array();
	if (count($request) >= 3)
	{
		$searchResults = array();
		$searchParam = filter_var($request[2], FILTER_SANITIZE_STRING) . "%";
		
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE userName LIKE ? OR firstName LIKE ? OR lastName  LIKE ?", Array($searchParam, $searchParam, $searchParam));
			
			foreach ($queryResult as $user) 
			{
				$firstName = $user["firstName"];
				$lastName = $user["lastName"];
				$userName = $user["userName"];
				$profileImage = $user["profileImage"];

				if ($profileImage == "")
				{
					$profileImage = $defaultProfileImg;
				}
				
				$userResults = [
					"name" => $firstName . " " . $lastName . " (" . $userName . ")",
					"profileImage" => $profileImagePath . $profileImage,
					"profileLink" => "profile/" . $userName,
				];	
				array_push($searchResults, $userResults);
			 } 		
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
	}
	if (count($errors) == 0)
	{
		$result = $searchResults;
	}
	else
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
			array_push($errors, $errorCodes["G000"]);
		}
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
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
			array_push($errors, $errorCodes["G000"]);
		}
						 
	}


	if (count($errors) == 0)
	{
		$result["message"] = "Listening to User";
	}
	else
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
			array_push($errors, $errorCodes["G000"]);
		}
	}
	else
	{
		array_push($errors, $errorCodes["G000"]);
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
			array_push($errors, $errorCodes["G000"]);
		}				
	}

	if (count($errors) == 0)
	{
		$result["message"] = "Stopped Listening to User";
	}
	else
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
	global $db, $errorCodes, $request, $profileImagePath, $defaultProfileImg;
	
	$result = array();
	$errors = array();
	
	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	$listeners = array();
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{			
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE profileID IN (SELECT listenerprofileID FROM Listeners WHERE profileID = ?) LIMIT 10", Array($profileID));
		if (count($queryResult) > 0)
		{
			foreach ($queryResult as $user) 
			{
				$firstName = $user["firstName"];
				$lastName = $user["lastName"];
				$userName = $user["userName"];
				$profileImage = $user["profileImage"];
					 
				if ($profileImage == "")
				{
					$profileImage = $defaultProfileImg;
				}
				
				$listener = [
					"firstName" => $firstName,
					"lastName" => $lastName,
					"userName" => $userName,
					"profileImage" => $profileImagePath . $profileImage,
					"profileLink" => "profile/" . $userName,
				];	
				array_push($listeners, $listener);
			}				
 
		}
	}

	if (count($errors) == 0)
	{
		$result["totalListeners"] = count($listeners);
		$result["users"] = $listeners;
	}
	else
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
	global $db, $errorCodes, $request, $profileImagePath, $defaultProfileImg;
	
	$result = array();
	$errors = array();
	
	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	$audienceMembers = array();
	
	if ($profileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE profileID IN (SELECT profileID FROM Listeners WHERE listenerprofileID = ?) LIMIT 10", Array($profileID));
		if (count($queryResult) > 0)
		{
			foreach ($queryResult as $user) 
			{
				$firstName = $user["firstName"];
				$lastName = $user["lastName"];
				$userName = $user["userName"];
				$profileImage = $user["profileImage"];
					 
				if ($profileImage == "")
				{
					$profileImage = $defaultProfileImg;
				}
					
				$audienceMember = [
					"firstName" => $firstName,
					"lastName" => $lastName,
					"userName" => $userName,
					"profileImage" => $profileImagePath . $profileImage,
					"profileLink" => "profile/" . $userName,
				];	
				array_push($audienceMembers, $audienceMember);
			 }
		}						
	}

	if (count($errors) == 0)
	{
		$result["totalAudienceMembers"] = count($audienceMembers);
		$result["users"] = $audienceMembers;
	}
	else
	{
		$result["errors"] = $errors;
	}
	
	return $result;
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
		array_push($errors, $errorCodes["G001"]);
	}
	
	if ((!isset($_POST['firstName']) || strlen($_POST['firstName']) == 0))
	{
		array_push($errors, $errorCodes["P001"]);
	}
	
	if ((!isset($_POST['lastName']) || strlen($_POST['lastName']) == 0))
	{
		array_push($errors, $errorCodes["P002"]);
	}
	
	if ((!isset($_POST['userName']) || strlen($_POST['userName']) == 0))
	{
		array_push($errors, $errorCodes["P003"]);
	}
	else
	{
		$userNameCheck = "^([a-zA-Z])[a-zA-Z_-]*[\w_-]*[\S]$|^([a-zA-Z])[0-9_-]*[\S]$|^[a-zA-Z]*[\S]{5,20}$";
		if (!preg_match("/$userNameCheck/", $_POST['userName'])) //check it meets the complexity requirements set above
		{
			array_push($errors, $errorCodes["P004"]);
		}
	}
	
	if ((!isset($_POST['dob']) || strlen($_POST['dob']) == 0))
	{
		array_push($errors, $errorCodes["P005"]);
	}
	
	if ((!isset($_POST['gender']) || strlen($_POST['gender']) == 0))
	{
		array_push($errors, $errorCodes["P006"]);
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
		
		$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE userName = ? AND profileID != ?", Array($userName, $profileID));
		if (count($queryResult) == 0)
		{
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
		else
		{
			array_push($errors, $errorCodes["P007"]);
		}
	}
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["message"] = "Profile Updated";
	}
	else
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
 * @param    int $userID of the current User
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdatePassword($profileID, $userID)
{
	global $db, $errorCodes;
	
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if ($userID == 0)
	{
		array_push($errors, $errorCodes["G001"]);
	}
	
	
	if (!isset($_POST['currentPassword']) || strlen($_POST['currentPassword']) == 0)
	{
		array_push($errors, $errorCodes["P008"]);
	}
	
	if (isset($_POST['newPassword']) && strlen($_POST['newPassword']) > 0)
	{
		$newPassword = $_POST['newPassword'];
		
		$passwordCheck = "^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).{8,}$";
		if (!preg_match("/$passwordCheck/", $newPassword )) //check it meets the complexity requirements set above
		{
			array_push($errors, $errorCodes["P009"]);
		}
		else 
		{
			if (isset($_POST['confirmNewPassword']) && strlen($_POST['confirmNewPassword']) > 0) //check if the confirmation password has been submitted
			{
				$confirmNewPassword = $_POST['confirmNewPassword'];
				if ($confirmNewPassword != $newPassword) //check the both passwords match
				{
					array_push($errors, $errorCodes["P010"]);
				}
			}
			else 
			{
				array_push($errors, $errorCodes["P011"]);
			}
		}
	}	
		
	if (count($errors) == 0) 
	{
		$userPassword = $_POST['currentPassword'];
		
		$queryResult = $db->rawQuery("SELECT userPassword FROM UserLogin WHERE userID = ?", Array($userID));
		if (count($queryResult) == 1)
		{
			$hashPass = $queryResult[0]["userPassword"];

			if (hash_equals(crypt($userPassword, $hashPass),$hashPass))
			{					
				$saltLength = 12;
				//Generate Salt
				$bytes = openssl_random_pseudo_bytes($saltLength);
				$salt   = bin2hex($bytes);
				
				//hash password
				$hashedPassword = crypt($newPassword, '$5$rounds=5000$'. $salt .'$');
				
				$data = Array(
				    "userPassword" => $hashedPassword,
				);
				$db->where("userID", $userID);
				$db->update("UserLogin", $data);
			}
			else
			{
				array_push($errors, $errorCodes["P012"]);
			}
		}
		else
		{
			array_push($errors, $errorCodes["G000"]);
		}		
	}
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["message"] = "Password Updated";
	}
	else
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
		array_push($errors, $errorCodes["G001"]);
	}
	
	if (!isset($_POST['userBio']) || strlen($_POST['userBio']) > 500)
	{
		array_push($errors, $errorCodes["P013"]);
	}
			
	if (count($errors) == 0) 
	{
		$userBio =  substr(htmlentities($_POST['userBio']),0,500);

		$data = Array(
			"userBio" => $userBio,
		);
		$db->where("profileID", $profileID);
		$db->update("Profile", $data);
	}
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["message"] = "Bio Updated";
	}
	else
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
 * @param    int $userID of the current User
 * @return   arrray arrray result if it was successful or failed
 *
 */
function UpdateEmail($profileID, $userID)
{
	global $db, $errorCodes;
	
	$result = array();
	$errors = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	if ($userID == 0)
	{
		array_push($errors, $errorCodes["G001"]);
	}
	
	
	if (!isset($_POST['currentPassword']) || strlen($_POST['currentPassword']) == 0)
	{
		array_push($errors, $errorCodes["P008"]);
	}
	
	if (isset($_POST['newEmail']) && strlen($_POST['newEmail']) > 0)
	{
		$newEmailAddress = filter_var($_POST['newEmail'],FILTER_SANITIZE_EMAIL);
		
		if (!filter_var($newEmailAddress, FILTER_VALIDATE_EMAIL)) //Check if its a vaild email format 
		{
			array_push($errors, $errorCodes["P014"]);
		}
		else
		{
			if ((!isset($_POST['confirmNewEmail'])) || (strlen($_POST['confirmNewEmail']) == 0)) //Check if the confirmation email has been submitted 
			{
				array_push($errors, $errorCodes["P015"]);
			}
			else
			{
				$confirmNewEmail = filter_var($_POST['confirmNewEmail'],FILTER_SANITIZE_EMAIL);
				if ($newEmailAddress != $confirmNewEmail) //Check if both email addresses match
				{
					array_push($errors, $errorCodes["P016"]);
				}
			}
		}
	}	
		
	if (count($errors) == 0) 
	{
		$userPassword = $_POST['currentPassword'];


		
		$queryResult = $db->rawQuery("SELECT userPassword FROM UserLogin WHERE userID = ?", Array($userID));
		if (count($queryResult) == 1)
		{
			$hashPass = $queryResult[0]["userPassword"];
					
			if (hash_equals(crypt($userPassword, $hashPass),$hashPass))
			{		
				$queryResult = $db->rawQuery("SELECT userEmail FROM UserLogin WHERE userEmail = ?", Array($newEmailAddress));			
				if (count($queryResult) == 0)
				{

					$data = Array(
						"userEmail" => $newEmailAddress,
					);
					$db->where("userID", $userID);
					$db->update("UserLogin", $data);
							
				}
				else
				{
					array_push($errors, $errorCodes["P017"]);
				}				
			}
			else
			{
				array_push($errors, $errorCodes["P012"]);
			}
		}
		else
		{
			array_push($errors, $errorCodes["G000"]);
		}
	}
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["message"] = "Email Updated";
	}
	else
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
		array_push($errors, $errorCodes["G001"]);
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
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["message"] = "Profile Image Updated";
	}
	else
	{
		$result["errors"] = $errors;
	}
	return $result;
}
?>