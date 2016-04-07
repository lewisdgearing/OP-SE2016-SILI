<?php

if (!isset($internal) && !isset($controller)) //check if its not an internal or controller request
{
	//Trying to direct access
	http_response_code(403);
	exit;
}

function CreateProfileID() //Generate a Unique ProfileID
{
	global $db;
	$queryResultID = "";

	//Generate ProfileID
	do {
	  	$bytes = openssl_random_pseudo_bytes(10, $cstrong);
	   	$hex = bin2hex($bytes);
	   	
		$queryResult = $db->rawQuery("SELECT profileID FROM Profile WHERE profileID = ?", Array($hex));
	   	//Check the generated id doesnt already exist
		if (count($queryResult) == 0)
		{
			$queryResultID = $hex;
		}
	} while ($queryResultID == "");
	
return $queryResultID;
}

function CreateProfile($userID, $firstName, $lastName)
{
	global $db;
	//Generate ProfileID
	$queryResultID = CreateProfileID();

	//add user to profile table
	$data = Array(
			"profileID" => $queryResultID,
            "userID" => $userID,
            "firstName" => $firstName,
			"lastName" => $lastName
	);
	$queryResult = $db->insert("Profile", $data);
}

function GetUserAccountSettings($userID)
{
	global $db, $errorCodes, $profileImagePath, $defaultProfileImg;
	
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
	else {
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userEmail, userName, userBio, dob, gender, location, joinDate, profileImage FROM Profile INNER JOIN UserLogin ON UserLogin.userID=Profile.userID WHERE Profile.userID = ?", Array($userID));
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
			
			$queryResult = [
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
		$result["userProfile"] = $queryResult;
	}
	else
	{
		$result["errors"] = $errors;
	}
	return $result;
}

function GetUserProfile($userID)
{
	global $db, $errorCodes, $profileImagePath, $defaultProfileImg, $request;
	
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

	$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE userID = ?", Array($userID));

	if (count($queryResult) == 1)
	{
		$requestedUserName = $queryResult[0]["userName"];
	}
	

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

	$queryResult = $db->rawQuery("SELECT userID, firstName, lastName, userName, userBio, location, profileImage FROM Profile WHERE userName = ?", Array($requestedUserName));

	if (count($queryResult) == 1)
	{
		$requestedUserID = $queryResult[0]["userID"];
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
		
		$queryResult = [
		"userID" => str_replace("=", "", base64_encode(str_pad($requestedUserID, 10, '0', STR_PAD_LEFT))),
		"firstName" => $firstName,
		"lastName" => $lastName,
		"userName" => $userName,
		"userBio" =>  $userBio,
		"location" =>  $location,
		"profileImage" => $profileImagePath . $profileImage,
		"listensTo" => getCount($requestedUserID, "listening"),
		"audience" => getCount($requestedUserID, "audience"),
		"listening" => getListeningStatus($userID, $requestedUserID),
		];
		
		
	}
	
	
	if (count($errors) == 0) //If no errors user is logged in
	{
		$result["userProfile"] = $queryResult;
	}
	else
	{
		$result["errors"] = $errors;
	}
		
	return $result;
}

//Check if the current User is following the user whos profile we are viewing
function getListeningStatus($userID, $queryResultUserID)
{
	global $db;

	//check its not themself they are viewing
	if ($userID == $queryResultUserID)
	{
		return null;
	}

	$result = false;

	$queryResult = $db->rawQuery("SELECT userID FROM Listeners WHERE userID = ? AND listenerUserID = ?", Array($userID, $queryResultUserID));
	if (count($queryResult) == 1)
	{
		$result = true;
	}

	return $result;
}

function getCount($userID, $type)
{
	global $db;

	if ($type == "audience")
	{
		$countQuery = "SELECT count(*) AS count FROM Listeners WHERE listenerUserID = ?";
	}
	elseif ($type == "listening")
	{
		$countQuery = "SELECT count(*) AS count FROM Listeners WHERE userID = ?";
	}

	else
	{
		return null;
	}

	$queryResult = $db->rawQuery($countQuery, Array($userID));
	$count = $queryResult[0]["count"];

	return $count;
}

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

function ListenToUser($userID)
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
		$listenerUserID = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
		if ($userID == $listenerUserID) 
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
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		
		$queryResult = $db->rawQuery("SELECT userID, listenerUserID FROM Listeners WHERE userID = ? AND listenerUserID = ?", Array($userID, $listenerUserID));
		

		if (count($queryResult) == 0)
		{
			//Follow User

			$data = Array(
				"userID" => $userID,
               	"listenerUserID" => $listenerUserID
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

function StopListenToUser($userID)
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
		$listenerUserID = base64_decode(filter_var($request[2], FILTER_SANITIZE_STRING));
		if ($userID == $listenerUserID) 
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
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		
		$queryResult = $db->rawQuery("SELECT userID, listenerUserID FROM Listeners WHERE userID = ? AND listenerUserID = ?", Array($userID, $listenerUserID));
		

		if (count($queryResult) == 1)
		{
		
			$db->where("userID", $userID);
			$db->where("listenerUserID", $listenerUserID);
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

function GetListeners($userID)
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
	
	if ($userID == 0)
	{
		array_push($errors, $errorCodes["G001"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{			
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE userID IN (SELECT listenerUserID FROM Listeners WHERE userID = ?) LIMIT 10", Array($userID));
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
				];	
				array_push($listeners, $listener);
			}				
 
		}
	}

	if (count($errors) == 0)
	{
		$result["totalListners"] = count($listeners);
		$result["listeners"] = $listeners;
	}
	else
	{
		$result["errors"] = $errors;
	}
	
	return $result;
}

function GetAudience($userID)
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
	
	if ($userID == 0)
	{
		array_push($errors, $errorCodes["G001"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{	
		$queryResult = $db->rawQuery("SELECT firstName, lastName, userName, profileImage FROM Profile WHERE userID IN (SELECT userID FROM Listeners WHERE listenerUserID = ?) LIMIT 10", Array($userID));
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
				];	
				array_push($audienceMembers, $audienceMember);
			 }
		}						
	}

	if (count($errors) == 0)
	{
		$result["totalAudienceMembers"] = count($audienceMembers);
		$result["audienceMembers"] = $audienceMembers;
	}
	else
	{
		$result["errors"] = $errors;
	}
	
	return $result;
}

function UpdateProfile($userID)
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

		$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE userName = ? AND userID != ?", Array($userName, $userID));
		if (count($queryResult) == 0)
		{
			$data = Array(
			    "firstName" => $firstName,
			    "lastName" => $lastName,
			    "userName" => $userName,
			    "dob" => $dob,
			    "gender" => $gender,

			);
			$db->where("userID", $userID);
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

function UpdatePassword($userID)
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

function UpdateBio($userID)
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
		$db->where("userID", $userID);
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

function UpdateEmail($userID)
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
?>