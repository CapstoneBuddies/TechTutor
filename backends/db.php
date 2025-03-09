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
			$cpass = $_POST['confirm-password'];
			$role = $_POST['role'];
			$profile = 'default.jpg';

			try {
				// Cleansing inputted data
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
				$stmt = $conn->prepare("INSERT INTO `users`(`password`,`email`,`role`) VALUES(?,?,?)");
				$stmt->bind_param("sss", $password, $email, $role);

				if($stmt->execute()) {
					$conn->commit();
					$stmt = $conn->prepare("SELECT uid FROM `users` WHERE `email` = ?");
					$stmt->bind_param("s", $email);
					$stmt->execute();
					$result = $stmt->get_result()->fetch_assoc();

					if ($result) { // Ensure a user was found before proceeding
    					$user_id = $result['uid'];
    					$stmt = $conn->prepare("INSERT INTO `user_details`(`user_id`,`first_name`,`last_name`) VALUES(?,?,?)");
    					$stmt->bind_param("iss", $user_id, $fname, $lname);
    					if($stmt->execute()) {
    						$mail = getMailerInstance();
							// Sending of verification
							$token = generateVerificationToken($user_id);
							sendVerificationEmail($mail, $email, $token, $fname);
    					}
    				}
					$_SESSION["msg"] = "Account was succesfully created, Please Log In";
					header("location: ".BASE."login");
					exit();
				}
			}
			catch(mysqli_sql_exception $e) {
				$conn->rollback();
				log_error("Error on line " . $e->getLine() . " in " . $e->getFile() . ": SQL Error: " . $e->getMessage(), 'database_error.log');

				$_SESSION['msg'] = "Something went wrong. Please try again later.";
			 	header("location: ".BASE."register");
				exit();
			}
			catch(Exception $e) {
				$conn->rollback();
				log_error("Error on line " . $e->getLine() . " in " . $e->getFile() . ": ".$e->getMessage(), 'error.log');
				
				if(!isset($_SESSION['msg']))
					$_SESSION['msg'] = "Unexpected Error Occured. Please contact System Administrator.";
				header("location: ".BASE."register");
				exit();
			}
			finally{
				$stmt->close();
				$conn->close();
			}
		}
		else {
			$_SESSION['msg'] = "Invalid Action";
			header("location: ".BASE."login");
		}
	}//End Registration Block

	function login() {
		global $conn;
		$response = array('success' => false, 'message' => '');

		if(isset($_POST['email']) && isset($_POST['password'])) {
			$email = mysqli_real_escape_string($conn, $_POST['email']);
			$pass = mysqli_real_escape_string($conn, $_POST['password']);

			// Check if email exists
			$query = "SELECT u.*, ud.first_name, ud.last_name, ud.profile_picture, ud.address, ud.contact_number 
					FROM users u 
					LEFT JOIN user_details ud ON u.uid = ud.user_id 
					WHERE u.email = ?";
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

						$stmt = $conn->prepare("INSERT INTO login_tokens (user_id, token, remember_expiration_date, type) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), 'remember_me')");
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
					setcookie('role', $user['role'], time() + (3 * 60 * 60), "/", "", true, true);
					
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

					$token_id = rememberTokenVerifier($token);

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
	function updateProfile() {
		global $conn;
        $response = array('success' => false, 'message' => '');

        if (!isset($_SESSION['user'])) {
            $response['message'] = 'Not authorized';
            echo json_encode($response);
            exit();
        }

        if (!isset($_POST['firstName']) || !isset($_POST['lastName'])) {
            $response['message'] = 'Required fields are missing';
            echo json_encode($response);
            exit();
        }

        $userId = $_SESSION['user'];
        $firstName = trim($_POST['firstName']);
        $lastName = trim($_POST['lastName']);
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $countryCode = isset($_POST['countryCode']) ? trim($_POST['countryCode']) : '+63';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

        // Validate first name and last name
        if (strlen($firstName) < 2 || strlen($firstName) > 50) {
            $response['message'] = 'First name must be between 2 and 50 characters';
            echo json_encode($response);
            exit();
        }

        if (strlen($lastName) < 2 || strlen($lastName) > 50) {
            $response['message'] = 'Last name must be between 2 and 50 characters';
            echo json_encode($response);
            exit();
        }

        // Validate phone number if provided
        if (!empty($phone)) {
            // Remove any existing hyphens for validation
            $cleanPhone = str_replace('-', '', $phone);
            
            // Check if it's exactly 10 digits
            if (!preg_match('/^[0-9]{10}$/', $cleanPhone)) {
                $response['message'] = 'Phone number must be exactly 10 digits';
                echo json_encode($response);
                exit();
            }

            // Check if country code is valid (starts with + and has 1-3 digits)
            if (!preg_match('/^\+[0-9]{1,3}$/', $countryCode)) {
                $response['message'] = 'Invalid country code';
                echo json_encode($response);
                exit();
            }

            // Format phone number with hyphens and country code
            $phone = $countryCode . substr($cleanPhone, 0, 3) . '-' . 
                    substr($cleanPhone, 3, 3) . '-' . 
                    substr($cleanPhone, 6);
        }

        // Validate address if provided
        if (!empty($address) && strlen($address) > 100) {
            $response['message'] = 'Address must not exceed 100 characters';
            echo json_encode($response);
            exit();
        }

        // Update user details
        $query = "UPDATE user_details SET first_name = ?, last_name = ?, address = ?, contact_number = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssi", $firstName, $lastName, $address, $phone, $userId);

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['name'] = $firstName . ' ' . $lastName;
            $_SESSION['address'] = $address;
            $_SESSION['phone'] = $phone;

            $response['success'] = true;
            $response['message'] = 'Profile updated successfully';
        } else {
            error_log("Profile update failed: " . $stmt->error);
            $response['message'] = 'Failed to update profile: ' . $stmt->error;
        }

        $stmt->close();
        echo json_encode($response);
        exit();
    }//

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
					$stmt = $conn->prepare("INSERT INTO class(subject_id, class_name, class_desc, tutor_id, start_date, end_date, class_size, is_free, price, thumbnail) VALUES(?,?,?,?,?,?,?,?,?,?)");
					$stmt->bind_param("ississiids", $subject,$class_name,$class_dec,$tutor,$start,$end,$limit,$free,$price,$pic);
					
					if($stmt->execute()) {
						$conn->commit();
						$_SESSION["msg"] = "Class Information was successfully added.";
						header("location: ".BASE." dashboard");
						exit();
					}

				}
				catch(mysqli_sql_exception $e) {
					log_error(" Error on line " . $e->getLine() . " in " . $e->getFile() . ": SQL Error: " . $e->getMessage(), 'database_error.log');

					$_SESSION['msg'] = "Something went wrong. Please try again later.";
				 	header("location: ".BASE."login");
					exit();
				}
				catch(Exception $e) {
					log_error("Error on line " . $e->getLine() . " in " . $e->getFile() . ": ".$e->getMessage(), 'error.log');
					header("location: ".BASE."dashboard");
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
		header("location: ".BASE."dashboard");
		exit();
	}//End of add_class function


	/* UTILITIES */

	function getUserByRole($role, $page = 1, $limit = 8) {
	    global $conn;
	    
	    // Calculate the offset
	    $offset = ($page - 1) * $limit;

	    $stmt = $conn->prepare("SELECT 
			    `u`.`uid`, `ud`.`first_name`, `ud`.`last_name`, `u`.`email`, `co`.`course_name` AS `course`, CONCAT(`cl`.`start_date`, ' = ', `cl`.`end_date`) AS `schedule`, `u`.`status`,`u`.`last_login`
			FROM 
			    `users` `u`
			INNER JOIN
			    `user_details` `ud` ON `u`.`uid` = `ud`.`user_id`
			LEFT JOIN 
			    `class_assignment` `ca` ON `u`.`uid` = `ca`.`student_id`
			LEFT JOIN 
			    `class` `cl` ON `ca`.`class_id` = `cl`.`class_id` OR `u`.`uid` = `cl`.`tutor_id`
			LEFT JOIN 
			    `subject` `sub` ON `cl`.`subject_id` = `sub`.`subject_id`
			LEFT JOIN 
			    `course` `co` ON `sub`.`course_id` = `co`.`course_id`
			WHERE 
			    `u`.`role` = ? 
	        LIMIT ? OFFSET ?");

	    $stmt->bind_param("sii", $role, $limit, $offset);
	    $stmt->execute();
	    $result = $stmt->get_result();

	    if ($result->num_rows > 0) {
	        return $result->fetch_all(MYSQLI_ASSOC);
	    } else {
	        return [];
	    }
	}//End of getUsersByRole table
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

	function getUserData() {
		global $conn;

		$stmt = $conn->prepare("SELECT `first_name`, `last_name`, `profile_picture`, `address`, `contact_number` FROM `user_details` WHERE user_id = ?");
		$stmt->bind_param("i", $user);
		$stmt->execute();
		$result = $stmt->get_result();

		// Since there should only be one row, we can directly fetch the result
		if ($result->num_rows === 1) {
			// Fetch and return the associative array with the user details
			return $result->fetch_assoc();
		} 
		else {
			// If no user is found or more than 1 (shouldn't happen with unique user_id), return null
			return null;
		}
	}

	function updateUserData() {
		global $conn;

		if (!isset($_SESSION['user'])) { // Fix: Negate condition
		$_SESSION['msg'] = "An error occurred, Please login";
		header("Location: login");
		exit();
		}
		$user = $_SESSION['user'];

		try {
			$conn->begin_transaction(); // Start transaction

			if (isset($_POST['change-password'])) {
				$curpass = $_POST['current-password'];
				$pass = $_POST['new-password'];
				$cpass = $_POST['confirm-new-password'];

				$stmt = $conn->prepare("SELECT password FROM users WHERE uid = ?");
				$stmt->bind_param("i", $user);
				$stmt->execute();
				$result = $stmt->get_result();

				if ($row = $result->fetch_assoc()) {
					if (!password_verify($curpass, $row['password'])) {
						$_SESSION['msg'] = "Current password is incorrect.";
						throw new Exception("Incorrect current password");
					}
				} 
				else {
					$_SESSION['msg'] = "User not found.";
					throw new Exception("User ID not found in database");
				}

				if ($pass !== $cpass) {
					$_SESSION['msg'] = "Password does not match.";
					throw new Exception("Mismatch Password");
				}
				if (strlen($pass) < 8) {
					$_SESSION['msg'] = "Password must be at least 8 characters long.";
					throw new Exception("Password too short");
				}
				if (!preg_match("/^(?=(.*[A-Z]))(?=(.*[a-z]))(?=(.*\d))(?=(.*[*-_!]))[A-Za-z\d*-_!]{8,16}$/", $pass)) {
					$_SESSION['msg'] = "Password must be 8-16 characters long and contain a mix of letters, numbers, and special characters.";
					throw new Exception("Password complexity failed(uniqueness)");
				}

				$password = password_hash($pass, PASSWORD_DEFAULT);
				if (!$password) {
					$_SESSION['msg'] = "We encountered an issue while updating your password. Please try again.";
					throw new Exception("Hashing Error!");
				}

				$stmt = $conn->prepare("UPDATE users SET password = ? WHERE uid = ?");
				$stmt->bind_param("si", $password, $user);
				if (!$stmt->execute()) {
					throw new Exception("Error processing password update");
				}

				$conn->commit(); // Commit transaction
			}

			if (isset($_POST['change-profile'])) {
				$fname = $_POST['first-name'];
				$lname = $_POST['last-name'];
				$address = $_POST['address'];
				$contact_num = $_POST['contact-number'];
				$filename = ""; //get filename from input type="file"

				$stmt = $conn->prepare("UPDATE user_details SET `first_name` = ?, `last_name` = ?, `address` = ?, `contact_number` = ?, `profile_picture` = ? WHERE user_id = ?");
				$stmt->bind_param("sssssi",$fname, $lname, $address, $contact_num, $filename, $user);
				if (!$stmt->execute()) {
					throw new Exception("Error processing profile information update");
				}
				$conn->commit(); // Commit transaction
			}
		}
		catch (Exception $e) {
			$conn->rollback(); // Rollback on error
			log_error($e->getMessage(), 'database_error.log');
		}
		finally {
			if (isset($stmt)) {
				$stmt->close();
			}
			$conn->close();
			header("Location: dashboard/profile");
			exit();
		}
	}



	
	/* EXTRAS */
	function rememberTokenVerifier($hashed_token) {
		global $conn;
		try {
			$stmt = $conn->prepare("SELECT token_id, token FROM login_tokens WHERE type = 'remember_me' AND remember_expiration_date > NOW()");
		    $stmt->execute();
		    $result = $stmt->get_result();

			while($row = $result->fetch_assoc()) { 
				if (password_verify($hashed_token, $row['token'])) { 
					return $row['token_id'];
				}
			}
			throw new Exception("Token not Exist");
		}
		catch(Exception $e) {
			log_error("Error on line " . $e->getLine() . " in " . $e->getFile() . ": SQL Error: " . $e->getMessage(), 'database_error.log');
			return null;
		}
	}
	function generateVerificationToken($user_id) {
	    global $conn;
	    $token = bin2hex(random_bytes(32)); // Generate token
	    $hashed_token = password_hash($token, PASSWORD_DEFAULT); // Hash token
	    $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token expires in 1 hour

	    // Insert the verification token
	    $stmt = $conn->prepare("INSERT INTO login_tokens (user_id, type, token, verification_expiration_date) VALUES (?, 'email_verification', ?, ?)");
	    $stmt->bind_param("iss", $user_id, $hashed_token, $expires_at);
	    if (!$stmt->execute()) {
	        log_error($stmt->error, 'database_error.log');
	        return null;
    	}
	    return $token;
	}
	function generateVerificationCode($userId){
	    global $conn;
	    // Generate a random 6-digit code
	    $code = rand(100000, 999999);
	    $expiresAt = date('Y-m-d H:i:s', strtotime('+3 minutes')); // Expires in 10 minutes

		// Save the code and expiration in the database
	    $stmt = $conn->prepare("UPDATE login_tokens SET verification_code = ?, verification_expiration_date	= ? WHERE user_id = ?");
	    $stmt->bind_param("ssi", $code, $expiresAt, $userId);
	    if (!$stmt->execute()) {
	        log_error($stmt->error, 'database_error.log');
    	}
	    $stmt->close();
	    return $code;
	}

	function checkVCodeStatus($user_id) {
		global $conn;
		$stmt = $conn->prepare("SELECT verification_code FROM login_tokens WHERE user_id = ? AND type = 'email_verification' AND verification_expiration_date > NOW()");
	    $stmt->bind_param("i", $user_id);
	    $stmt->execute();
	    $result = $stmt->get_result();

		return ($result->num_rows > 0);
	}

	function verifyEmailToken($token) {
		global $conn;

		try {
			$stmt = $conn->prepare("SELECT user_id, token FROM login_tokens WHERE type = 'email_verification' AND verification_expiration_date > NOW()");
			$stmt->execute();
			$result = $stmt->get_result();

			while ($row = $result->fetch_assoc()) {
				if (password_verify($token, $row['token'])) {
					$user_id = $row['user_id'];

					// Update user verification status
					$update_stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE uid = ?");
					$update_stmt->bind_param("i", $user_id);
					$update_stmt->execute();

					// Delete the verification token from `login_tokens`
					$delete_stmt = $conn->prepare("DELETE FROM login_tokens WHERE user_id = ? AND type = 'email_verification'");
					$delete_stmt->bind_param("i", $user_id);
					$delete_stmt->execute();

					$_SESSION['msg'] = "Email verified successfully! You can now log in.";
					return true;
				}
			}
			$_SESSION['msg'] = "Invalid or expired token.";
			return false;
		} catch (Exception $e) {
			log_error("Error verifying email: " . $e->getMessage(),'mail.log');
			$_SESSION['msg'] = "An error occured, Please try again later";
			return false;
		}
	}
	function verifyCode() {
		global $conn;
		if(isset($_POST['code']) && is_array($_POST['code'])) {
			$verification_code = implode('', $_POST['code']);
			if (strlen($verification_code) === 6 && ctype_digit($verification_code)) { 
				$stmt = $conn->prepare("SELECT user_id FROM login_tokens WHERE verification_code = ? AND type = 'email_verification' AND verification_expiration_date > NOW()");
				$stmt->bind_param("s", $verification_code);
	    		$stmt->execute();
	    		$result = $stmt->get_result();
	    		if ($result->num_rows > 0) { 
	    			$user = $result->fetch_assoc();
	                $user_id = $user['user_id'];

	                // Update user's verification status
	                $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE uid = ?");
	                $update_stmt->bind_param("i", $user_id);
	                $update_stmt->execute();


					// Set session and cookie information
					$_SESSION['name'] = $user['first_name'].' '.$user['last_name'];
					$_SESSION['first-name'] = $user['first_name'];
					$_SESSION['profile'] = USER_IMG.$user['profile_picture'];
					$_SESSION['email'] = $user['email'];
					$_SESSION['role'] = $user['role'];  
					setcookie('role', $user['role'], time() + (24 * 60 * 60), "/", "", true, true);

	                $_SESSION['msg'] = "Account Verification has been successful!";
	                header("location: dashboard");
	                exit();
	    		}
			}
		}
		$_SESSION['msg'] = "Invalid Verification code!";
        header("location: verify");
        exit();
	}
?>