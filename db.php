<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "capstone";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

			// Add another insert to the role segregation table

			if($stmt->execute()) {

				$conn->commit();

				$_SESSION['role'] = $role;
				$_SESSION["msg"] = "Account was succesfully created. Please Log In";
				header("location: login.php");
				exit();
			}
		} 
        catch (Exception $e) {
            $conn->rollback();
            $_SESSION["error"] = $e->getMessage();
            header("location: signup.php");
            exit();
        }
		finally{
			$stmt->close();
	    	$conn->close();
		}
	}//End Registration Block

    if(isset($_POST['signin'])) {
		$email = $_POST['email'];
		$pass = $_POST['password'];

		if(empty($email) || empty($pass)) {
			$_SESSION['msg'] = "Login Credentials should not be empty!";
		 	header("location: admin_dashboard.php");
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

					$_SESSION['user'] = $user['user_id'];
					$_SESSION['name'] = $user['last_name'].', '.$user['first_name'];
					$_SESSION['email'] = $user['email'];
					setcookie('role', $user['role'], 0);
					
					$conn->close();
					header("location: admin_dashboard.php");
					exit();

				}
				$_SESSION['msg'] = "Invalid Login Credentials, Please Try Again";
				header("location: login.php");
				exit();
			} else {
				$_SESSION['msg'] = "No Account Found";
				header("location: login.php");
				exit();
			}
		} 
		catch(mysqli_sql_exception $e) {
			$_SESSION['msg'] = "Something went wrong. Please try again later.";
		 	header("location: login.php");
			exit();
		}
		catch(Exception $e) {
			$_SESSION['msg'] = "Unknown error occured, Please contact System Administrator!";
		 	header("location: login.php");
			exit();
		}
		finally {
			if ($conn) {
        		$conn->close();
    		}
		}
	}
?>