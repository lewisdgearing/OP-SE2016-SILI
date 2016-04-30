var userName = "";
var firstName = "";
var lastName = "";
var profileImage = "";
var loggedIn = false;
var timeNow = moment().valueOf();
var currentPage = 0;
var totalPages = 0;
var currentUserPage = 0;
var totalUserPages = 0;
var currentAction = "";
var currentViewedProfileID = "";

window.emojioneVersion = "2.1.1";

function userLogin(){
	var data = $(this).serialize();
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/user/login",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			//reset all the form fields
			$(".loginEmail").removeClass("has-error");
			$(".loginPassword").removeClass("has-error");
			$(".loginEmail .help-block").text("");
			$(".loginPassword .help-block").text("");
			data = $.parseJSON(jqXHR.responseText);
			$.each(data.errors, function(index, element) {
				if(element.code == "L001" || element.code == "L007")
				{
					$(".container-fluid").append("<div class=\"modal fade\" id=\"myModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"myModalLabel\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\" id=\"myModalLabel\">Error</h4></div><div class=\"modal-body\">An error occurred while trying to Login, Please try again.</div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Close</button></div></div></div> </div>");
					$('#myModal').modal('show');
					return false;
				}
				else 
				{
					$("." + errors[element.code].field).addClass("has-error");
					$("." + errors[element.code].field +" .help-block").text(errors[element.code].errorMessage);	
				}
			});
		},				
	  success: function(data) {	
			location.reload();
		}
	});
	return false;
};

function userRegister(){
	var data = $(this).serialize();
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/user/register",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			//reset all the form fields
			$(".registerFirstName").removeClass("has-error");
			$(".registerFirstName .help-block").text("");
			$(".registerLastName").removeClass("has-error");
			$(".registerLastName .help-block").text("");
			$(".registerEmail").removeClass("has-error");
			$(".registerEmail .help-block").text("");
			$(".registerConfirmEmail").removeClass("has-error");
			$(".registerConfirmEmail .help-block").text("");
			$(".registerPassword").removeClass("has-error");
			$(".registerPassword .help-block").text("");
			$(".registerConfirmPassword").removeClass("has-error");
			$(".registerConfirmPassword .help-block").text("");
			data = $.parseJSON(jqXHR.responseText);
			$.each(data.errors, function(index, element) {
				if(element.code == "R001" || element.code == "R007")
				{
					$(".container-fluid").append("<div class=\"modal fade\" id=\"myModal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"myModalLabel\"><div class=\"modal-dialog\" role=\"document\"><div class=\"modal-content\"><div class=\"modal-header\"><button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button><h4 class=\"modal-title\" id=\"myModalLabel\">Error</h4></div><div class=\"modal-body\">An error occurred while trying to Register, Please try again.</div><div class=\"modal-footer\"><button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Close</button></div></div></div> </div>");
					$('#myModal').modal('show');
					return false;
				}
				else 
				{
					$("." + errors[element.code].field).addClass("has-error");
					$("." + errors[element.code].field +" .help-block").text(errors[element.code].errorMessage);	
				}
			});
		},
		success: function(data) {	
			location.reload();
			}
		});
	return false;
}

function addSay(){
	var data = $(this).serialize();
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "API/say/",
		data: data,
		success: function(data) {	
		$('.emojionearea-editor').text('');
			$(".sayFeed").loadTemplate("content/templates/say.html",
				{
					sayID: data.say["sayID"],
					firstName: data.say["firstName"],
					lastName: data.say["lastName"],
					userName: data.say["userName"],
					message: data.say["message"],
					profilePicture: data.say["profileImage"],
					timeStamp: data.say["timePosted"],
					timePosted: moment(data.say["timePosted"]).fromNow(),
					boos: data.say["boos"],
					applauds: data.say["applauds"],
					resays: data.say["resays"],
					profileLink: data.say["profileLink"],
				}, { prepend: true});
;
		}
	});
	return false;
}

function fetchSays(){
	return $.ajax({
		dataType: "json",
		url: "API/say/" + currentPage + "/" + timeNow,
		success: function(data) {	
			totalPages = totalPages == 0 ? data["totalPages"] : totalPages; 
			if (totalPages == 1 || totalPages == 0)
			{
				$("#loadSays").remove();
			}
			$.each(data.says, function(index, element) {					
				loadSay(".sayFeed",element, false);
			});
		}
	});
}

function fetchMoreSays(){
	currentPage = currentPage + 1;
	return fetchSays();
}

function loadSay(location, element, profileView)
{
	if(element["activityStatus"] === false || element["ownSay"] === true && profileView === false) 
	{
		$(location).loadTemplate("content/templates/say.html",
		{
		    sayID: element["sayID"],
			firstName: element["firstName"],
		    lastName: element["lastName"],
		    userName: element["userName"],
			message: element["message"],
			profilePicture: element["profileImage"],
			timeStamp: element["timePosted"],
			timePosted: moment(element["timePosted"]).fromNow(),
			boos: element["boos"],
			applauds: element["applauds"],
			resays: element["resays"],
			profileLink: element["profileLink"],
		}, { append: true, async: false, afterInsert: function (elem) {
				assignActionStatus(elem, element);
		}});	
	} 
	else
	{
		$(location).loadTemplate("content/templates/resay.html",
		{
		    sayID: element["sayID"],
			firstName: element["firstName"],
		    lastName: element["lastName"],
		    userName: element["userName"],
			message: element["message"],
			profilePicture: element["profileImage"],
			timeStamp: element["timePosted"],
			timePosted: moment(element["timePosted"]).fromNow(),
			boos: element["boos"],
			applauds: element["applauds"],
			resays: element["resays"],
			profileLink: element["profileLink"],
			resayFirstName: element.activityStatus["firstName"],
			resayLastName: element.activityStatus["lastName"],
			resayUserName: element.activityStatus["userName"],
			resayProfileLink:element.activityStatus["profileLink"],
		}, { append: true, async: false, afterInsert: function (elem) {
				assignActionStatus(elem, element);
		}});	
	}
}

function fetchUserSays(reqUserName){
	return $.ajax({
		dataType: "json",
		url: "API/say/user/" + reqUserName + "/" + currentPage + "/" + timeNow,
		success: function(data) {	
			totalPages = totalPages == 0 ? data["totalPages"] : totalPages; 
			if (totalPages == 1)
			{
				$("#loadSaysProfile").remove();
			}

			$.each(data.says, function(index, element) {				
				loadSay(".sayFeed", element, true);		
			});
		}
	});
}

function fetchMoreUserSays(reqUserName){
	currentPage = currentPage + 1;
	return fetchUserSays(reqUserName);
}

function assignActionStatus(elem, data) {
	var sayElement = elem;
	setActionStatus(sayElement.find("i.applaud"), data["applaudStatus"]);
	setActionStatus(sayElement.find("i.reSay"), data["resayStatus"]);
	setActionStatus(sayElement.find("i.boo"), data["booStatus"]);
	setActionStatus(sayElement.find("i.applaudModal"), data["applaudStatus"]);
	setActionStatus(sayElement.find("i.reSayModal"), data["resayStatus"]);
	setActionStatus(sayElement.find("i.booModal"), data["booStatus"]);
	if(data["ownSay"] === true)
	{
		
		sayElement.find("i.reSay").addClass("reSayOwn");
		sayElement.find("i.reSay").removeClass("reSay");
		sayElement.find("i.reSayModal").addClass("reSayOwn");
		sayElement.find("i.reSayModal").removeClass("reSay");
		sayElement.find("i.reportModal").remove();
		$(document).find("#confirmReport").remove();
	}
	else
	{
		sayElement.find("i.deleteModal").remove();
		$(document).find("#confirmDelete").remove();
	}
}

function assignActionUsers(elem, data) {
	var sayElement = elem;
	var text = "";
	var col = 1;
	sayElement.find(".usersDisplay1").empty();
	sayElement.find(".usersDisplay2").empty();
	sayElement.find(".usersDisplay3").empty();
	if(data["applauds"] > 0)
	{
		text = "Applaud";
		if(data["applauds"] > 1)
		{
			text = "Applauds";
		}
		sayElement.find(".usersDisplay" + col).html("<span class=\"applaudUsers\" data-action=\"applaud\">" + data["applauds"] + " " + text + "</span>");
		col = col + 1;	
	}	
	if(data["resays"] > 0)
	{
		text = "Re-Say";
		if(data["resays"] > 1)
		{
			text = "Re-Says";
		}
		sayElement.find(".usersDisplay" + col).html("<span class=\"reSayUsers\" data-action=\"resay\">" + data["resays"] + " " + text + "</span>");
		col = col + 1;	
	}	
	if(data["boos"] > 0)
	{
		text = "Boo";
		if(data["boos"] > 1)
		{
			text = "Boos";
		}
		sayElement.find(".usersDisplay" + col).html("<span class=\"booUsers\" data-action=\"boo\">" + data["boos"] + " " + text + "</span>");
		col = col + 1;	
	}	
}

function fetchSayDetails(sayID){
	return $.ajax({
		dataType: "json",
		url: "API/say/say/" + sayID,
		success: function(data) {
			$(".sayDetailsModal").loadTemplate("content/templates/sayDetails.html",
			{
				sayID: data.say["sayID"],
				firstName: data.say["firstName"],
			    lastName: data.say["lastName"],
			    userName: data.say["userName"],
				message: data.say["message"],
				profilePicture: data.say["profileImage"],
				timePosted: moment(data.say["timePosted"]).format('lll'),
				boos: data.say["boos"],
				applauds: data.say["applauds"],
				resays: data.say["resays"],
				profileLink: data.say["profileLink"],
			}, { afterInsert: function (elem) {
						assignActionStatus(elem, data.say);
						assignActionUsers(elem, data.say);
			}});
			
		}
	});
}

function fetchComments(sayID){
	return $.ajax({
		dataType: "json",
		url: "API/say/comment/" + sayID,
		success: function(data) {
			$.each(data.comments, function(index, element) {
				$(".commentFeed").loadTemplate("content/templates/sayDetailsComment.html",
				{
					sayID: element["sayID"],
					firstName: element["firstName"],
				    lastName: element["lastName"],
				    userName: element["userName"],
					message: element["message"],
					profilePicture: element["profileImage"],
					timeStamp: element["timePosted"],
					timePosted: moment(element["timePosted"]).fromNow(),
					boos: element["boos"],
					applauds: element["applauds"],
					resays: element["resays"],
					profileLink: element["profileLink"],
				}, { append: true, afterInsert: function (elem) {
						assignActionStatus(elem, element);
				}});
			});
		}
	});	
}

function addComment(data, sayID){
	$.ajax({
		type: "POST",
		dataType: "json",
		url: "API/say/comment/" + sayID,
		data: data,
		success: function(data) {	
			$(".commentBox").val("");
			$(".commentFeed").loadTemplate("content/templates/sayDetailsComment.html",
				{
					firstName: data.comment["firstName"],
				    lastName: data.comment["lastName"],
				    userName: data.comment["userName"],
					message: data.comment["message"],
					profilePicture: data.comment["profileImage"],
					timePosted: moment(data.comment["timePosted"]).fromNow(),
					profileLink: data.comment["profileLink"]
				}, { prepend: true });
		}
	});
	return false;
}

function fetchConversations(){
	return $.ajax({
		dataType: "json",
		url: "API/message/",
		success: function(data){
			$.each(data.messages, function(index, element){
				$(".conversationFeed").loadTemplate("content/templates/conversation.html",
				{
					firstName: element["firstName"],
				    lastName: element["lastName"],
				    userName: element["userName"],
					message: element["message"],
					profilePicture: element["profileImage"],
					timePosted: moment(element["timePosted"]).format('lll'),
				}, { append: true })
			})
		}
	})
}

function fetchMessages(data, userName){
		return $.ajax({
		dataType: "json",
		url: "API/message/user/",
		success: function(data) {
			$(".messageModal").loadTemplate("content/templates/message.html",
			{
				firstName: data.say["firstName"],
			    lastName: data.say["lastName"],
			    userName: data.say["userName"],
				profilePicture: data.say["profileImage"],
			}, { async: false });
			$.each(data.messages, function(index, element){
				$(".messageFeed").loadTemplate("content/templates/singleMessage.html",
				{
					singleMessage: element["message"],
					timeStamp: element["timeSent"],
				}, { append: true, afterInsert: function(elem) {
					// MAKE FUNCTION HERE FOR PUSH MESSAGE LEFT OR RIGHT
				}});
			});
		}
	});
}

function addMessage(data, userName){

}

function getUserDetials() {
	return $.ajax({
		dataType: "json",
		url: "API/user/",
		success: function(data) {
			userName = data.userData["userName"];
			firstName = data.userData["firstName"];
			lastName = data.userData["lastName"];
			profileImage = data.userData["profileImage"];
			loggedIn = true;
		},
		error: function(jqXHR, textStatus, errorThrown) { loggedIn = false; }

	});
}

function getUserProfile(reqUserName) {
	reqUserName = typeof reqUserName !== 'undefined' ? reqUserName : '';

	reqUserName = window.btoa(reqUserName).replace("=",""); //Remove equals from base64 string	
	requestUserProfile(reqUserName).done(function(data) {
		$(".sayFeed").empty();
		fetchUserSays(reqUserName);
	});
}

function requestUserProfile(reqUserName) {
	return $.ajax({
		dataType: "json",
		url: "API/profile/user/" + reqUserName,
		success: function(data) {
			$(".profile-name").text(data.userProfile["firstName"] + " " + data.userProfile["lastName"]);
			$(".profile-username").text(data.userProfile["userName"]);
			$(".profile-profileImage").attr("src", data.userProfile["profileImage"]);
			$(".profile-userbio").text(data.userProfile["userBio"]);
			$(".profile-listens").text(numeral(data.userProfile["listensTo"]).format('0 a'));
			$(".profile-audience").text(numeral(data.userProfile["audience"]).format('0 a')); 
			$(".profile").data("userid", data.userProfile["profileID"]);
			$(".profile").data("username", data.userProfile["userName"]);
			$(".listenButton").data("listening", data.userProfile["listening"]);
			if(data.userProfile["listening"] === true) //listens to user
			{
				$(".listenButton").html("<i class=\"icons flaticon-nolisten\"></i>Stop Listening To " + data.userProfile["firstName"]);
			} 
			else if(data.userProfile["listening"] === false) //not listening to the user
			{
				$(".listenButton").html("<i class=\"icons flaticon-listen\"></i>Listen To " + data.userProfile["firstName"]);	
			}
			else //Own profile remove button
			{
				$(".listenButton").remove();
				$(".messageButton").remove();
			}			
		}
	});
}

function getUserSettings() {
	requestUserSettings().done(function(data) {
			$('body').find('select[name="gender"]').val(data.userProfile["gender"]);
			$('[data-toggle="datepicker"]').datepicker({
				autohide: true,
				format: 'dd/mm/yyyy',
				zIndex: 10000,
				startView: 2,
				date: data.userProfile["dob"]
			});
	});
}

function requestUserSettings() {
	return $.ajax({
		dataType: "json",
		url: "API/profile/",
		success: function(data) {
			$(".acc-name").text(data.userProfile["firstName"] + " " + data.userProfile["lastName"]);
			$(".acc-username").text(data.userProfile["userName"]);
			$(".acc-email").text(data.userProfile["email"]);
			$(".acc-profileImage").attr("src", data.userProfile["profileImage"]);
			$(".acc-userbio").text(data.userProfile["userBio"]);
			$(".acc-gender").text(data.userProfile["gender"]);
			$(".acc-dob").text(data.userProfile["dob"]);
			$(".acc-location").text(data.userProfile["location"]);
			$(".acc-joined").text(moment(data.userProfile["joinDate"]).format('Do MMMM YYYY'));

			$("#profileModals").html("");

			$("#profileModals").loadTemplate("content/templates/changeEmail.html", "", { append: true });
				
			$("#profileModals").loadTemplate("content/templates/changePassword.html", "", { append: true });
				
			$("#profileModals").loadTemplate("content/templates/personalForm.html",
			{
			    firstName: data.userProfile["firstName"],
			    lastName: data.userProfile["lastName"],
			    userName: data.userProfile["userName"],
				dob: data.userProfile["dob"],
				gender: data.userProfile["gender"],
				location: data.userProfile["location"]
			}, { append: true, async: false });
				
			$("#profileModals").loadTemplate("content/templates/userBio.html",
				{
				        bio: data.userProfile["userBio"]
				}, { append: true });
				
			$( "#personal-form").on('shown.bs.modal', function(){
				$("#personalLocation").easyAutocomplete(userLocationOptions);
			});
			
			
		}
	});
}

function listenButton(reqUserID, reqUserName, status) {
	var method = 'listen';
	if (status === true)
	{
		method = 'stoplisten';
	}
	return $.ajax({
		dataType: "json",
		url: "API/profile/" + method +"/" + reqUserID,
		success: function(data) {
			getUserProfile(reqUserName);
		}
	});
}

function deleteSay(sayID) {
	$.ajax({
		dataType: "json",
		async: false,
		url: "API/say/delete/" + sayID,
		
	});
	return false;
}

function ReportSay(sayID) {
	$.ajax({
		dataType: "json",
		async: false,
		url: "API/say/report/" + sayID,
		
	});
	return false;
}

function SayAction(sayID, action) {
	var dataResult;
	$.ajax({
		dataType: "json",
		async: false,
		url: "API/say/" + action + "/" + sayID,
		success: function(data) {
			dataResult = data;
		}
	});
	
	return dataResult;
}

function ProfileEdit(data)
{
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/profile/",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			
		},				
	  success: function(data) {	
			AccountSettingsUpdate('#personal-form');
		}
	});
	return false;
}

function ProfilePasswordChange(data)
{
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/profile/password",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			
		},				
	  success: function(data) {	
			AccountSettingsUpdate('#changePassword-form');
		}
	});
	return false;
}

function ProfileEmailChange(data)
{
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/profile/email",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			
		},				
	  success: function(data) {	
			AccountSettingsUpdate('#changeEmail-form');
		}
	});
	return false;
}

function ProfileBioEdit(data)
{
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/profile/bio",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			
		},				
	  success: function(data) {	
			AccountSettingsUpdate('#userBio-form');
		}
	});
	return false;
}

function ProfileImageEdit(data)
{
	$.ajax({
	  type: "POST",
	  dataType: "json",
	  url: "API/profile/image",
	  data: data,
	  error: function(jqXHR, textStatus, errorThrown) {
			
		},				
	  success: function(data) {	
			AccountSettingsUpdate('#profileImage-form');
		}
	});
	return false;
}

function setActionStatus(element, status)
{
	if(status == true)
	{
		element.addClass("active");
	}
	else
	{
		element.removeClass("active");
	}
}

function GetActionUser(sayID, action) {
	$.ajax({
		dataType: "json",
		async: false,
		url: "API/say/" + action + "users/" + sayID,
		success: function(data) {
			var actionHeader = action;
			if (action == "resay")
			{
				actionHeader = "Resaid"
			} 
			else if(action == "applaud")
			{
				actionHeader = "Applauded"
			}
			else
			{
				actionHeader = "Booed"
			}
			$(".activityModal").loadTemplate("content/templates/activity.html",
				{
					activityHeader:"Users that " + actionHeader,
				}, {async: false});


			$.each(data.users, function(index, element) {	
				$(".activityFeed").loadTemplate("content/templates/activityDisplay.html",
					{
						profileLink:element["profileLink"],
						profilePicture:element["profileImage"],
						firstName:element["firstName"],
						lastName:element["lastName"],
						userName:element["userName"],
					},{append: true});				
				
			});
			
			
		}
	});
	
	
}

function GetProfileListeners(action, name, userProfileID) {
	$.ajax({
		dataType: "json",
		async: false,
		url: "API/profile/" + action + "/" + userProfileID + "/" + currentUserPage + "/" + timeNow,
		success: function(data) {
			currentUserPage = 0;
			totalUserPages = data["totalPages"];
			currentViewedProfileID = userProfileID;
			currentAction = action;
			var actionHeader = action;
			if (action == "listeners")
			{
				actionHeader = "Users that listen to " + name;
			} 
			else
			{
				actionHeader = name + "'s Audience";
			}
			$(".profileModal").loadTemplate("content/templates/profileModal.html",
				{
					profileHeader:actionHeader,
				}, {async: false});

			if (totalUserPages == 1)
			{
				$("#loadMoreUsers").remove();
			}
			$.each(data.users, function(index, element) {	
				$(".profileFeed").loadTemplate("content/templates/activityDisplay.html",
					{
						profileLink:element["profileLink"],
						profilePicture:element["profileImage"],
						firstName:element["firstName"],
						lastName:element["lastName"],
						userName:element["userName"],
					},{append: true});				
				
			});
			
			
		}
	});
	
	
}

function fetchMoreUsers()
{
	currentUserPage = currentUserPage + 1;
	return $.ajax({
		dataType: "json",
		async: false,
		url: "API/profile/" + currentAction + "/" + currentViewedProfileID + "/" + currentUserPage + "/" + timeNow,
		success: function(data) {		
			$.each(data.users, function(index, element) {	
				$(".profileFeed").loadTemplate("content/templates/activityDisplay.html",
					{
						profileLink:element["profileLink"],
						profilePicture:element["profileImage"],
						firstName:element["firstName"],
						lastName:element["lastName"],
						userName:element["userName"],
					},{append: true});				
				
			});
			
			
		}
	});
}

function AccountSettingsUpdate(modal)
{
	getUserDetials().done(function() {
	if(loggedIn) {
		$("#profileImage").attr("src", profileImage);
		$("#userName").text(firstName);
	}
	});
	$(modal).modal('toggle');
	$('body').removeClass('modal-open');
	$('.modal-backdrop').remove();
	getUserSettings();
}


getUserDetials().done(function() {
	if(loggedIn) {
		$("#profileImage").attr("src", profileImage);
		$("#userName").text(firstName);
	}
});

var options = {
  url: function(phrase) {
		return "API/profile/search/" + phrase;
	},
  
  placeholder: "Search SILI for friends!",
  
  getValue: "name",
  
  cssClasses: "userSearch",
  
  adjustWidth: false,
  
  requestDelay: 500,

  template: {
  	type: "iconRight",
		fields: {
			iconSrc: "profileImage"
		}
  },

  list: {
		maxNumberOfElements: 10,
		match: {
				enabled: true
		},
		showAnimation: {
		  type: "slide"
		},
		hideAnimation: {
		  type: "slide"
		},
		onChooseEvent: function() {
			var index = $("#userSearch").getSelectedItemIndex();
			window.location = $("#userSearch").getItemData(index).profileLink;
		}
  }

};

var userLocationOptions = {
  url: function(phrase) {
		return "//maps.googleapis.com/maps/api/geocode/json?address=" + phrase;
	},
  
  placeholder: "Type to search location",
  
  getValue: "formatted_address",
  
  cssClasses: "userLocation",
  
  adjustWidth: false,
  
  requestDelay: 500,
  
  listLocation: "results",

  list: {
		maxNumberOfElements: 10,
		match: {
				enabled: true
		},
		showAnimation: {
		  type: "slide"
		},
		hideAnimation: {
		  type: "slide"
		},
		onChooseEvent: function() {
			var index = $("#personalLocation").getSelectedItemIndex();
			//window.location = $("#personalLocation").getItemData(index).profileLink;
		}
  }

};

angular.module('app', ['ngImgCrop'])
  .controller('Ctrl', function($scope) {
    $scope.myImage = '';
    $scope.myCroppedImage = '';

    var handleFileSelect=function(evt) {
      var file = evt.currentTarget.files[0];
      var reader = new FileReader();
      reader.onload = function (evt) {
        $scope.$apply(function($scope){
          $scope.myImage = evt.target.result;
        });
      };

    $scope.onLoadError=function() {
    };
    $scope.onLoadDone=function() {
      $('#profileImage-form').modal('show');
    };
      reader.readAsDataURL(file);
    };
    angular.element(document.querySelector('#profileImageUpload')).on('change',handleFileSelect);
  });

$("document").ready(function() {
	setInterval(function()
	{
		$(".say").each(function() {
			var timeCode = $(this).data("timestamp");
			$(this).find(".timeStamp").text(moment(timeCode).fromNow());
		});
	},60000);
	
	$("#userSearch").easyAutocomplete(options);
	$(document).on('click', '.applaud', function(){		
		var $el = $(this).parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = SayAction(sayID, "applaud")
		var count = action["applauds"];
		var status = action["status"];
		$(this).parent().find("span").html(count);			
		setActionStatus($(this).parent().find("i"), status);	
	});
	$(document).on('click', '.applaudModal', function(){		
		var $el = $(this).parent().parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = SayAction(sayID, "applaud")
		var count = action["applauds"];
		var status = action["status"];
		$(this).parent().parent().find("span.applaudModalCount").html(count);			
		setActionStatus($(this).parent().find("i"), status);	
		$("#" + sayID).find("span.applaud").html(count);	
		setActionStatus($("#" + sayID).find("i.applaud"), status);
		assignActionUsers($el, action);
	});
		
	$(document).on('click', '.reSay', function(){
		var $el = $(this).parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = SayAction(sayID, "resay");
		var count = action["resays"];
		var status = action["status"];
		$(this).parent().find("span").html(count);			
		setActionStatus($(this).parent().find("i"), status);
	});
	
	$(document).on('click', '.reSayModal', function(){		
		var $el = $(this).parent().parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = SayAction(sayID, "resay")
		var count = action["resays"];
		var status = action["status"];
		$(this).parent().parent().find("span.reSayModalCount").html(count);			
		setActionStatus($(this).parent().find("i"), status);	
		$("#" + sayID).find("span.reSay").html(count);	
		setActionStatus($("#" + sayID).find("i.reSay"), status);
		assignActionUsers($el, action);
	});
	
	$(document).on('click', '.boo', function(){
		var $el = $(this).parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = SayAction(sayID, "boo");
		var count = action["boos"];
		var status = action["status"];
		$(this).parent().find("span").html(count);			
		setActionStatus($(this).parent().find("i"), status);
		});
		
	$(document).on('click', '.booModal', function(){		
		var $el = $(this).parent().parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = SayAction(sayID, "boo");
		var count = action["boos"];
		var status = action["status"];
		$(this).parent().parent().find("span.booModalCount").html(count);			
		setActionStatus($(this).parent().find("i"), status);	
		$("#" + sayID).find("span.boo").html(count);	
		setActionStatus($("#" + sayID).find("i.boo"), status);
		assignActionUsers($el, action);
	});	
		
	$(document).on('click', '.more', function(){
		var $el = $(this).parent().parent().parent().parent();
		var sayID = $el.attr('id');
		fetchSayDetails(sayID).done(function() {
			fetchComments(sayID).done(function()
			{
				$('#sayDetailsModal').modal('show');
			});
		});
	});

	$(document).on('click', '.deleteModal', function(){
		$('#confirmDelete').modal('show');
	});

	$(document).on('click', '.reportModal', function(){
		$('#confirmReport').modal('show');
	});

	$(document).on('click', '.applaudUsers, .booUsers, .reSayUsers', function(){
		var $el = $(this).parent().parent().parent().parent().parent();
		var sayID = $el.attr('id');
		var action = $(this).data('action');
		GetActionUser(sayID, action);

		$('#activityModal').modal('show');
	});

	$(document).on('click', '.listeningUsers, .userAudience', function(){
		var $el = $(this).parent().parent();		
		var action = $(this).data('action');
		var name = $(document).find(".profile-name").text();
		var userProfileID = $el.data('userid');		
		GetProfileListeners(action, name, userProfileID);
		$('#profileModal').modal('show');
	});

	$(document).on('click', '.confirmDelete', function(){
		var $el = $(this).parent().parent();
		var sayID = $el.attr('data-sayID');
		$('#confirmDelete').modal('hide');
		$('#sayDetailsModal').modal('hide');
		$('#' + sayID).remove();
		$('.sayDetailsModal').empty();
		$('body').removeClass('modal-open');
		$('.modal-backdrop').remove();
		deleteSay(sayID);
	});

	$(document).on('click', '.confirmReport', function(){
		var $el = $(this).parent().parent();
		var sayID = $el.attr('data-sayID');
		$('#confirmReport').modal('hide');
		$('#sayDetailsModal').modal('hide');
		$('#' + sayID).remove();
		$('.sayDetailsModal').empty();
		$('body').removeClass('modal-open');
		$('.modal-backdrop').remove();
		ReportSay(sayID);
	});

	$(document).on('click', '.commentPen', function(){
		var $el = $(this).parent().parent().parent().parent();
		var sayID = $el.attr('id');
		fetchSayDetails(sayID).done(function() {
			fetchComments(sayID).done(function()
			{
				$('#sayDetailsModal').modal('show');
			});
		});
	});

	$(document).on('click', '.listenButton', function(){
		var $el = $(this).parent().parent().parent();
		var userID = $el.data('userid');
		var reqUserName = $el.data('username');
		var listeningStatus = $(this).data("listening");
		listenButton(userID, reqUserName, listeningStatus);	
		$(this).blur();
	});	


	$(document).on('submit','.comment-form', function(e){
		console.log(this);
		e.preventDefault();
		var data = $(this).serialize();
		var sayID = $(this).parent().attr('id');
		addComment(data, sayID);
		$(this).blur();
	});
	
	$('#loadSays').on('click', function() {
	    var $this = $(this);
	  	$this.button('loading');
	  	fetchMoreSays().done( function() { 
	  		$this.button('reset');
	  		if (currentPage + 1 == totalPages) {
	  			$this.remove();
	  		} });
	});
	
	$(document).on('click' , '#loadMoreUsers', function() {
	    var $this = $(this);
	  	$this.button('loading');
	  	fetchMoreUsers().done( function() { 
	  		$this.button('reset');
	  		if (currentUserPage + 1 == totalUserPages) {
	  			$this.remove();
	  		} });
	});

	$('#loadSaysProfile').on('click', function() {
	    var $this = $(this);
	  	$this.button('loading');
	  	var pathArray = window.location.pathname.split( '/' );
		var reqUserName = '';
		if(typeof pathArray[3] !== 'undefined')
		{
			reqUserName = pathArray[3];
			reqUserName = window.btoa(reqUserName).replace("=","");
		}
	  	fetchMoreUserSays(reqUserName).done( function() { 
	  		$this.button('reset');
	  		if (currentPage + 1 == totalPages) {
	  			$this.remove();
	  		} });
	});

	$("body").tooltip({
		selector: '[data-toggle="tooltip"]'
	});
});