<?php
 /**
  * SILI User API
  *
  * User API contains functions to mainly the User Table
  * and/or functions related to User.
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
 * Check Login
 *
 * Check if a userID is set in the Sesson and matches a
 * existing user record
 * 
 * @return int userID of the logged in User
 */
function CheckLogin()
{
	global $db;
	
	$userID = 0;

	if (isset($_SESSION['userID']))
	{
		$userID = $_SESSION['userID'];
	}
	elseif (isset($_COOKIE['rememberMe']))
	{
		$userID = CookieLogin();
	}
	
	if($userID != 0)
	{
		$queryResult = $db->rawQuery("SELECT userID FROM UserLogin WHERE userID = ?", Array($userID));
		if (count($queryResult) != 1)
		{
			//No user found matching userID
			deleteRememberMeCookie($userID);
			$_SESSION = array();
			session_destroy();
			$userID = 0;
		}
	}

	return $userID;

}

/**
 *
 * User Login
 *
 * Log a User in setting there userID in the session
 * 
 * @return   string the result of the login
 *
 */
function UserLogin()
{
	global $db, $errorCodes;
	// Arrays for jsons
	$result = array();
	$errors = array();

	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	// email validation
	if ((!isset($_POST['email'])) || (strlen($_POST['email']) == 0)) // Check if the email has been submitted and is longer than 0 chars
	{
		array_push($errors, $errorCodes["U001"]);
	}
	else
	{
		$emailAddress = filter_var($_POST['email'],FILTER_SANITIZE_EMAIL);
		if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) // Check if its a vaild email format 
		{
			array_push($errors, $errorCodes["U002"]);
		}
	}
	
	//Password validation
	if (!isset($_POST['password']) || (strlen($_POST['password']) == 0)) // Check if the password has been submitted and is longer than 0 chars
	{

		array_push($errors, $errorCodes["U003"]);
	}
	
	//Process
	if (count($errors) == 0) //If theres no errors so far
	{
		$userPassword = $_POST['password'];
		

		$queryResult = $db->rawQuery("SELECT userID, userPassword FROM UserLogin WHERE userEmail = ?", Array($emailAddress));
		if (count($queryResult) == 1)
		{
			$userID = $queryResult[0]["userID"];
			$hashPass = $queryResult[0]["userPassword"];
				
			if (hash_equals(crypt($userPassword, $hashPass),$hashPass))
			{
				$_SESSION['userID'] = $userID;
			}
			else
			{
				array_push($errors, $errorCodes["U004"]);
			}
			
			if (isset($_POST['rememberMe']))
			{
				newUserRememberMeCookie($userID);
			}
		}
		else
		{
			array_push($errors, $errorCodes["U005"]);
		}
	}
	
	//Post Processing
	if (count($errors) != 0) //If there was errors otherwise login was sucessful
	{
		$result["message"] = "User Login Failed";
		$result["errors"] = $errors;

	}
	
	return $result;
}

/**
 *
 * Cookie Login
 *
 * Log a user in with a remeber me cookie that has been set
 * validating it matches the one stored in the database,
 * then creating a new one for next time
 * 
 * @return   int The userID of the user to be logged in
 *
 */
function CookieLogin()
{
	global $db, $errorCodes, $cookieSecret;
	if (isset($_COOKIE['rememberMe'])) {
		list ($userID, $token, $hash) = explode(':', $_COOKIE['rememberMe']);

		if ($hash == hash('sha256', $userID . ':' . $token . $cookieSecret) && !empty($token)) 
		{
			$queryResult = $db->rawQuery("SELECT userID FROM UserSessions WHERE rememberMeToken = ?", Array($token));			
			if (count($queryResult) == 1)
			{			
				$_SESSION['userID'] = $queryResult[0]["userID"];
				// Cookie token usable only once
				newUserRememberMeCookie($userID, $token);
				return $userID;
			}
		}
		deleteRememberMeCookie($userID);
	}
	return false;
}

/**
 *
 * Create a new remember me cookie
 *
 * Generates a new Remember Me cookie for a user
 * storing it in the database to check on the next login
 * 
 *
 */
function newUserRememberMeCookie($userID, $currentToken = '')
{
	global $db, $errorCodes, $cookieSecret;
	$randomToken = hash('sha256', mt_rand());
	
	if ($currentToken== '') {
		$data = Array ("userID" => $userID,
               "rememberMeToken" => $randomToken,
               "loginAgent" => $_SERVER['HTTP_USER_AGENT'],
			   "loginIP" =>  $_SERVER['REMOTE_ADDR'],
			   "loginDatetime" => date("Y-m-d H:i:s"),
			   "lastVisit" => date("Y-m-d H:i:s")
		);
		$db->insert ("UserSessions", $data);
	}
	else {
		$db->where ("userID = ? AND rememberMeToken = ?", Array($userID, $currentToken));
		$data = Array ("rememberMeToken" => $randomToken,
               "lastVisit" => date("Y-m-d H:i:s"),
			   "lastVisitAgent" => $_SERVER['HTTP_USER_AGENT']
		);
		$db->update ("UserSessions", $data);
	}
	
	// generate cookie string that consists of userid, randomstring and combined hash of both
	$cookieFirstPart = $userID . ':' . $randomToken;
	$cookieHash = hash('sha256', $cookieFirstPart . $cookieSecret);
	$cookie = $cookieFirstPart . ':' . $cookieHash;
	// set cookie
	setcookie('rememberMe', $cookie, time() + 1209600, "/", "kate.ict.op.ac.nz");
}

/**
 *
 * Delete a remember me cookie
 *
 * Removes a users Remember me cookie from the database
 * 
 *
 */
function deleteRememberMeCookie($userID)
{
	global $db, $errorCodes;
	if (isset($_COOKIE['rememberMe'])) {
            list ($user_id, $token, $hash) = explode(':', $_COOKIE['rememberMe']);
            
            if ($hash == hash('sha256', $user_id . ':' . $token . $cookieSecret) && !empty($token)) {
				$db->where("rememberMeToken = ? AND userID = ?", Array($token, $userID));
				$db->delete("UserSessions");
			}
        setcookie('rememberMe', false, time() - (3600 * 3650), '/', "kate.ict.op.ac.nz");
    }
}

/**
 *
 * User Register
 *
 * Create a new user login details and create a  new profile
 *
 * @return   string the result of the Registration
 *
 */
function UserRegister()
{
	global $db, $errorCodes;
	$result = array();
	$errors = array();
	
	//Pre Requirments
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}
	
	//Email Validation
	if ((!isset($_POST['email'])) || (strlen($_POST['email']) == 0)) //Check if the email has been submitted 
	{
		array_push($errors, $errorCodes["U001"]);
	}
	else
	{
		$emailAddress = filter_var($_POST['email'],FILTER_SANITIZE_EMAIL);
		if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) //Check if its a vaild email format 
		{
			array_push($errors, $errorCodes["U002"]);
		}
		else
		{
			if ((!isset($_POST['emailConfirm'])) || (strlen($_POST['emailConfirm']) == 0)) //Check if the confirmation email has been submitted 
			{
				array_push($errors, $errorCodes["U007"]);
			}
			else
			{
				$confirmEmailAddress = filter_var($_POST['emailConfirm'],FILTER_SANITIZE_EMAIL);
				if ($emailAddress != $confirmEmailAddress) //Check if both email addresses match
				{
					array_push($errors, $errorCodes["U008"]);
				}
				else
				{
					$emailCheck = $db->rawQuery("SELECT * FROM UserLogin WHERE userEmail = ?", Array($emailAddress));
					//Check that email doesnt already exist in the DB	
					if (count($emailCheck) > 0)
					{
						array_push($errors, $errorCodes["U009"]);
					}
				}
			}
		}
	}
	
	if (!isset($_POST['firstName']) || strlen($_POST['firstName']) == 0) //Check if the first name has been submitted
	{
		array_push($errors, $errorCodes["U010"]);
	}
	if (!isset($_POST['lastName']) || strlen($_POST['lastName']) == 0) //Check if the last name has been submitted
	{
		array_push($errors, $errorCodes["U011"]);
	}
	
	//Password Validation
	if (isset($_POST['password']) && strlen($_POST['password']) > 0) //check if the password has been submitted
	{
		$password = $_POST['password'];
		
		$passwordCheck = "^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).{8,}$";
		if (!preg_match("/$passwordCheck/", $password )) //check it meets the complexity requirements set above
		{
			array_push($errors, $errorCodes["U012"]);
		}
		else 
		{
			if (isset($_POST['confirmPassword']) && strlen($_POST['confirmPassword']) > 0) //check if the confirmation password has been submitted
			{
				$confirmPassword = $_POST['confirmPassword'];
				if ($confirmPassword != $password) //check the both passwords match
				{
					array_push($errors, $errorCodes["U013"]);
				}
			}
			else 
			{
				array_push($errors, $errorCodes["U014"]);
			}
		}
	}
	else 
	{
		array_push($errors, $errorCodes["U003"]);
	}
	
	//User Name Validation
	if (!isset($_POST['userName']) || strlen($_POST['userName']) == 0) //Check if the first name has been submitted
	{
		array_push($errors, $errorCodes["U015"]);
	}
	else
	{
		$userName = $_POST['userName'];
		
		$userNameCheck = "^([a-zA-Z])[a-zA-Z_-]*[\w_-]*[\S]$|^([a-zA-Z])[0-9_-]*[\S]$|^[a-zA-Z]*[\S]{5,20}$";
		if (!preg_match("/$userNameCheck/", $_POST['userName'])) //check it meets the complexity requirements set above
		{
			array_push($errors, $errorCodes["U016"]);
		}
		else
		{
			$queryResult = $db->rawQuery("SELECT userName FROM Profile WHERE userName = ?", Array($userName));
			if (count($queryResult) > 0)
			{
				array_push($errors, $errorCodes["U017"]);
			}
		}
	}
	
	//Process
	if (count($errors) == 0) //If no errors add the user to the system
	{
		$userName = strtoupper(filter_var($_POST['userName'], FILTER_SANITIZE_STRING));
		$firstName = filter_var($_POST['firstName'], FILTER_SANITIZE_STRING);
		$lastName = filter_var($_POST['lastName'], FILTER_SANITIZE_STRING);
		
		$saltLength = 12;
		//Generate Salt
		$bytes = openssl_random_pseudo_bytes($saltLength);
		$salt   = bin2hex($bytes);
		
		//hash password
		$hashedPassword = crypt($password, '$5$rounds=5000$'. $salt .'$');
		
		//Add user to the Database
		$data = Array ("userEmail" => $emailAddress,
				"userPassword" => $hashedPassword
		);
		$userID = $db->insert ("UserLogin", $data);	
		
		//Create Profile
		CreateProfile($userID, $firstName, $lastName, $userName);
		
		//Log the user in 
		$_SESSION['userID'] = $userID;	
	}
	else //return the json of errors 
	{	
		$result["message"] = "User Registration failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}
?>