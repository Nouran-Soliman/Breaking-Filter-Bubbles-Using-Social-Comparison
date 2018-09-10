<?php

require_once("twitteroauth/twitteroauth/twitteroauth.php"); //Path to twitteroauth library
require_once("twitteroauth/twitteroauth/OAuth.php"); 

define('CONSUMER_KEY', 'mEsOyJCK65LC8hAEN0UGRSEAY');
define('CONSUMER_SECRET', 'AnoszNrsYGFUD6A0eudnbj5vu25nVK82rvDkPFRwrJjwEHhWIl');
define('OAUTH_CALLBACK', 'http://localhost/getHome.php');
define('ACCESS_TOKEN', '3138711905-WtuLNEHrlMMieTcWmdYXRZBVYmZyfICrUB8nIR6');
define('ACCESS_TOKEN_SECRET', 'JR292ZrivD9aIP60J579wSByQ5lNGLvDOFfKIpJ6GaOQ4');

session_start();


if(isset($_GET['logout'])){
	session_unset();
	$redirect = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
	header('Location: '.filter_var($redirect, FILTER_SANITIZE_URL));
}


if(!isset($_SESSION['data']) && !isset($_GET['oauth_token'])){
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
	$request_token = $connection->getRequestToken(OAUTH_CALLBACK);

	if($request_token){
		$token = $request_token['oauth_token'];
		$_SESSION['request_token'] = $token;
		$_SESSION['request_token_secret'] = $request_token['oauth_token_secret'];

		$login_url = $connection->getAuthorizeURL($token); 
	}
}




if(isset($_GET['oauth_token'])){
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['request_token'], $_SESSION['request_token_secret']);

	$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

	if($access_token){
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
		$_SESSION['access_token'] = $access_token['oauth_token'];
		$_SESSION['access_token_secret'] = $access_token['oauth_token_secret'];
		$params = array('include_entities'=>'false');
		$data = $connection->get('account/verify_credentials', $params);

		if($data)
		{
			$_SESSION['data']=$data;
			//print_r($data);
			$redirect = "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			header('Location: '.filter_var($redirect, FILTER_SANITIZE_URL));
		}
	}
}

if (isset($_GET['read'])) {
	$username = $_GET['username'];
	$rPath = join('/', array(trim("tweets", '/'), trim($username.".json", '/')));
	$jsonFile = file_get_contents($rPath);
	echo($jsonFile);
}
else if(isset($_GET['write'])){
	$username = $_GET['username'];
	$fileData = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
	$wPath = join('/', array(trim("tweets", '/'), trim($username.".json", '/')));
	$wFile = fopen($wPath, "w") or die("Error");
	fwrite($wFile, json_encode($fileData['file']));
	fclose($wFile);
	echo "Data written to file!";
	//echo $GLOBALS['HTTP_RAW_POST_DATA'];

}
else if(isset($_GET['get_world'])){
	$keywords = $_GET['tweet_keywords'];
	$key = $_GET['key'];
	$conn = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
	$worldTweets = $conn->get("https://api.twitter.com/1.1/search/tweets.json?q=".$keywords."&count=5&result_type=popular&tweet_mode=extended");
	$tweetsObj = $worldTweets->statuses;
	$tweetsText = array($key);
	foreach($tweetsObj as $tweetObj){
		array_push($tweetsText, $tweetObj->full_text);
	}
	echo json_encode($tweetsText);
}
else if (isset($login_url) && !isset($_SESSION['data'])){
	if(!isset($_GET['signature']))
		header("Location:consent.html");
	echo "<a href='$login_url'><button style = 'background-color: #4CAF50; border: none; color: white; padding: 15px 32px; text-align: center; text-decoration: none; margin:0px auto; display:block; font-size: 16px; margin: 4px 2px; cursor: pointer; width:250px;' >Login with Twitter</button></a>";
	$_SESSION['signature'] = $_GET['signature'];
	$_SESSION['date'] = $_GET['date'];
	$_SESSION['party'] = $_GET['party'];
}	else {
	$data = $_SESSION['data'];
	#?logout=true
	echo "<a href='?logout=true'><button style = 'background-color: #4CAF50; border: none; color: white; padding: 15px 32px; text-align: center; text-decoration: none; margin:0px auto; display:block; font-size: 16px; margin: 4px 2px; cursor: pointer; width:250px;' >Logout</button></a>";
	$twitteruser = $data->screen_name;
	$notweets = 200; //change!
	
	function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
	  $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
	  return $connection;
	}
	 
	$connection = getConnectionWithAccessToken(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['access_token'], $_SESSION['access_token_secret']);
	 
	$tweets = $connection->get("https://api.twitter.com/1.1/statuses/home_timeline.json?screen_name=".$twitteruser."&count=".$notweets."&tweet_mode=extended");
	$userData = $connection->get("https://api.twitter.com/1.1/users/show.json?screen_name=".$twitteruser);

	if(!file_exists("tweets")){
		mkdir("tweets");
	}

	$json_merge = array();

	$temp = json_decode(json_encode($userData), true);
	$temp['signature'] = $_SESSION['signature'];
	$temp['date'] = $_SESSION['date'];
	$temp['party'] = $_SESSION['party'];
	$userData = $temp;

	array_push($json_merge, $userData);
	//$fullData = array_merge($userData, $tweets);

	foreach ($tweets as $tweet){
		array_push($json_merge, $tweet);
	}
	//echo json_encode($json_merge);

	$path = join('/', array(trim("tweets", '/'), trim($twitteruser.".json", '/')));
	$file = fopen($path, "w") or die("Error");
	fwrite($file, json_encode($json_merge));
	fclose($file);
	echo "Thank you! Don't forget to logout.";
	//echo json_encode($tweets);
}


?>