<?php
class DB_Connect{
	// change these values
	protected $host = "localhost:3306";
	protected $user = "XXXXXX";
	protected $pass = "XXXX";
	protected $db = "XXXXX";
	
	public function query_db($query){
		
		$conn = mysql_connect($this->host, $this->user, $this->pass);
		//echo $conn;
		if (!$conn) {
			die('Could not connectooo: ' . mysql_error());
		}

		mysql_select_db($this->db);
		$results = mysql_query($query) or die('Query failed: ' . mysql_error());
		
		mysql_close($conn);
		return $results;
	}
	
	public static function mysql_insert($query){
		$results = $this->query_db($query);
		return $results;
	}
	
	/*
	 * returns the User IP Address
	 *
	 *
	*/
	function getUserIP(){
	   $ip = "";
	
	   if (isset($_SERVER)){
		   if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
			   $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		   } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
			   $ip = $_SERVER["HTTP_CLIENT_IP"];
		   } else {
			   $ip = $_SERVER["REMOTE_ADDR"];
		   }
	   } else {
		   if ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
			   $ip = getenv( 'HTTP_X_FORWARDED_FOR' );
		   } elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
			   $ip = getenv( 'HTTP_CLIENT_IP' );
		   } else {
			   $ip = getenv( 'REMOTE_ADDR' );
		   }
	   }
	   return $ip;
	}
	
	/*
	 *
	 *
	 *
	*/
	function authenticate($field){
		// To foil any possible attempts at SQL injection,
		// do the following function
		// $variable=str_replace("what to look for",
		//           "what to replace it with",$what_variable_to_use);
		// Now use the replace function on our variables
	
		if(trim($field) == "" || $field == null) return false;
	
		$field = str_replace(" ", "", $field); //remove spaces from password
		$field = str_replace("%20", "", $field); //remove escaped spaces from password
		// And finally, add slashes to escape things like quotes and apostrophes
		// because they can be used to hijack SQL statements!
		// use the function, addslashes(), pretty self explanatory
		//$field = addslashes($field); //remove spaces from password
		return $field;
	} // end authenticate()
	
	function validate_email($email){
	
	   // Create the syntactical validation regular expression
	   $regexp =
		"^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$";
	
	   // Presume that the email is invalid
	   $valid = 0;
	
	   // Validate the syntax
	   if (eregi($regexp, $email))
	   {
		  list($username,$domaintld) = split("@",$email);
		  $valid = 1;
	   } else {
		  $valid = 0;
	   }
	
	   return $valid;
	
	}

}
?>