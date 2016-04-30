 <?php
 /**
  * SILI Slack Bot
  *
  * Slack Bot Functions for Posting and Replying to Slack
  * 
  *  
  * @copyright 2016 GLADE
  *
  * @author Definitely Lewis
  *
  */
 
/**
 *
 * Post the given data to Slack using the Incoming Web Hook URL
 *
 * @param    string $data the json data to be sent to slack
 * @return   string the result
 *
 */
function PostToSlack($data)
{
  global $slackURL;

  if(!isset($slackURL) || strlen($slackURL) == 0)
  {
    return null;
  }

  if (!isset($data) || strlen($data) == 0)
  {
    return null;
  }


  $ch = curl_init($slackURL);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  $result = curl_exec($ch);
  curl_close($ch);

  return $result;
}

/**
 *
 * Format the Array into JSON, and formatting it so slack will understand it 
 *
 * @param    string $dataArray the array of data
 * @return   string the data to send to slack
 *
 */
function FormatForSlack($dataArray)
{
  $data = "payload=" . json_encode($dataArray); 

  return $data;
}

/**
 *
 * Report a Say to the SLACK channel
 *
 * @param    string $sayID 
 * @param    string $sayMessage
 * @param    string $posterUserName
 * @param    string $reporterUserName
 * @return   string the result
 *
 */
function SlackBot_ReportSay($sayID, $sayMessage, $posterUserName, $reporterUserName)
{
  $dataArray = array();

  $dataArray["username"] = "SILI on Kate"; //The Username to be displayed on Slack (if null will default to the one set in slack)
  $dataArray["icon_url"] = "https://cdnjs.cloudflare.com/ajax/libs/emojione/2.1.4/assets/png/1f5a5.png"; //The Icon URL to be displayed on Slack (if null will default to the one set in slack)
  $dataArray["text"] = "Someone Reported this Say as inappropriate:";

  //Attachments
  $attachments = array();
  $attachment1 = array();
  $attachment1Fields = array();
  $attachment1["color"] = "danger";

  $attachment2 = array();

  //Each Field of the attachment
  $attachment1Field1 = array();  
  $attachment1Field1["title"] = "Say Message";
  $attachment1Field1["value"] = $sayMessage;
  $attachment1Field1["short"] = false;
  array_push($attachment1Fields, $attachment1Field1);

  $attachment1Field2 = array();  
  $attachment1Field2["title"] = "Orginal Say Poster";
  $attachment1Field2["value"] = $posterUserName;
  $attachment1Field2["short"] = true;
  array_push($attachment1Fields, $attachment1Field2);

  $attachment1Field3 = array();  
  $attachment1Field3["title"] = "User that Reported";
  $attachment1Field3["value"] = $reporterUserName;
  $attachment1Field3["short"] = true;
  array_push($attachment1Fields, $attachment1Field3);

  $attachment1Field4 = array();  
  $attachment1Field4["title"] = "Say ID";
  $attachment1Field4["value"] = $sayID;
  $attachment1Field4["short"] = false;
  array_push($attachment1Fields, $attachment1Field4);

  $attachment1["fields"] = $attachment1Fields;

  $attachment2["text"] = "To take action and flag it as inappropriate run :remove $sayID";

  array_push($attachments, $attachment1, $attachment2);

  $dataArray["attachments"] = $attachments;

  $data = FormatForSlack($dataArray);

  PostToSlack($data);
}

function SlackBot_ErrorOutput($error)
{
  $dataArray = array();

  $dataArray["username"] = "SILI on Kate"; //The Username to be displayed on Slack (if null will default to the one set in slack)
  $dataArray["icon_url"] = "https://cdnjs.cloudflare.com/ajax/libs/emojione/2.1.4/assets/png/1f5a5.png"; //The Icon URL to be displayed on Slack (if null will default to the one set in slack)
  $dataArray["channel"] = "#error-log"; //The Username to be displayed on Slack (if null will default to the one set in slack)
  $dataArray["text"] = "```$error```";

  $data = FormatForSlack($dataArray);

  PostToSlack($data);
}
?>