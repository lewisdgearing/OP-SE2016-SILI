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
        $errorText = "Warning:  $errstr on line $errline in file $errfile\n";
        break;

    case E_NOTICE:
        $errorText = "Notice: $errstr on line $errline in file $errfile\n";
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
	"message" => "No ProfileID provided",
];
	
//Generic MySQL codes/Messages
$errorCodes["M001"] = [
	"code" => "M001",
	"field" => "mysqli",
	"message" => "Failed to connect to MySQLi",
];

//UserAPI Errors
$errorCodes["U001"] = [
	"code" => "U001",
	"field" => "email",
	"message" => "Email is empty",
];
$errorCodes["U002"] = [
	"code" => "U002",
	"field" => "email",
	"message" => "Invalid Email format",
];
$errorCodes["U003"] = [
	"code" => "U003",
	"field" => "password",
	"message" => "Password is empty",
];
$errorCodes["U004"] = [
	"code" => "U004",
	"field" => "password",
	"message" => "Password is incorrect",
];
$errorCodes["U005"] = [
	"code" => "U005",
	"field" => "email",
	"message" => "Email address not found",
];
$errorCodes["U007"] = [
	"code" => "U007",
	"field" => "emailConfirm",
	"message" => "Confirm Email is empty",
];
$errorCodes["U008"] = [
	"code" => "U008",
	"field" => "email/emailConfirm",
	"message" => "Email does not match Confirm Email",
];
$errorCodes["U009"] = [
	"code" => "U009",
	"field" => "email",
	"message" => "Email has already been used to register",
];
$errorCodes["U010"] = [
	"code" => "U010",
	"field" => "firstName",
	"message" => "First Name is empty",
];
$errorCodes["U011"] = [
	"code" => "U011",
	"field" => "lastName",
	"message" => "Last Name is empty",
];
$errorCodes["U012"] = [
	"code" => "U012",
	"field" => "password",
	"message" => "Password does not meet complexity requirments",
];
$errorCodes["U013"] = [
	"code" => "U013",
	"field" => "password/confirmPassword",
	"message" => "Password does not match Confirm Password",
];
$errorCodes["U014"] = [
	"code" => "U014",
	"field" => "confirmPassword",
	"message" => "Confirm Password is empty",
];
$errorCodes["U015"] = [
	"code" => "U015",
	"field" => "userName",
	"message" => "UserName is empty",
];
$errorCodes["U016"] = [
	"code" => "U016",
	"field" => "userName",
	"message" => "UserName does not meet the complexity requirments",
];
$errorCodes["U017"] = [
	"code" => "U017",
	"field" => "userName",
	"message" => "UserName already exists",
];

//SayAPI
$errorCodes["S000"] = [
	"code" => "S000",
	"field" => "sayID",
	"message" => "SayID was not given",
];
$errorCodes["S001"] = [
	"code" => "S001",
	"field" => "sayBox",
	"message" => "SayBox is empty",
];
$errorCodes["S002"] = [
	"code" => "S002",
	"field" => "commentBox",
	"message" => "CommentBox is empty",
];
$errorCodes["S003"] = [
	"code" => "S003",
	"field" => "",
	"message" => "Cannot Resay your own Say",
];

//ProfileAPI
$errorCodes["P001"] = [
	"code" => "P001",
	"field" => "",
	"message" => "Search is empty",
];
$errorCodes["P002"] = [
	"code" => "P002",
	"field" => "",
	"message" => "Other User cannot be yourself",
];
$errorCodes["P003"] = [
	"code" => "P003",
	"field" => "",
	"message" => "ProfileID of other user not provided",
];
$errorCodes["P004"] = [
	"code" => "P004",
	"field" => "",
	"message" => "Already listening to the given user",
];
$errorCodes["P005"] = [
	"code" => "P005",
	"field" => "",
	"message" => "Already not listening to the given user",
];
$errorCodes["P006"] = [
	"code" => "P006",
	"field" => "firstName",
	"message" => "First Name is empty",
];
$errorCodes["P007"] = [
	"code" => "P007",
	"field" => "lastName",
	"message" => "Last Name is empty",
];
$errorCodes["P008"] = [
	"code" => "P008",
	"field" => "userName",
	"message" => "UserName is empty",
];
$errorCodes["P009"] = [
	"code" => "P009",
	"field" => "userName",
	"message" => "UserName does not meet the complexity requirments",
];
$errorCodes["P010"] = [
	"code" => "P010",
	"field" => "dob",
	"message" => "DOB is empty",
];
$errorCodes["P011"] = [
	"code" => "P011",
	"field" => "gender",
	"message" => "Gender is empty",
];
$errorCodes["P012"] = [
	"code" => "P012",
	"field" => "userName",
	"message" => "UserName already exists",
];
$errorCodes["P013"] = [
	"code" => "P013",
	"field" => "currentPassword",
	"message" => "Current Password is empty",
];
$errorCodes["P014"] = [
	"code" => "P014",
	"field" => "newPassword",
	"message" => "New Password is empty",
];
$errorCodes["P015"] = [
	"code" => "P015",
	"field" => "currentPassword",
	"message" => "Current Password is incorrect",
];
$errorCodes["P016"] = [
	"code" => "P016",
	"field" => "userBio",
	"message" => "userBio is over 500 characters",
];
$errorCodes["P017"] = [
	"code" => "P017",
	"field" => "newEmail",
	"message" => "New Email field is empty",
];
$errorCodes["P018"] = [
	"code" => "P018",
	"field" => "newEmail",
	"message" => "Invalid Email format",
];
$errorCodes["P019"] = [
	"code" => "P019",
	"field" => "confirmNewEmail",
	"message" => "Confirm New Email is empty",
];
$errorCodes["P020"] = [
	"code" => "P020",
	"field" => "newEmail",
	"message" => "New Email already exists",
];
$errorCodes["P021"] = [
	"code" => "P021",
	"field" => "newPassword/confirmNewPassword",
	"message" => "New Password does not match Confirm New Password",
];
$errorCodes["P022"] = [
	"code" => "P023",
	"field" => "newPassword",
	"message" => "New Password is empty",
];
$errorCodes["P023"] = [
	"code" => "P023",
	"field" => "confirmNewPassword",
	"message" => "Confirm New Password is empty",
];
?>