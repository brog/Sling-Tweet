<?php

//adds a second to the existing date
function add_date($orgDate){
  $cd = strtotime($orgDate);
  $retDAY = date('Y-m-d H:i:s', mktime(date('H',$cd),date('i',$cd),date('s',$cd)+1,date('m',$cd),date('d',$cd),date('Y',$cd)));
  return $retDAY;
}	

//return the published date, content
//NOTE: this v1 Twitter API needs to be migrated
//Please migrate to API v1.1. https://dev.twitter.com/docs/api/1.1/overview
function parseTwitter($tag, $last_check_date){
		$searchUrl = "http://search.twitter.com/search.atom?rpp=85&q=" . $tag;

		if($last_check_date){
			$searchUrl .= "&since_id=" . $last_check_date;
		}
		// echo '<br/> parseTwitter - $searchUrl ' . $searchUrl;
		
		$xml = simplexml_load_file($searchUrl);
		// echo $xml . '<br/><br/>';
		
		$arrTwitter = array();
		
		foreach ($xml->children() as $entry) {	
			// echo 'check ' . 	$entry->getName() . '<br/>';    
			if($entry->getName() == 'entry'){
				$arEntry = array();
				$arEntry['published'] = str_replace("Z"," ",str_replace("T"," ",$entry->published) );
				$arEntry['content'] = str_replace("&","and",$entry->title);
				$arEntry['username'] = str_replace("http://twitter.com/","",$entry->author->uri);
				$arEntry['tweet_id'] = str_replace("tag:search.twitter.com,2005:","",$entry->id);
				// echo '<br/><br/> entry_since: ' . $arEntry['tweet_id'];
				
				array_push($arrTwitter, $arEntry);
			}
		}
		// echo count($arrTwitter) . '<br/><br/> -- <br/><br/>';
		
		return $arrTwitter;
}

function getFriendCount($userName, $minNumFollowers){
	//echo '<br/><br/> $minNumFollowers '. $minNumFollowers;
	
	$aBigTimeFriends = array();
	
	$xml = simplexml_load_file("http://api.twitter.com/1/users/lookup.xml?screen_name=" . $userName);
	
	foreach ($xml->children() as $user) {	
		// echo 'check ' . 	$entry->getName() . '<br/>';
		
		$numFollowers = (int) $user->followers_count;
		  
		if($numFollowers > $minNumFollowers){
			$aBigTimeFriends[(string) $user->screen_name] = $numFollowers;
			//echo '<br/> get friend count <br/>' . $user->screen_name;
		}
	}
	
	return $aBigTimeFriends;
}

//postTwitter($post,$cRow['twitter_username'],$cRow['twitter_password']);
function postTwitter($msg,$username,$password){
	//Create the connection handle
	$curl_conn = curl_init();
	 
	//Set up the URL to query Twitter
	$user_followers = "https://twitter.com/statuses/update.xml";
	
	//Set cURL options
	curl_setopt($curl_conn, CURLOPT_URL, $user_followers); //URL to connect to
	curl_setopt($curl_conn, CURLOPT_POST, 1); //POST
	curl_setopt($curl_conn, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); //Use basic authentication
	curl_setopt($curl_conn, CURLOPT_USERPWD, $username.':'.$password); //Set u/p
	curl_setopt($curl_conn, CURLOPT_SSL_VERIFYPEER, false); //Do not check SSL certificate (but use SSL of course), live dangerously!
	curl_setopt($curl_conn, CURLOPT_RETURNTRANSFER, 1); //Return the result as string
 	curl_setopt($curl_conn, CURLOPT_POSTFIELDS,"status=".$msg);
 
	$output = curl_exec($curl_conn);
	curl_close($curl_conn);
	
	return $output;
}

//use existing php codebase
function LoadClass($classname){
	$SOURCE_LOCATION='/home2/chopsho/src/main/';
	$FILE_EXT='.php';
	
	require_once $SOURCE_LOCATION . str_replace('.', DIRECTORY_SEPARATOR, $classname) . $FILE_EXT;
}

//load database stuff
LoadClass('dao.DBConnect');
LoadClass('dao.TweetSQL');

//get all items to search on
$db = new TweetSQL();
$result = $db->getAll();

// echo 'count of things ' . count($result);

//store twitter names in map that will look friend counts
$latestMap = array();
$usernameList = '';

//loop dem items
while ($cRow = mysql_fetch_array($result)) {
	//save some api calls, make sure it's enabled
	if($cRow['enabled']){
		//get the lastest twitter search
		$hashTag = $cRow['hash'];
		$lastTweetId = $cRow['last_tweet_id'];
		
		
		//echo "<br/>hash " . $hashTag;
		// echo "<br/>lastTweetId " . $lastTweetId;
		$aEntries = parseTwitter($hashTag, $lastTweetId);//parseTwitter("%23".$cRow['hash']);
	
		echo '<br/><br/>number to process for  ' . $hashTag .  '  ' . count($aEntries);
	
		// $dbDate = add_date(date($cRow['last_post']));
		//will be used to save the latest post
		// $maxDate = $dbDate;
		$maxTweetId = $lastTweetId;
		foreach ($aEntries as $arrTwitter) {		    
			// echo $arrTwitter;
			// echo ' <br/> $arrTwitter ' . var_dump($arrTwitter);
				
			$username = $arrTwitter['username'];
			
			$usernameList .= $username . ',';
			
			$latestMap[$username] = $arrTwitter;
			
			
			echo "<br/>twitter id: " . $arrTwitter['tweet_id'] . '<br/><br/>';
			if($maxTweetId < $arrTwitter['tweet_id']){
				$maxTweetId = $arrTwitter['tweet_id'];
			}
		
			// }
		
			//update the db w/ the last published
			// $db->update($maxDate,$cRow['id']);
		}	
		$db->update_last_tweet_id($maxTweetId, $cRow['id']);
	}
}


//make request
echo '<br/><br/> user names ' . $usernameList;
$MIN_NUM_FOLLOWERS = 100000;

$friendCount = getFriendCount($usernameList, $MIN_NUM_FOLLOWERS);


// find if this user is bigtime
if(count($friendCount) > 0){
	$to = 'to@email.com, to2@email2.com';
	$headers = 'Content-Type: text/html; charset="UTF-8"' . "\r\n" .
				'From: slingtweet@domain.com' . "\r\n" .
	    		'Reply-To: slingtweet@domain.com' . "\r\n" ;
	$message = '';
	$subject = '';
	
	foreach ($friendCount as $key => $numFollowers) {
		// echo '<br/>key ' . $key;
		// echo '<br/>numFollowers ' . $numFollowers;
		
		$arrTwitter = $latestMap[$key];
		$username = $arrTwitter['username'];
		
		$subject = '@' . $username . ' Tweeted about ' . $hashTag . ' to ' . $numFollowers . ' followers';
		$message .= '<br/><br/>' . $numFollowers . ' followers --- <a href="https://twitter.com/#!/'.$username.'">@' . $username . '</a> : '. $arrTwitter['content'];
	}
	$message .=  '<br/><br/><br/> This service provided by '.
		'<a style="font-size: smaller" href="http://www.stupidventures.com">Stupid Ventures</a> <br/>';
		
	echo '<br/><br/> content ' . $message;
	$success =  mail($to, $subject , $message, $headers);
}

?>
