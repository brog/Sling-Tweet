<?php
class TweetSQL extends DB_Connect{
	
	public function create($hash,$username,$password){
		$SQL = "INSERT INTO `sv_tweetercron` (".
			"`id` , `hash` , `last_post`, `twitter_username`, `twitter_password` )".
			"VALUES (NULL, '$hash', now(), '$username', '$password' );";
		return $this->query_db($SQL);
	}
	
	public function update($post_date, $id){
		$SQL = "UPDATE `sv_tweetercron` SET `last_post` = '$post_date' WHERE `id` =$id ;";
		//echo $SQL;
		return $this->query_db($SQL);
	}
	
	public function update_last_tweet_id($tweet_id, $id){
		$SQL = "UPDATE `sv_tweetercron` SET `last_tweet_id` = '$tweet_id' WHERE `id` = $id;";
		// echo $SQL;
		return $this->query_db($SQL);
	}
	
	public function getAll(){
		$SQL = "SELECT * FROM `sv_tweetercron`";
		//echo $SQL . "<br/>";
		return $this->query_db($SQL);
	}
}
?>