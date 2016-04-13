<?php
 /**
  * SILI Message API
  *
  * Message API contains functions to manage the direct messages between users
  * and functions to interect with the messages table in the database.
  * 
  * Direct access to this file is not allowed, can only be included
  * in files and the file that is including it must contain 
  *	$internal=true;
  *  
  * @copyright 2016 GLADE
  *
  * @author Probably Dan (w/help from Lewis :) )
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
 * Generate a random messageID
 *
 * Generates a random messageID checking that it does not 
 * already exist in the database
 * 
 * @return   string The messageID for the Message
 *
 */
function GenerateMessageID()
{
	global $db;
	$messageID = "";

	//Generate MessageID
	do {
	  	$bytes = openssl_random_pseudo_bytes(20, $cstrong);
	   	$hex = bin2hex($bytes);
	   	
		$queryResult = $db->rawQuery("SELECT messageID FROM Message WHERE messageID = ?", Array($hex));
	   	//Check the generated id doesnt already exist
		if (count($queryResult) == 0)
		{
			$messageID = $hex;
		}
	} while ($messageID == "");
	
return $messageID;
}

/**
 *
 * Create record for message
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the message or any errors that have occurred
 *
 */
function MessageIt($profileID)
{
	global $db, $errorCodes, $request;
	// Arrays for jsons
	$result = array();
	$errors = array();

	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}

	$recipientProfileID = 0;

	if (count($request) >= 3)
	{
		if (strlen($request[2]) > 0)
		{
			$recipientProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);	
		}
	}
	
	if ($profileID === 0 || $recipientProfileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	else
	{
		// Check if the Message has been submitted and is longer than 0 chars
		if ((!isset($_POST['messageBox'])) || (strlen($_POST['messageBox']) == 0))
		{
			array_push($errors, $errorCodes["S003"]);
		}
		else
		{
			$messageContent = htmlspecialchars($_POST['messageBox']);
			$messageID = GenerateMessageID();

			$data = Array(
				"messageID" => $messageID,
				"profileID" => $profileID,
				"recipientProfileID" => $recipientProfileID,
               	"message" => $messageContent,
               	"timeSent" => date("Y-m-d H:i:s")
			);
			$db->insert("Message", $data);

			$message = FetchMessage($messageID);			
		}
	}

	// If no errors insert Say message into database
	if (count($errors) == 0)
	{
		$result["message"] = "Message has been added";
		$result["message"] = $message;
		
	}
	else //return the json of errors 
	{	
		$result["message"] = "Message failed";	
		$result["errors"] = $errors;
	}
	
	return $result;
}

/**
 *
 * Return all the messages for the current user
 *
 * Returns all the messages between current user and other users
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the messages or any errors that have occurred
 *
 */
function GetMessages($profileID)
{
	global $db, $errorCodes;
	// Arrays for jsons
	$result = array();
	$messages = array();
	
	if ($db->ping() !== TRUE) 
	{
		array_push($errors, $errorCodes["M001"]);
	}

	$recipientProfileID = 0;

	if (count($request) >= 3)
	{
		if (strlen($request[2]) > 0)
		{
			$recipientProfileID = filter_var($request[2], FILTER_SANITIZE_STRING);	
		}
	}
	
	if ($profileID === 0 || $recipientProfileID === 0)
	{
		array_push($errors, $errorCodes["G002"]);
	}
	else
	{
		$messagesQuery = "SELECT message, isRead, timeSent, firstName, lastName, profileImage, userName FROM Message JOIN Profile ON Message.recipientProfileID = Profile.profileID WHERE Message.profileID = ? GROUP BY recipientProfileID ORDER BY timeSent ASC";

		$queryResult = $db->rawQuery($messagesQuery, Array($recipientProfileID));
		if (count($queryResult) >= 1)
		{
			foreach ($queryResult as $value) {
				$messageID = $value["messageID"];
				array_push($messages, FetchMessage($messageID));
			}
		}	

		$result["messages"] = $messages;
	}
	return $result;
}

/**
 *
 * Return most recent message from each conversation between 2 users
 *
 * For display on Messages page
 *
 * @param    int  $profileID of the current logged in user
 * @return   array Containing the conversations or any errors that have occurred
 *
 */
function GetConversation($profileID)
{

}


?>