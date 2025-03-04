<?php
	include_once 'config.php';

	$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

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
					$_SESSION['msg'] = "Please fill in all the required fields.";
					throw new Exception("Empty Fields");
				}
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$_SESSION['msg'] = "Invalid email format.";
					throw new Exception("Invalid Email");
				}
				if(strlen($pass) < 8) {
					$_SESSION['msg'] = "Password length does not match!";
					throw new Exception("Password complexity failed(length)");
				}
				if (!preg_match("/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*-_!]))[A-Za-z\d*-_!]{8,16}$/", $pass)) {
					$_SESSION['msg'] = "Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.";
					throw new Exception("Password complexity failed(uniqueness)");
				}

				$conn -> begin_transaction();

				// Checking if email already exist
				$stmt = $conn->prepare("SELECT * FROM `users` WHERE `email` = ?");
				$stmt->bind_param("s", $email);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows > 0) {
					$_SESSION['msg'] = "The email address is already registered.";
					throw new Exception("User already exist");
				}

				$password = password_hash($pass, PASSWORD_DEFAULT);

				if ($password === false) {
					$_SESSION['msg'] = "We encountered an issue while creating your account. Please try again.";
					throw new Exception("Hashing Error!");
				}

				// Adding Details to the User Table
				$stmt = $conn->prepare("INSERT INTO `users`(`password`,`first_name`,`last_name`,`email`,`role`) VALUES(?,?,?,?,?)");
				$stmt->bind_param("sssss", $password, $fname, $lname, $email, $role);

				if($stmt->execute()) {

					$conn->commit();

					$_SESSION['role'] = $role;
					$_SESSION["msg"] = "Account was succesfully created, Please Log In";
					header("location: login");
					exit();
				}
			}
			catch(mysqli_sql_exception $e) {
				$conn->rollback();
				log_error(date("Y-m-d H:i:s") . " SQL Error: " . $e->getMessage(), 'database_error.log');

				$_SESSION['msg'] = "Something went wrong. Please try again later.";
			 	header("location: login");
				exit();
			}
			catch(Exception $e) {
				$conn->rollback();
				log_error($e->getMessage(), 'error.log');
				
				if(!isset($_SESSION['msg']))
					$_SESSION['msg'] = "Unexpected Error Occured. Please contact System Administrator.";
				header("location: register");
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
				$stmt = $conn->prepare("SELECT `uid`, `email`, `password`, `first_name`, `last_name`, `role`, `profile_picture` FROM `users` WHERE `email` = ?");
				$stmt->bind_param("s", $email);
				$stmt->execute();
				$result = $stmt->get_result();

				if($result->num_rows > 0) {
					$user = $result->fetch_assoc();

					if (password_verify($pass, $user['password'])) {
						
						// Set session information
						$_SESSION['user'] = $user['uid'];
						$_SESSION['name'] = $user['first_name'].' '.$user['last_name'];
						$_SESSION['first-name'] = $user['first_name']
						$_SESSION['email'] = $user['email'];
						$_SESSION['profile'] = USER_IMG.$user['profile_picture'];
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
				$stmt->close();
				$conn->close();
			}
		}
	}//End of login block

	function logout() {
		global $conn;
		if(isset($_SESSION['user'])) {
			if(isset($_COOKIE['role'])) {
				unset($_COOKIE['role']);
				setcookie('role','',time() - 3600, '/');
			}
			if(isset($_COOKIE['remember_me'])) {
				$token = $_COOKIE['remember_me']; //get cookie value

				// Remove cookie from the client-side
				unset($_COOKIE['remember_me']);
				setcookie('remember_me','',time() - 3600, '/');

				// Remove token from the server-side
				try{
					$conn -> begin_transaction();
					$stmt = $conn->prepare("DELETE FROM `login_tokens` WHERE `token` = ?");
					$stmt->bind_param("s", $token);
					if($stmt->execute()) {
						$conn->commit();
					}
					else {
						$conn->rollback();
					}	
				}
				catch (Exception $e) {
					$conn->rollback();
					echo "Failed to delete token: " . $e->getMessage();
				}
				finally {
					$stmt->close();
					$conn->close();
				}
			}
			session_unset();
			session_destroy();

			// Redirect after processing logout logic
			$_SESSION['msg'] = "You have successfully logout";
			header("location: login");
			exit();
		}
	}//End of logout block	

	function add_class() {
		global $conn;
		if (isset($_COOKIE['role']) && in_array($_COOKIE['role'], ['TECHGURU', 'ADMIN'])) {
			if(isset($_POST['add-class'])) {
				$subject = $_POST['subject'];
				$class_name = $_POST['class-name'];
				$class_dec = $_POST['class-desc'];
				$tutor = $_SESSION['user'];
				$start = $_POST['start-date'];
				$end = $_POST['end-date'];
				$limit = $_POST['limit'];
				$free = $_POST['free'];
				$price = $_POST['price'];
				$pic = $_POST['thumbnail'];

				try {
					// Cleaning of Data
					if(empty($subject) || empty($class_name) || empty($class_dec) || empty($tutor) || empty($start) || empty($end) ){
						$_SESSION['msg'] = "Please fill in all the required fields.";
						throw new Exception("Empty Fields");
					}

					$conn -> begin_transaction();
					$stmt = $conn->prepare("INSERT INTO class(subject_id, class_name, class_desc, tutor_id, start_date, end_date, class_size, is_free, price, thumbnail) VALUES(??????????)");
					$stmt->bind_param("ississiids", $subject,$class_name,$class_dec,$tutor,$start,$end,$limit,$free,$price,$pic);
					
					if($stmt->execute()) {
						$conn->commit();
						$_SESSION["msg"] = "Class Information was successfully added.";
						header("location: dashboard");
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
					log_error($e->getMessage(), 'error.log');
					header("location: dashboard");
					exit();
				}
				finally {
					$conn->rollback();
					$stmt->close();
					$conn->close();
				}
			}
		}
		$_SESSION['msg'] = "Invalid action";
		header("location: dashboard");
		exit();
	}

	function getStudents() {
		global $conn;

		$stmt = $conn->prepare("SELECT * FROM ?");
		$stmt->bind_param("s",$table);
		$stmt->execute();
		$result = $stmt->get_result();

		if($result->num_rows > 0) {
			$data = $result->fetch_assoc();
		}
	}
?>