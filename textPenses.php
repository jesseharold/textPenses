<!DOCTYPE html>

<html>
<head>
    <title>Expense Tracking App that receives SMS messages</title>
</head>

<body>
<?php
/**************************************************************

Work in Progress by Jesse Harold

***************************************************************/
// work with get or post
$request = array_merge($_GET, $_POST);
$log = "";
$db_name = "kneesand_kmexpensesTEST";
$username = "kneesand_jesse";
$pw = "removedForPublicRepo";
$host = "localhost";

//data for the new record
$person = "unknown";
$emailadd = "";
$directPayment = false;
$amount = 0;
$description = "";
$origtext = "";
$date = date('c'); //current date and time
$validMessage=validateSMS($request);


function validateSMS($request){
	// check that request is inbound message
	if(!isset($request['to']) OR !isset($request['msisdn']) OR !isset($request['text'])){
    		errDie('Invalid inbound text message');
    		//$log .= "Not inbound message<br>";
	} else {
		$log .= "This is an inbound message. <br>";
		$log .= "inbound message from: " . $request['msisdn'] . "<br>";
		$log .= "inbound message body: " . $request['text'] . "<br>";
		$origtext = $request['text'];
		//look for our phone numbers
		if ($request['msisdn'] == "removed"){
			$person = "Harold";
			$emailadd = "removed";
		} elseif ($request['msisdn'] == "removed" OR $request['msisdn'] == "removed"){
			$person = "Victor";
			$emailadd = "removed";
		} else {
			errDie ("Could not recognize number: ".$request['msisdn']);
		}
		$log .= "Person is: " . $person . "<br>";
		return true;
	}
}//function validateSMS

if ($validMessage){
	if (dbConn()){
		parseSMS();
	}
}

function dbConn(){
//connect to DB
    	$con=mysqli_init();
    	if (!$con){
      		errDie("mysqli_init failed");
    	}
    
    	if (!mysqli_real_connect($con, $host, $username, $pw)){
      		errDie("Connect Error: " . mysqli_connect_error());
    	} else {
        	$log .= "<p>Connected to mySQL database.</p>";
        	if (!mysqli_select_db($con, $db_name)){
            		errDie("Connect Error: " . mysqli_connect_error());
        	} else {
            		$log .= "<p>Selected database " . $db_name . " successfully.</p>";
            		return true;
        	}
    	}//else
}//function dbConn

function errDie($msg){
	sendEmail($msg);
 	die($msg);
}//function errDie

function sendEmail($msg){
	//send the user an email to $emailadd
}

function parseSMS(){
	//loop through the words in the msg to look for info
	if (strpos($origtext, '$') === FALSE){//no $
		//check to see if this is a request for a total
		if (strpos($origtext, 'total') !== FALSE OR strpos($origtext, 'TOTAL') !== FALSE OR strpos($origtext, 'Total') !== FALSE){
			// send the user an email with the total
			$log .= "Sending " . $person . " an email with the total.<br>";
			sendEmail();
		}
	} else { //$
		$textRA = explode(" ", $origtext);
		foreach($textRA as $key => $value){
	        	if($value == 'direct' OR $value == 'Direct' OR $value == 'DIRECT'){
	        		$directPayment = true;
	        	}
	        	if (strpos($value, '$') !== FALSE){ // if a word has a dollar sign, it's the amount
	        		$numval = str_replace("$", "", $value);//remove the dollar sign
	        		$amount = floatval($numval);//convert this to a float instead of a string
	        		if ($amount == 0){
	        			$errDie("$ detected, but no valid amount found.");
	        		}
	        	} else {
	        		$description .= $value . " ";//add words without keys to the description
	        	}
	    	}//foreach
	    	addTheRecord();
	 }//$
} //function parseSMS
	 
	
function addTheRecord () {
	//add a record to the database with the info from the SMS
	$sql = "INSERT INTO `expenses`(`person`, `amount`, `date`, `directpayement`, `description`, `transID`, `originaltext`) VALUES (" .
	    "'" . $person . "', " .
	    "'" . $amount . "', " .
	    "'" . $date . "', " .
	    "'" . $directPayment . "', " .
	    "'" . $description . "', '', " .
	    "'" . $origtext . $request['msisdn'] . "')";
	    
	    $log .= "query: " . $sql . "<br>";
    
    	//send to the DB
    	mysqli_query($con, $sql) or die(mysqli_error);
	$log .= "Transaction successfully added to the database.";

	mysqli_close($con);  
}//function addTheRecord
?>

<h1>Testing App that receives SMS messages</h1>
<ul><li>To add an expense, text the amount with a $ and a description to 206 717 7264</li>
<li>For a direct person-to-person payment, include the word "direct" in your text.</li>
<li>To get an email with total owed, text "total" to 206 717 7264</li>
<li>Otherwise, do not use a $, "total," or "direct" in your message.</li></ul>
<h3>Program Log:</h3>
<p>
<?php
echo $log;
?>
</p>
<h3>Sample Data:</h3>
<p>
<?php
echo "Person: ";
echo $person;
echo "<br>Direct Payment? ";
echo $directPayment;
echo "<br>Amount: ";
echo $amount;
echo "<br>Description: ";
echo $description;
?>
</p>

</body>
</html>
