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
				$stmt = $conn->prepare("INSERT INTO `users`(`password`,`email`,`role`,`first_name`,`last_name`) VALUES(?,?,?,?,?)");
				$stmt->bind_param("sssss", $password, $email, $role,$fname, $lname);

				if($stmt->execute()) {
					$mail = getMailerInstance();

					$user_id = $conn->insert_id; // Retrieve the last query user id

					// Sending of verification
					$token = generateVerificationToken($user_id);
					sendVerificationEmail($mail, $email, $token, $fname);
					$_SESSION["msg"] = "Account was succesfully created, Please Log In";
					$conn->commit();
					header("location: ".BASE."login");
					exit();
				}
			}
			catch(mysqli_sql_exception $e) {
				$conn->rollback();
				log_error("Error on line " . $e->getLine() . " in " . $e->getFile() . ": SQL Error: " . $e->getMessage(), 'database_error.log');

				$_SESSION['msg'] = "Something went wrong. Please try again later.";
			 	header("location: ".BASE."login");
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

        $userId = $_SESSION['user'];

        // Handle profile picture removal via POST parameter
        if (isset($_POST['removeProfilePicture']) && $_POST['removeProfilePicture'] === 'true') {
            // Get current profile picture
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentPicture = $result->fetch_assoc()['profile_picture'];
            $stmt->close();
            
            // Delete current picture if it's not the default
            if ($currentPicture !== 'default.jpg') {
                $picturePath = ROOT_PATH . '/assets/img/users/' . $currentPicture;
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
                
                // Reset to default picture in database
                $defaultPic = 'default.jpg';
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE uid = ?");
                $stmt->bind_param("si", $defaultPic, $userId);
                if (!$stmt->execute()) {
                    error_log("Failed to reset profile picture in database: " . $stmt->error);
                    $response['message'] = 'Failed to reset profile picture';
                    echo json_encode($response);
                    exit();
                }
                $stmt->close();
                
                $_SESSION['profile'] = USER_IMG.'default.jpg';
                $response['success'] = true;
                $response['message'] = 'Profile picture removed successfully';
                echo json_encode($response);
                exit();
            }
            else {
                $response['message'] = 'Failed to reset profile picture';
                echo json_encode($response);
                exit();
            }
        }

        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : null;
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : null;
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $countryCode = isset($_POST['countryCode']) ? trim($_POST['countryCode']) : '+63';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

        // Handle profile picture upload
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profilePicture'];
            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmp = $file['tmp_name'];
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed extensions
            $allowedExt = array('jpg', 'jpeg', 'png', 'gif');
            
            // Validate file type and size
            if (!in_array($fileExt, $allowedExt)) {
                $response['message'] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedExt);
                echo json_encode($response);
                exit();
            }
            
            if ($fileSize > 5242880) { // 5MB in bytes
                $response['message'] = 'File size too large. Maximum size: 5MB';
                echo json_encode($response);
                exit();
            }
            
            // Create new filename with user ID
            $newFileName = $userId . '.' . $fileExt;
            $uploadPath = ROOT_PATH . '/assets/img/users/' . $newFileName;
            
            // Get current profile picture
            $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentPicture = $result->fetch_assoc()['profile_picture'];
            $stmt->close();
            
            // Delete old profile picture if it's not the default
            if ($currentPicture !== 'default.jpg') {
                $oldPicturePath = ROOT_PATH . '/assets/img/users/' . $currentPicture;
                if (file_exists($oldPicturePath)) {
                    unlink($oldPicturePath);
                }
            }
            
            // Move uploaded file
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Update profile picture in database
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE uid = ?");
                $stmt->bind_param("si", $newFileName, $userId);
                if (!$stmt->execute()) {
                    error_log("Failed to update profile picture in database: " . $stmt->error);
                    $response['message'] = 'Failed to update profile picture in database';
                    echo json_encode($response);
                    exit();
                }
                $stmt->close();
                
                $_SESSION['profile'] = USER_IMG . $newFileName;
            } else {
                log_error("Failed to move uploaded file from $fileTmp to $uploadPath", 'database_error.log');
                $response['message'] = 'Failed to upload profile picture';
                echo json_encode($response);
                exit();
            }
        }

        // Validate first name and last name if provided
        if ($firstName !== null && (strlen($firstName) < 2 || strlen($firstName) > 50)) {
            $response['message'] = 'First name must be between 2 and 50 characters';
            echo json_encode($response);
            exit();
        }

        if ($lastName !== null && (strlen($lastName) < 2 || strlen($lastName) > 50)) {
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
        $query = "UPDATE users SET first_name = ?, last_name = ?, address = ?, contact_number = ? WHERE uid = ?";
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
    }

    function deactivateAccount($userId) {
        global $conn;
        $response = array('success' => false, 'message' => '');

        // Check if user exists and is active
        $stmt = $conn->prepare("SELECT status FROM users WHERE uid = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['message'] = 'User not found';
            return $response;
        }
        
        $user = $result->fetch_assoc();
        if (!$user['status']) {
            $response['message'] = 'Account is already inactive';
            return $response;
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Set status to 0 (inactive)
            $stmt = $conn->prepare("UPDATE users SET status = 0 WHERE uid = ?");
            $stmt->bind_param("i", $userId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to deactivate account");
            }
            
            // Commit transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Account deactivated successfully';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Account deactivation failed: " . $e->getMessage());
            $response['message'] = 'Failed to deactivate account: ' . $e->getMessage();
        }
        
        $stmt->close();
        return $response;
    }

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

    /**
     * Class Management Functions
     * These functions have been moved to class_management.php for better organization
     */
    require_once 'class_management.php';

    /**
     * Notifications Management Functions
     * These functions have been moved to notifications_management.php for better organization
     */
    require_once 'notifications_management.php';

    /**
     * Transactions Management Functions
     * These functions have been moved to transactions_management.php for better organization
     */
    require_once 'transactions_management.php';

    /**
     * Student Management Functions
     * These functions have been moved to student_management.php for better organization
     */
    require_once 'student_management.php';

    require_once 'user_management.php';
    ?>