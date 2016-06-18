<!DOCTYPE html>

<html>
<head>
    <title>Textpenses</title>
    <style type="text/css">
    	.error{color:red;}
    	.success{color:green;}
    	h1{margin-top:0;}
    	div#log {
	    	display:block;
	    	overflow:auto;
	    	border: 1px black solid;
	    	bgcolor:#aaa;
    		padding:7px;
    	}
    	div#rightcol{
    		float:right;
    		padding:7px;
    		width:35%;
    	}
    	div#container{
    		float:left;
    		padding:7px;
    		width:60%;
    	}
    </style>
</head>

<body>
<?php
/**************************************************************
This application uses Nexmo to update and report on a database
in order to track shared household expenses. 

Nexmo uses SMS to send data to this page via a GET request.
The Nexmo number is removed, and the callback to this page
is hard-coded in the settings on my Nexmo dashboard: 
https://dashboard.nexmo.com/

"direct" is a keyword that signals that the transaction is a 
direct payment, person to person rather than a paid-for shared
expense. Direct payments are calculated differently (2x).

$ is a key that signals the amount of the transaction follows.
Do not use a $ or "direct" anywhere else in the text message.

Planned features:
- validate submission for more than one $, no $ but a parsable amount
- send an email with either a confirmation or error messages
- make it fun by putting a random gif in the confirmation?
- record the entire original string in the DB, for troubleshooting
- add a keyword that triggers a balance report being emailed
- have this page show the full transaction history and balance
  information, link to this page in the report email.

***************************************************************/


// static variables (keep these private)
$db_name = "kneesand_kmexpensesTEST";
$username = "removed";
$pw = "removed";
$host = "localhost";

$users = array(
            $harry = array(
            	"name" => "Harry", 
            	"email" => "removed", 
            	"phones" => "removed"
            ), 
            $victor = array(
            	"name" => "Victor", 
            	"email" => "removed", 
            	"phones" => "removed"
            ), 
            $victor2 = array(
            	"name" => "Victor", 
            	"email" => "removed", 
            	"phones" => "removed"
            )                      
        );

// global variables
$con; //dbconnection object
$log = ""; // text to show for testing


// data received from SMS via Nexmo
// works with get or post
$request = array_merge($_GET, $_POST);


function validateSMS($request, $users){
	$userslength = count($users);
	$origtext = "";
	// check that request is inbound message
	if(!isset($request['to']) OR !isset($request['msisdn']) OR !isset($request['text'])){
    		debugMSG('Invalid inbound text message');
	} else {
		debugMSG("This is an inbound message.");
		
		
		//look for our phone numbers and set global vars
		$recognizeNumber = false;
		for($x = 0; $x < $userslength; $x++) {
			if ($request['msisdn'] == $users[$x]["phones"]){
				$recognizeNumber = $x;
				debugMSG("Recognized number as user " . $x );
			} 
		}
		if($recognizeNumber === false) {
			debugMSG ("Could not recognize number: ".$request['msisdn']);
		} else {
			return $recognizeNumber;
		}
	}
}//function validateSMS


function dbConn($host, $db_name, $username, $pw){
global $con;
//connect to DB
    	$con = mysqli_init();
    	if (!$con){
      		debugMSG("mysqli_init failed");
    	}
    
    	if (!mysqli_real_connect($con, $host, $username, $pw)){
      		debugMSG("Connect Error: " . mysqli_connect_error());
    	} else {
        	debugMSG("Connected to mySQL database.");
        	if (!mysqli_select_db($con, $db_name)){
            		debugMSG("Connect Error: " . mysqli_connect_error());
        	} else {
            		debugMSG("Selected database " . $db_name . " successfully.");
            		return true;
        	}
    	}//else
}//function dbConn

function debugMSG($msg){
	global $log;
	//sendEmail($msg);
 	error_log($msg);
 	$log .= $msg . "<br>";
}//function debugMSG

function sendEmail($msg){
	//send the user an email to $emailadd
}

function parseSMS($request, $sender){
	$directPayment = false;
	$desc = "";
	$person = $sender["name"]; 
	$origtext = $request['text'];
	$pnumber = $request['msisdn'];
	$amount;
	
	//loop through the words in the msg to look for info
	debugMSG("Parsing. Full original text: " . $origtext . "From: " . $person );
	if (strpos($origtext, '$') === FALSE){//no $
		//check to see if this is a request for a total
		if (strpos($origtext, 'total') !== FALSE OR strpos($origtext, 'TOTAL') !== FALSE OR strpos($origtext, 'Total') !== FALSE){
			// send the user an email with the total
			debugMSG("Sending " . $sender["email"] . " an email with the total.");
			sendEmail($sender["email"], "Calculated total");
		} else {
			debugMSG("There is no amount or keyword in this text.");
		}
	} else { //$
		
		debugMSG("There is an amount.");
		$textRA = explode(" ", $origtext);
		foreach($textRA as $key => $value){
	        	if($value == 'direct' OR $value == 'Direct' OR $value == 'DIRECT'){
	        		$directPayment = true;
	        		debugMSG("This is a direct p2p payment.");
	        	}
	        	if (strpos($value, '$') !== FALSE){ // if a word has a dollar sign in it, it's the amount
	        		$numval = str_replace("$", "", $value);//remove the dollar sign
	        		$amount = floatval($numval);//convert this to a float instead of a string
	        		if ($amount == 0){
	        			$debugMSG("$ detected, but no valid amount found.");
	        		}
	        	} else {
	        		$desc .= $value . " ";//add words without keys to the description
	        	}
	    	}//foreach
	    	addTheRecord($person, $amount, $directPayment, $desc, $origtext, $pnumber);
	 }//$
} //function parseSMS
	 
	
function addTheRecord ($person, $amount, $directPayment, $description, $origtext, $pnumber) {
	global $con;
	$date = date('c'); //current date and time
	//add a record to the database with the info from the SMS
	$sql = "INSERT INTO `expenses`(`person`, `amount`, `date`, `directpayement`, `description`, `transID`, `originaltext`) VALUES (" .
	    "'" . $person . "', " .
	    "'" . $amount . "', " .
	    "'" . $date . "', " .
	    "'" . $directPayment . "', " .
	    "'" . $description . "', '', " .
	    "'" . $origtext . " " . $pnumber . "')";
	    
	    debugMSG("query: " . $sql );
    
    	//send to the DB
    	mysqli_query($con, $sql) or die(mysqli_error);
	debugMSG("Transaction successfully added to the database."); 

}//function addTheRecord


// begin
debugMSG("someone sent a text");
$textSenderID = validateSMS($request, $users);
if ($textSenderID !== false){
	debugMSG($textSenderID . " sent a text");
	if (dbConn($host, $db_name, $username, $pw)){
		parseSMS($request, $users[$textSenderID]);
	}
}

?>
<div id="container">
<h1>Textpenses</h1>
<h3>Expense Tracking App that receives SMS messages</h3>
<div>Jesse Harold, v 0.1</div>
<p>To add an expense, text the amount with a <b>$</b> and a description to (206) 717 7264. The app will recognize your number if you are an authorized user.</p>
<ul>
<li>For a direct person-to-person payment, include the word <b>direct</b> in your text.</li>
<li>To get an email with total owed, text <b>total</b> to the number</li>
<li>Otherwise, do not use a "$", "total," or "direct" in your message.</li>
</ul>


<h3>Show all Entries:</h3>
<table border=1 cellpadding="3">
<tr><th>Payer
</th><th>Amount
</th><th>Description
</th><th>direct payement? (1/0=y/n)
</th><th>Date Submitted
</th><th>ID
</th><th>full text
</th>
</tr>
<?php
//loop through the data and display as a table
$html = "";
$displayQuery = "SELECT * FROM expenses ORDER BY transID DESC";
$resultObj = mysqli_query($con, $displayQuery) or die("Could not Select all from ". $db_name . " expenses.");
while ($row = mysqli_fetch_array($resultObj)){
        $html .= "<tr><td>";
       $html .= $row['person'] . "</td><td>"
            . $row['amount'] . "</td><td>"
            . $row['description'] . "</td><td>"
            . $row['directpayement'] . "</td><td>"
            . $row['date'] . "</td><td>"
            . $row['transID'] . "</td><td>"
            . $row['originaltext'];
       $html .= "</td></tr>";
    }
    echo $html;
    mysqli_close($con); 
?>
</table>
</div><!--container-->
<div id="rightcol">
<h3>Program Log:</h3>
<div id="log">
<?php
echo $log;
?>
</div>
<p>You can also use this web page to test the app, using the query string. Example: <br>
http://www.kneesandtoes.org/expensesapp/nexmocallback.php?to=2037203&text=$39+for+movies&msisdn=removed</p>
</div><!--rightcol-->
</body>
</html>
