<?php
	include_once 'config.php';

	$conn = new mysqli(DB_HOST, DB_USER, "", DB_NAME, DB_PORT);

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	function register() {
		global $conn;
		if(isset($_POST['register'])) {
			$email = $_POST['email'];
			$fname = $_POST['first-name'];
			$lname = $_POST['last-name'];
			$pass = $_POST['password'];
			$role = $_POST['role'];
			$profile = 'default.jpg';

			try {
				// Cleansing inputted data
				if(empty($email) || empty($fname) || empty($lname) ){
					throw new Exception("Please fill in all the required fields.");
				}
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					throw new Exception("Invalid email format.");
				}
				if(strlen($pass) < 8) {
					throw new Exception("Password length does not match!");
				}
				if (!preg_match("/^[A-Za-z0-9-_!]{8,12}$/", $pass)) {
					throw new Exception("Password must be at least 8 characters long and contain a mix of letters, numbers, and special characters.");
				}

				$conn -> begin_transaction();

				// Checking if email already exist
				$stmt = $conn->prepare("SELECT * FROM `users` WHERE `email` = ?");
				$stmt->bind_param("s", $email);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows > 0) {
					throw new Exception("The email address is already registered.");
				}

				$password = password_hash($pass, PASSWORD_DEFAULT);

				if ($password === false) {
					throw new Exception("We encountered an issue while creating your account. Please try again.");
				}

				// Adding Details to the User Table
				$stmt = $conn->prepare("INSERT INTO `users`(`password`,`first_name`,`last_name`,`email`,`role`) VALUES(?,?,?,?,?)");
				$stmt->bind_param("sssss", $password, $fname, $lname, $email, $role);

				if($stmt->execute()) {

					$conn->commit();

					$_SESSION['role'] = $role;
					$_SESSION["msg"] = "Account was succesfully 2d. Please Log In";
					header("location: login");
					exit();
				}
			}
			catch(mysqli_sql_exception $e) {
				log_error(date("Y-m-d H:i:s") . " SQL Error: " . $e->getMessage(), 'database_error.log');

				$_SESSION['msg'] = "Something went wrong. Please try again later.";
			 	header("location: " . ROOT_PATH . 'login');
				exit();
			}
			catch(Exception $e) {
				$conn->rollback();
				log_error($e->getMessage(), 'error.log');
				
				if(!isset($_SESSION['msg']))
					$_SESSION['msg'] = "Unexpected Error Occured. Please contact System Administrator.";
				header("location: /register");
				exit();
			}
			finally{
				$stmt->close();
				$conn->close();
			}
		}
	}//End Registration Block

	function login() {
		global $conn;
		if(isset($_POST['signin'])) {
			$email = $_POST['email'];
			$pass = $_POST['password'];

			if(empty($email) || empty($pass)) {
				$_SESSION['msg'] = "Login Credentials should not be empty!";
				header("location: login");
				exit();
			}

			try {
				$stmt = $conn->prepare("SELECT `uid`, `email`, `password`, `first_name`, `last_name`, `role` FROM `users` WHERE `email` = ?");
				$stmt->bind_param("s", $email);
				$stmt->execute();
				$result = $stmt->get_result();

				if($result->num_rows > 0) {
					$user = $result->fetch_assoc();

					if (password_verify($pass, $user['password'])) {
						
						// Set session information
						$_SESSION['user'] = $user['uid'];
						$_SESSION['name'] = $user['last_name'].', '.$user['first_name'];
						$_SESSION['email'] = $user['email'];
						setcookie('role', $user['role'], time() + (24 * 60 * 60), "/", "", true, true);

						//Check if remember was passed
						if(isset($_POST['remember']) && $_POST['remember'] == 'on') {
							$token = bin2hex(random_bytes(16));
							setcookie('remember_me', $token, time() + (7 * 24 * 60 * 60), "/", "", true, true);

							$hashed_token = hash('sha256', $token);

							$stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, expiration_date) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
							$stmt->bind_param("is",$user['uid'], $hashed_token);
							
							if(!$stmt->execute()) {
								log_error($stmt->error, 'database_error.log');
							}
						}

						// Update Last Login column
						$stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE uid = ?");
						$stmt->bind_param("i",$user['uid']);
						if(!$stmt->execute()) {
								log_error($stmt->error, 'database_error.log');
						}

						// Redirect to user dashboard
						$conn->close();
						header("location: dashboard");
						exit();

					}
					$_SESSION['msg'] = "Invalid Login Credentials, Please Try Again";
					header("location: login");
					exit();
				} else {
					$_SESSION['msg'] = "No Account Found";
					header("location: login");
					exit();
				}
			}
			catch(mysqli_sql_exception $e) {
				log_error(date("Y-m-d H:i:s") . " SQL Error: " . $e->getMessage(), 'database_error.log');
				$_SESSION['msg'] = "Something went wrong. Please try again later.";
				header("location: login");
				exit();
			}
			catch(Exception $e) {
				log_error(date("Y-m-d H:i:s") . " SQL Error: " . $e->getMessage(), 'error.log');
				$_SESSION['msg'] = "Unknown error occured, Please contact System Administrator!";
				header("location: login");
				exit();
			}
			finally {
				if ($conn) {
					$conn->close();
				}
			}
		}
	}//End of login block

	function logout() {
		if(isset($_SESSION['user'])) {
			if(isset($_COOKIE['role'])) {
				unset($_COOKIE['role']);
				setcookie('role','',time() - 3600, '/');
			}
			if(isset($_COOKIE['remember_me'])) {
				unset($_COOKIE['remember_me']);
				setcookie('remember_me','',time() - 3600, '/');
			}
			session_unset();
			session_destroy();

			$_SESSION['msg'] = "You have successfully logout";
			header("location: login");
			exit();
		}
	}//End of logout block	
?>