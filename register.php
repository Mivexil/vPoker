<?php
	class registrationManager
	{
		private $dbConnection = null;
		private $jsonReturnArray = null;
		public function __construct()
		{
			$this->doRegister();
		}
		private function createConnection()
		{
			$connString = 'mysql:host=localhost;dbname=vPoker';
			$uname = root;
			$pass = 'v3#D4g7';
			try
			{
				$this->dbConnection = new PDO($connString, $uname, $pass);
			}
			catch (PDOException $e)
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Failed to connect to database.');
				return false;
			}
			return true;
		}
		private function checkRegistrationData()
		{
			if (!isset($_POST["register"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'No data submitted.');
			}
			if (!empty ($_POST["uname"]) &&
				strlen($_POST["uname"]) <= 64 &&
				strlen($_POST["uname"]) >= 2 &&
				preg_match('/^[a-z\d]{2,64}$/i', $_POST["uname"]) &&
				!empty($_POST["email"]) &&
				strlen($_POST["email"]) <= 96 &&
				filter_var($_POST["email"], FILTER_VALIDATE_EMAIL) &&
				!empty($_POST["newpass"]) &&
				!empty($_POST["repeatpass"]) &&
				($_POST["newpass"] === $_POST["repeatpass"]) &&
				!empty($_POST["btcaddr"]) &&
				preg_match('/^[a-z\d]{2,64}$/i', $_POST["btcaddr"]) &&
				strlen($_POST["btcaddr"]) <= 34)
			{return true;}
			elseif (empty($_POST["uname"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Username not provided.');
			}
			elseif (empty($_POST["email"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'E-mail not provided.');
			}
			elseif (empty($_POST["newpass"]) || empty($_POST["repeatpass"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Password not provided.');
			}
			elseif (empty($_POST["btcaddr"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'PMC address not provided.');
			}
			elseif ($_POST["newpass"] !== $_POST["repeatpass"])
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Passwords do not match.');
			}
			elseif (strlen($_POST["uname"]) > 64 || strlen($_POST["uname"]) < 2)
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Username cannot be longer than 64 or shorter than 2 characters.');
			}
			elseif (strlen($_POST["email"]) > 96)
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Mail address cannot be longer than 96 characters.');
			}
			elseif (strlen($_POST["btcaddr"]) > 34)
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'PMC address contains too many characters.');
			}
			elseif(!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Mail address is not valid.');
			}
			elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST["uname"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'Username contains invalid characters.');
			}
			elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST["btcaddr"]))
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'PMC address contains invalid characters.');
			}
			else
			{
				$this->jsonReturnArray = array('success' => false, 'feedback' => 'An unknown error occured.');
			}
			return false;
		}
		private function createUser()
		{
			$username = htmlentities($_POST["uname"], ENT_QUOTES);
			$email = htmlentities($_POST["email"], ENT_QUOTES);
			$btcaddr = htmlentities($_POST["btcaddr"], ENT_QUOTES);
			$password = $_POST["newpass"];
			$salt = bin2hex(openssl_random_pseudo_bytes(16));
			$pwdhash = password_hash($password.$salt, PASSWORD_DEFAULT);
			$selectStatement = $this->dbConnection->prepare('SELECT COUNT(*) AS cnt FROM users WHERE username = ? OR email = ?');
			$ssRetVal = $selectStatement->execute(array($username, $email));
			if (!ssRetVal)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'A database error occured.');
				return false;
			}
			$selectRow = $selectStatement->fetchObject();
			if (!$selectRow)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'A database error occured.');
				return false;
			}
			if ($selectRow.cnt != 0)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'This username/email is already taken.');
				return false;
			}
			$statement = $this->dbConnection->prepare('INSERT INTO users(username, email, btcaddress, passwordhash, salt) values(?, ?, ?, ?, ?)');
			$rtrn = $statement->execute(array($username, $email, $btcaddr, $pwdhash, $salt));
			if (!$rtrn)
			{
				$jsonReturnArray = array('success' => false, 'feedback' => 'A database error occured.');
				return false;
			}
			return true;
			//TODO: call to bitcoind to generate an account?
		}
		public function doRegister()
		{
			if ($this->createConnection())
			{
				if ($this->checkRegistrationData())
				{
					if ($this->createUser())
					{
						$this->jsonReturnArray = array('success' => true, 'feedback' => 'Registration completed successfully.');
					}
				}
			}
			echo json_encode($this->jsonReturnArray);
		}
	}
	$rmgr = new RegistrationManager();
?>