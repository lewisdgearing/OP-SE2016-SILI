<?php
$errorCodes = array();
//Generic Errors
$errorCodes["G000"] = [
	"code" => "G000",
	"field" => "",
	"message" => "Error",
	];
	
$errorCodes["G001"] = [
	"code" => "G000",
	"field" => "userID",
	"message" => "No UserID provided",
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
$errorCodes ["P002"] = [
		"code" => "P002",
		"field" => "userID",
		"message" => "UserID is not set", 
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