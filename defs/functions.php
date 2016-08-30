<?php
define("SECURE", FALSE);	// to amend once SSL cert sorted

function sec_session_start() {
	$session_name = 'sec_session_id';   // Set a custom session name
	$secure = SECURE;
	// This stops JavaScript being able to access the session id.
	$httponly = true;
	// Forces sessions to only use cookies.
	if (ini_set('session.use_only_cookies', 1) === FALSE) {
		header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
		exit();
	}
	// Gets current cookies params.
	$cookieParams = session_get_cookie_params();
	session_set_cookie_params(
		$cookieParams["lifetime"],
		$cookieParams["path"], 
		$cookieParams["domain"], 
		$secure,
		$httponly
	);
	// Sets the session name to the one set above.
	session_name($session_name);
	session_start();			// Start the PHP session 
	session_regenerate_id();	// regenerated the session, delete the old one. 
}

function login($email, $password, $db) {
	// Using prepared statements means that SQL injection is not possible. 
	if ($q = $db->prepare("SELECT id, username, password, salt FROM users WHERE email = ? LIMIT 1")) {
		$q->bindValue(1, $email, PDO::PARAM_STR);  // Bind "$email" to parameter.
		$q->execute();	// Execute the prepared query.
		$r = $q->fetch(PDO::FETCH_ASSOC);
		// get variables from result.
		$user_id = $r['id'];
		$username = $r['username'];
		$db_password = $r['password'];
		$salt = $r['salt'];

		// hash the password with the unique salt.
		$password = hash('sha512', $password . $salt);
		$nr = $q->rowCount();
		if ($nr == 1) {
			// If the user exists we check if the account is locked
			// from too many login attempts 

			if (checkbrute($user_id, $db) == true) {
				// Account is locked 
				// Send an email to user saying their account is locked
				return false;
			} else {
				// Check if the password in the database matches
				// the password the user submitted.
				if ($db_password == $password) {
					// Password is correct!
					// Get the user-agent string of the user.
					$user_browser = $_SERVER['HTTP_USER_AGENT'];
					// XSS protection as we might print this value
					$user_id = preg_replace("/[^0-9]+/", "", $user_id);
					$_SESSION['user_id'] = $user_id;
					// XSS protection as we might print this value
					$username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);
					$_SESSION['username'] = $username;
					$_SESSION['login_string'] = hash('sha512', $password . $user_browser);
					// Login successful.
					return true;
				} else {
					// Password is not correct
					// We record this attempt in the database
					$now = time();
					$db->exec("INSERT INTO login_attempts(user_id, time) VALUES ('$user_id', '$now')");
					return false;
				}
			}
		} else {
			// No user exists.
			return false;
		}
	}
}
function checkbrute($user_id, $db) {
	// Get timestamp of current time 
	$now = time();

	// All login attempts are counted from the past 2 hours. 
	$valid_attempts = $now - (2 * 60 * 60);

	if ($q = $db->prepare("SELECT time FROM login_attempts WHERE user_id = ? AND time > '$valid_attempts'")) {
		$q->bindValue(1, $user_id, PDO::PARAM_INT);

		// Execute the prepared query. 
		$q->execute();

		// If there have been more than 10 failed logins 
		$nr = $q->rowCount();
		if ($nr > 10) {
			return true;
		} else {
			return false;
		}
	}
}
function login_check($db) {
	// Check if all session variables are set 
	if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['login_string'])) {

		$user_id = $_SESSION['user_id'];
		$login_string = $_SESSION['login_string'];
		$username = $_SESSION['username'];

		// Get the user-agent string of the user.
		$user_browser = $_SERVER['HTTP_USER_AGENT'];

		if ($q = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1")) {
			// Bind "$user_id" to parameter. 
			$q->bindValue(1, $user_id, PDO::PARAM_INT);
			$q->execute();   // Execute the prepared query.
			$nr = $q->rowCount();
			if ($nr == 1) {
				// If the user exists get variables from result.
				$r = $q->fetch(PDO::FETCH_ASSOC);
				$password = $r['password'];
				$login_check = hash('sha512', $password . $user_browser);

				if ($login_check == $login_string) {
					// Logged In!!!! 
					return true;
				} else {
					// Not logged in 
					return false;
				}
			} else {
				// Not logged in 
				return false;
			}
		} else {
			// Not logged in 
			return false;
		}
	} else {
		// Not logged in 
		return false;
	}
}
function esc_url($url) {

	if ('' == $url) {
		return $url;
	}

	$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

	$strip = array('%0d', '%0a', '%0D', '%0A');
	$url = (string) $url;

	$count = 1;
	while ($count) {
		$url = str_replace($strip, '', $url, $count);
	}

	$url = str_replace(';//', '://', $url);

	$url = htmlentities($url);

	$url = str_replace('&amp;', '&#038;', $url);
	$url = str_replace("'", '&#039;', $url);

	if ($url[0] !== '/') {
		// We're only interested in relative links from $_SERVER['PHP_SELF']
		return '';
	} else {
		return $url;
	}
}
function d($text) {
	file_put_contents('debug.txt', date('Y-m-d H:i:s'), FILE_APPEND);
	file_put_contents('debug.txt', ":\r\n", FILE_APPEND);
	file_put_contents('debug.txt', $text, FILE_APPEND);
	file_put_contents('debug.txt', "\r\n\r\n", FILE_APPEND);
}

// function p($v) {
	// print "<pre>";
	// print_r($v);
	// print "</pre>";
// }

function p($a) {
    // $backtrace = debug_backtrace()[0];		//not working
	$backtracearr = debug_backtrace();
	$backtrace = $backtracearr[0];
    $fh = fopen($backtrace['file'], 'r');
    $line = 0;
    while (++$line <= $backtrace['line']) {
        $code = fgets($fh);
    }
    fclose($fh);
    preg_match('/' . __FUNCTION__ . '\s*\((.*)\)\s*;/u', $code, $name);
    echo '<pre>'.trim($name[1]).": ";
    // var_export($a);
	print_r($a);
    echo '</pre>';
}



$bankaccounts = array(
	'lloyds' => 'Lloyds',
	'securetrading' => 'SecureTrading',
	'eway' => 'eWay',
	'landz' => 'London & Zurich',
	'erms' => 'eRMS'
);

if (isset($_POST)) {
	$post = $_POST;
}

if(!function_exists('murmurhash')) {
    function murmurhash($key,$seed = 0) {
        $m = 0x5bd1e995;
        $r = 24;
        $len = strlen($key);
        $h = $seed ^ $len;
        $o = 0;
        
        while($len >= 4) {
            $k = ord($key[$o]) | (ord($key[$o+1]) << 8) | (ord($key[$o+2]) << 16) | (ord($key[$o+3]) << 24);
            $k = ($k * $m) & 4294967295;
            $k = ($k ^ ($k >> $r)) & 4294967295;
            $k = ($k * $m) & 4294967295;
 
            $h = ($h * $m) & 4294967295;
            $h = ($h ^ $k) & 4294967295;
 
            $o += 4;
            $len -= 4;
        }
 
        $data = substr($key,0 - $len,$len);
    
        switch($len) {
            case 3: $h = ($h ^ (ord($data[2]) << 16)) & 4294967295;
            case 2: $h = ($h ^ (ord($data[1]) << 8)) & 4294967295;
            case 1: $h = ($h ^ (ord($data[0]))) & 4294967295;
            $h = ($h * $m) & 4294967295;
        };
        $h = ($h ^ ($h >> 13)) & 4294967295;
        $h = ($h * $m) & 4294967295;
        $h = ($h ^ ($h >> 15)) & 4294967295;
    
     return $h;
    }
}


// header ('Content-type: text/html; charset=utf-8');

?>