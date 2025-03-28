<?php
	include_once 'config.php';

	$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}

	function register() {
		global $conn;
		
		if(isset($_POST['register'])) {
			try {
				// Get form data
				$email = $_POST['email'] ?? '';
				$fname = $_POST['first-name'] ?? '';
				$lname = $_POST['last-name'] ?? '';
				$pass = $_POST['password'] ?? '';
				$cpass = $_POST['confirm-password'] ?? '';
				$role = $_POST['role'] ?? '';
				
				// Input validation
				if(empty($email) || empty($fname) || empty($lname) || empty($pass) || empty($cpass)){
					$_SESSION['msg'] = "Please fill in all the required fields.";
					throw new Exception("Empty Fields");
				}
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$_SESSION['msg'] = "Invalid email format.";
					throw new Exception("Invalid Email");
				}
				if($pass !== $cpass) {
					$_SESSION['msg'] = "Password does not match.";
					throw new Exception("Mismatch Password");
				}
				if(strlen($pass) < 8) {
					$_SESSION['msg'] = "Password length does not match!";
					throw new Exception("Password complexity failed(length)");
				}
				if (!preg_match("/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*-_!]))[A-Za-z\d*-_!]{8,16}$/", $pass)) {
					$_SESSION['msg'] = "Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.";
					throw new Exception("Password complexity failed(uniqueness)");
				}
				if(!in_array($role, ['TECHGURU', 'TECHKID'])) {
					$_SESSION['msg'] = "Invalid user role selected.";
					throw new Exception("Invalid Role");
				}

				$conn->begin_transaction();

				// Check for existing email
				$stmt = $conn->prepare("SELECT uid FROM users WHERE email = ?");
				$stmt->bind_param("s", $email);
				$stmt->execute();
				if ($stmt->get_result()->num_rows > 0) {
					$_SESSION['msg'] = "The email address is already registered.";
					throw new Exception("User already exists");
				}

				// Hash password
				$password = password_hash($pass, PASSWORD_DEFAULT);
				if ($password === false) {
					$_SESSION['msg'] = "We encountered an issue while creating your account. Please try again.";
					throw new Exception("Password hashing failed");
				}

				// Create user account
				$stmt = $conn->prepare("INSERT INTO users (password, email, role, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
				$stmt->bind_param("sssss", $password, $email, $role, $fname, $lname);
				
				if($stmt->execute()) {
					$user_id = $conn->insert_id;
					$mail = getMailerInstance();
					$token = generateVerificationToken($user_id);
					sendVerificationEmail($mail, $email, $token, $fname);
					
					$conn->commit();
					$_SESSION["msg"] = "Account was successfully created. Please check your email to verify your account.";
					header("location: ".BASE."login");
					exit();
				} else {
					throw new Exception("Failed to create user account");
				}
			}
			catch(mysqli_sql_exception $e) {
				$conn->rollback();
				log_error("SQL Error in registration: " . $e->getMessage(), 'database_error.log');
				$_SESSION['msg'] = "Something went wrong. Please try again later.";
			 	header("location: ".BASE."signup");
				exit();
			}
			catch(Exception $e) {
				$conn->rollback();
				log_error("Registration error: " . $e->getMessage(), 'error.log');
				if(!isset($_SESSION['msg'])) {
					$_SESSION['msg'] = "An unexpected error occurred. Please try again.";
				}
				header("location: ".BASE."register");
				exit();
			}
			finally {
				if(isset($stmt)) {
					$stmt->close();
				}
			}
		} else {
			log_error("Invalid registration attempt - missing register field");
			$_SESSION['msg'] = "Invalid registration attempt";
			header("location: ".BASE."register");
			exit();
		}
	}//End Registration Block

	function login() {
		global $conn;
		$response = array('success' => false, 'message' => '');

		if(isset($_POST['email']) && isset($_POST['password'])) {
			$email = mysqli_real_escape_string($conn, $_POST['email']);
			$pass = mysqli_real_escape_string($conn, $_POST['password']);

			// Check if email exists
			$query = "SELECT * FROM users WHERE email = ?";
			$stmt = $conn->prepare($query);
			$stmt->bind_param("s", $email);
			$stmt->execute();
			$result = $stmt->get_result();

			if($result->num_rows > 0) {
				$user = $result->fetch_assoc();

				// Verify password
				if(password_verify($pass, $user['password'])) {
					// Check if user is verified
					if($user['is_verified'] == 0) {
						$_SESSION['msg'] = "Your account is not verified. Please check your email for a verification link.";
						$_SESSION['email'] = $email;
						$_SESSION['user'] = $user['uid'];

						// Sending of verification for login
						$mail = getMailerInstance();
						$token = generateVerificationCode($user['uid']);
						sendVerificationCode($mail, $email, $token);


						header("Location: verify");
						exit();
					}

					// Check if user is active
					if($user['status'] == 0) {
						$_SESSION['msg'] = "Your account has been deactivated. Please contact support.";
						header("Location: login");
						exit();
					}

					if(isset($_POST['remember']) && $_POST['remember'] == 'on') {
						$token = bin2hex(random_bytes(16));
						setcookie('remember_me', $token, time() + (7 * 24 * 60 * 60), BASE, "", true, true);

						$hashed_token = password_hash($token, PASSWORD_BCRYPT);

						$stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, expiration_date, type) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), 'remember_me')");
						$stmt->bind_param("is",$user['uid'], $hashed_token);
						
						if(!$stmt->execute()) {
							log_error($stmt->error, 'database_error.log');
						}
					}
					
					// Set session variables
					$_SESSION['user'] = $user['uid'];
					$_SESSION['email'] = $user['email'];
					$_SESSION['role'] = $user['role'];
					$_SESSION['first_name'] = $user['first_name'];
					$_SESSION['last_name'] = $user['last_name'];
					$_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
					$_SESSION['address'] = $user['address'];
					$_SESSION['phone'] = $user['contact_number'];
					$_SESSION['profile'] = USER_IMG . ($user['profile_picture'] ?? 'default.jpg');
					$_SESSION['rating'] = $user['rating'] ?? 'Undecided';

					if(empty($user['role'])) {
						$deleteStmt = $conn->prepare("DELETE FROM users WHERE email = ?");
						$deleteStmt->bind_param("s", $user['email']);
						$deleteStmt->execute();
						$deleteStmt->close();
						header("Location: user-logout");
						exit();
					}
					
					// Update last login
					$updateQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE uid = ?";
					$updateStmt = $conn->prepare($updateQuery);
					$updateStmt->bind_param("i", $user['uid']);
					$updateStmt->execute();
					$updateStmt->close();



					header("Location: dashboard");
					exit();
				} else {
					$_SESSION['msg'] = "Invalid password";
					header("Location: login");
					exit();
				}
			} else {
				$_SESSION['msg'] = "Email not found";
				header("Location: login");
				exit();
			}
			$stmt->close();
		} else {
			$_SESSION['msg'] = "Please fill in all fields";
			header("Location: login");
			exit();
		}
	}//

	function logout() {
		global $conn;
		if(isset($_SESSION['user'])) {
			if(isset($_COOKIE['remember_me'])) {
				// Remove cookie from the client-side
				unset($_COOKIE['remember_me']);
				setcookie('remember_me', '', time() - 3600, BASE, "", true, true);

				// Remove token from the server-side
				try{
					$conn -> begin_transaction();

					$token_id = tokenVerifier($token);

					if(isset($token_id)) {
						$stmt = $conn->prepare("DELETE FROM `login_tokens` WHERE `token_id` = ?");
						$stmt->bind_param("i", $token_id);
						if($stmt->execute()) {
							$conn->commit();
						}
						else {
							$conn->rollback();
							throw new Exception("Error Encountered during deletion");
						}
					}
				}
				catch (Exception $e) {
					$conn->rollback();
					log_error(" Error on line " . $e->getLine() . " in " . $e->getFile() . ": SQL Error: " . $e->getMessage(), 'database_error.log');
				}
				finally {
					if (isset($stmt)) $stmt->close();
					$conn->close();
				}
			}
			session_unset();
			session_destroy();

			// Redirect after processing logout logic
			$_SESSION['msg'] = "You have successfully logout";
			header("location: ".BASE."login");
			exit();
		}
		else {
			$_SESSION['msg'] = "Invalid Action";
			header("location: ".BASE."login");	
		}
	}//End of logout block	

    function getItemCountByTable($table,$role = null) {
        $valid_tables = ['users', 'course', 'transactions'];
        global $conn;

        if(in_array($table, $valid_tables)) {
            if($table == 'course' || $table == 'transactions') {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table`");
                $stmt->execute();
                $result = $stmt->get_result();

                return $result->fetch_row()[0];
            }
            if($table == 'users') {
                if(isset($role)) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table` WHERE `role` = ?");
                    $stmt->bind_param("s",$role);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    return $result->fetch_row()[0];
                }
                $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table`");
                $stmt->execute();
                $result = $stmt->get_result();

                return $result->fetch_row()[0];
            }
        }
        return null;
    }
    ?>