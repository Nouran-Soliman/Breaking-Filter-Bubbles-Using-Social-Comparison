<?php

session_start();

if(isset($_GET['write'])){
	$username = $_GET['username'];
	$fileData = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
	$wPath = join('/', array(trim("tweets", '/'), trim($username.".json", '/')));
	$wFile = fopen($wPath, "w") or die("Error");
	fwrite($wFile, json_encode($fileData['file']));
	fclose($wFile);
	echo "Data written to file!";
	//echo $GLOBALS['HTTP_RAW_POST_DATA'];
}

?>