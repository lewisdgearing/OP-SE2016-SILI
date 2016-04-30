<?php
function exception_handler($exception) 
{
	$trace = json_encode($exception->getTrace());
	SlackBot_ErrorOutput($exception->getMessage() ."\n\nStack_Trace\n". $trace);

  	http_response_code(500);
  	die();
}

function error_handler($errno, $errstr, $errfile, $errline)
{
	$errorText = "";
	switch ($errno) {
    case E_WARNING:
        $errorText = "'''Warning:''' $errstr on line $errline in file $errfile\n";
        break;

    case E_NOTICE:
        $errorText = "'''Notice:''' [$errno] $errstr on line $errline in file $errfile\n";
        break;

    default:
        $errorText = "[$errno] $errstr\n on line $errline in file $errfile";
        break;
    }

   	SlackBot_ErrorOutput($errorText);

    http_response_code(500);
    /* Don't execute PHP internal error handler */
    return true;
}

set_exception_handler('exception_handler');
set_error_handler('error_handler');

$errorCodes = array();
//Generic Errors
$errorCodes["G000"] = [
	"code" => "G000",
	"field" => "",
	"message" => "Error",
	];
	
$errorCodes["G001"] = [
	"code" => "G001",
	"field" => "userID",
	"message" => "No UserID provided",
	];
	
$errorCodes["G002"] = [
	"code" => "G002",
	"field" => "profileID",
	"message" => "profileID not found",
	];
	
//Generic MySQL codes/Messages
$errorCodes["M001"] = [
		"code" => "M001",
		"field" => "mysqli",
		"message" => "Failed to connect to MySQLi",
		];
$errorCodes["M002"] = [
		"code" => "M002",
		"field" => "mysqli",
		"message" => "MySQLi failed to prepare statement",
		];
	
// Login error codes/messages
$errorCodes["L002"] = [
		"code" => "L002",
		"field" => "email",
		"message" => "Email is empty", 
		];
$errorCodes ["L003"] = [
		"code" => "L003",
		"field" => "email",
		"message" => "Not a Valid Email",
		];
$errorCodes ["L004"] = [
		"code" => "L004",
		"field" => "password",
		"message" => "Password is empty",
		];
$errorCodes ["L005"] = [
		"code" => "L005",
		"field" => "password",
		"message" => "Password incorrect",
		];
$errorCodes ["L006"] = [
		"code" => "L006",
		"field" => "user",
		"message" => "User email not found",
		];
$errorCodes ["L007"] = [
		"code" => "L007",
		"field" => "mysqli",
		"message" => "Error with mysqli prepare",
		];

// Check Login error codes/messages
$errorCodes ["C001"] = [
		"code" => "C001",
		"field" => "userID",
		"message" => "User Profile not found",
		];
$errorCodes ["C002"] = [
		"code" => "C002",
		"field" => "userID",
		"message" => "No User Logged in",
		];
		
// Registration error codes/messages
$errorCodes ["R002"] = [
		"code" => "R002",
		"field" => "email",
		"message" => "Email is empty", 
		];
$errorCodes ["R003"] = [
		"code" => "R003",
		"field" => "email",
		"message" => "Not a valid email", 
		];
$errorCodes ["R004"] = [
		"code" => "R004",
		"field" => "emailConfirm",
		"message" => "Confirmation email is empty", 
		];
$errorCodes ["R005"] = [
		"code" => "R005",
		"field" => "email & emailConfirm",
		"message" => "Emails dont match", 
		];
$errorCodes ["R006"] = [
		"code" => "R006",
		"field" => "email",
		"message" => "Email already exists in the database", 
		];
$errorCodes ["R008"] = [
		"code" => "R008",
		"field" => "firstName",
		"message" => "First name is empty", 
		];
$errorCodes ["R009"] = [
		"code" => "R009",
		"field" => "lastName",
		"message" => "Last name is empty", 
		];
$errorCodes ["R010"] = [
		"code" => "R010",
		"field" => "password",
		"message" => "Password does not meet the complexity requirements", 
		];
$errorCodes ["R011"] = [
		"code" => "R011",
		"field" => "password & confirmPassword",
		"message" => "Password does not match Confirm password", 
		];
$errorCodes ["R012"] = [
		"code" => "R012",
		"field" => "confirmPassword",
		"message" => "Password Confirmation is empty", 
		];
$errorCodes ["R013"] = [
		"code" => "R013",
		"field" => "password",
		"message" => "Password is empty", 
		];
$errorCodes ["R014"] = [
		"code" => "R014",
		"field" => "userName",
		"message" => "User Name is empty", 
		];
$errorCodes ["R015"] = [
		"code" => "R015",
		"field" => "userName",
		"message" => "User Name does not meet the complexity requirements", 
		];
$errorCodes ["R016"] = [
	"code" => "R016",
	"field" => "",
	"message" => "User Name already Exists", 
	];
		
// SayIt error codes/messages
$errorCodes ["S002"] = [
		"code" => "S002",
		"field" => "userID",
		"message" => "UserID is not set", 
		];
$errorCodes ["S003"] = [
		"code" => "S003",
		"field" => "sayBox",
		"message" => "Say is empty", 
		];
		
// Profile error codes/messages
$errorCodes ["P001"] = [
	"code" => "P001",
	"field" => "firstName",
	"message" => "First Name is empty", 
	];
	
$errorCodes ["P002"] = [
	"code" => "P002",
	"field" => "lastName",
	"message" => "Last Name is empty", 
	];
	
$errorCodes ["P003"] = [
	"code" => "P003",
	"field" => "userName",
	"message" => "User Name is empty", 
	];
	
$errorCodes ["P004"] = [
	"code" => "P004",
	"field" => "userName",
	"message" => "User Name does not meet requirements", 
	];
	
$errorCodes ["P005"] = [
	"code" => "P005",
	"field" => "dob",
	"message" => "Date of Birth is empty", 
	];
	
$errorCodes ["P006"] = [
	"code" => "P006",
	"field" => "gender",
	"message" => "Gender is empty", 
	];
			
$errorCodes ["P007"] = [
	"code" => "P007",
	"field" => "",
	"message" => "User Name already Exists", 
	];
				
$errorCodes ["P008"] = [
	"code" => "P008",
	"field" => "currentPassword",
	"message" => "Current Password is empty", 
	];
				
$errorCodes ["P009"] = [
	"code" => "P009",
	"field" => "newPassword",
	"message" => "New password does not meet complexity requirements", 
	];
				
$errorCodes ["P010"] = [
	"code" => "P010",
	"field" => "",
	"message" => "New Password does not Match Confirm New Password", 
	];
				
$errorCodes ["P011"] = [
	"code" => "P011",
	"field" => "confirmNewPassword",
	"message" => "Confirm New Password is empty", 
	];	
	
$errorCodes ["P012"] = [
	"code" => "P012",
	"field" => "password",
	"message" => "Incorrect Current Password", 
	];
		
$errorCodes ["P013"] = [
	"code" => "P013",
	"field" => "userBio",
	"message" => "User Bio is empty", 
	];
		
$errorCodes ["P014"] = [
	"code" => "P014",
	"field" => "newEmail",
	"message" => "Email is invaid format", 
	];	
	
			
$errorCodes ["P015"] = [
	"code" => "P015",
	"field" => "confirmNewEmail",
	"message" => "Confirm New Email is empty", 
	];	
	
$errorCodes ["P016"] = [
	"code" => "P016",
	"field" => "",
	"message" => "New Email does not match Confirm New Email", 
	];	
	
$errorCodes ["P017"] = [
	"code" => "P017",
	"field" => "",
	"message" => "Email is already Registered", 
	];

// Comment error codes/messages
$errorCodes ["Co02"] = [
		"code" => "C002",
		"field" => "userID",
		"message" => "UserID is not set",
		];
$errorCodes ["Co03"] = [
		"code" => "C003",
		"field" => "commentBox",
		"message" => "Comment is empty",
		];
$errorCodes ["Co04"] = [
		"code" => "C004",
		"field" => "sayID",
		"message" => "SayID is not set",
		];
?>