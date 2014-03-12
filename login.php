<?php
	require_once 'secSessionStart.php';
	class LoginManager()
	{
		private $dbConnection = null;
		private $jsonReturnArray = null;
		public function __construct()
		{
			doLogin();
		}
		private function createConnection()
		{
			$connString = 'mysql:host=localhost;dbname=vPoker';
			$uname = root;
			$pass = 'v3#D4g7';
			try
			{
				$dbConnection = new PDO($connString, $uname, $pass);
			}
			catch (PDOException $e)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'Failed to connect to database.');
				return false;
			}
			return true;
		}
		private function checkLoginDataOk()
		{
			if (!empty($_POST['uname']) && !empty($_POST['pass']))
			{
				return true;
			}
			$jsonReturnArray = array('success' => false, 'feedback' => 'Username or password not valid.');
			return false;
		}
		private function tryLogin()
		{
			$username = htmlentities($_POST["uname"], ENT_QUOTES);
			$password = $_POST["pass"];
			$statement = $dbConnection->prepare("SELECT username, passwordhash, salt FROM users WHERE username = ?");
			$sRetVal = $statement->execute(array($username));
			if($sRetVal === false)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'A database error occured.');
				return false;
			}
			$row = $statement->fetchObject();
			if (!$row)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'Username or password not valid.');
				return false;
			}
			if (!(password_verify($password.$row->salt, $row->passwordHash)))
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'Username or password not valid.');
				return false;
			}
			$_SESSION['uname'] = $row->username;
			$_SESSION['loggedin'] = true;
			return true;
		}
		private function doLogin()
		{
			sec_session_start();
			if (createConnection())
			{
				if (checkLoginDataOk())
				{
					if (tryLogin())
					{
						$jsonReturnArray = array('success' => true, 'feedback' => 'Login successful.');
					}
				}
			}
			echo json_encode($jsonReturnArray);
		}
	}
	$lmgr = new LoginManager();
?>