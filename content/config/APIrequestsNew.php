<?php
	$reqArray = array();
	$reqArray["user"] = [
		0 => ["GET" => "CheckLogin"],
		"login" => ["POST" => "UserLogin"],
		"register" => ["POST" => "UserRegister"],
	];
	
	$reqArray["say"] = [
		0 => ["GET" => "GetSays", "POST" => "SayIt"],
		"comment" => ["POST" => "CommentSayIt", "GET" => "GetComments"],
		"boo" => ["GET" => "Boo"],
		"applaud" => ["GET" => "Applaud"],
		"resay" => ["GET" => "Resay"],
		"user" => ["GET" => "GetUserSays"],
		"say" => ["GET" => "GetSay"],
		"delete" =>	["GET" => "DeleteSay"],
		"applaudusers" =>	["GET" => "ApplaudUsers"],
		"boousers" =>	["GET" => "BooUsers"],
		"resayusers" =>	["GET" => "ResayUsers"],
		"page" =>	["GET" => "GetSays"],
		];
		
	$reqArray["profile"] = [
		0 => ["GET" => "GetUserAccountSettings", "POST" => "UpdateProfile"],
		"image" => ["GET"=> "", "POST" => "UpdateProfileImage"],
		"bio" => ["POST" => "UpdateBio"],
		"password" => ["POST" => "UpdatePassword"],
		"email" => ["POST" => "UpdateEmail"],
		"listen" => ["GET" => "ListenToUser"],
		"listeners" => ["GET" => "GetListeners"],
		"audience" => ["GET" => "GetAudience"],
		"stoplisten" => ["GET" => "StopListenToUser"],
		"search" => ["GET" => "UserSearch"],
		"user" => ["GET" => "GetUserProfile"],
	];
?>