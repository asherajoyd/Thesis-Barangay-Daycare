<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "barangaydaycare";


// ========================================
// Database Connection
// ========================================

$conn = new mysqli(
    $servername,
    $username,
    $password,
    $dbname
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// $conn->set_charset("utf8mb4");


// // ========================================
// // Create User Function
// // ========================================

// function createUser(
//     mysqli $conn,
//     string $first_name,
//     string $middle_name,
//     string $last_name,
//     string $email,
//     string $plain_password,
//     string $role
// ): void {

//     // Hash password
//     $hashed_password = password_hash(
//         $plain_password,
//         PASSWORD_DEFAULT
//     );


//     // Insert user
//     $sql = "
//         INSERT INTO users
//         (
//             first_name,
//             middle_name,
//             last_name,
//             email,
//             password,
//             role
//         )
//         VALUES (?, ?, ?, ?, ?, ?)
//     ";

//     $stmt = $conn->prepare($sql);

//     if (!$stmt) {
//         echo "
//             <div>
//                 <strong>Error preparing {$role} account:</strong>
//                 " . htmlspecialchars($conn->error) . "
//             </div>
//         ";

//         return;
//     }


//     $stmt->bind_param(
//         "ssssss",
//         $first_name,
//         $middle_name,
//         $last_name,
//         $email,
//         $hashed_password,
//         $role
//     );


//     // Execute
//     if ($stmt->execute()) {

//         echo "
//             <div style='margin-bottom: 20px;'>
//                 <strong>" . ucfirst($role) . " account created successfully!</strong>
//                 <br><br>

//                 Name:
//                 " . htmlspecialchars(
//                     trim("$first_name $middle_name $last_name")
//                 ) . "
//                 <br>

//                 Email:
//                 " . htmlspecialchars($email) . "
//                 <br>

//                 Password:
//                 " . htmlspecialchars($plain_password) . "
//                 <br>

//                 Role:
//                 " . htmlspecialchars($role) . "
//             </div>
//         ";

//     } else {

//         echo "
//             <div style='margin-bottom: 20px;'>
//                 <strong>Error creating " . htmlspecialchars($role) . " account:</strong>
//                 <br>
//                 " . htmlspecialchars($stmt->error) . "
//             </div>
//         ";
//     }


//     $stmt->close();
// }


// // ========================================
// // Admin Account
// // ========================================

// createUser(
//     $conn,
//     "Admin",
//     "",
//     "Barangay",
//     "admin@barangay.com",
//     "password123",
//     "admin"
// );


// // ========================================
// // Teacher Account
// // ========================================

// createUser(
//     $conn,
//     "Juan",
//     "",
//     "Dela Cruz",
//     "teacher@gmail.com",
//     "password123",
//     "teacher"
// );


// // ========================================
// // Parent Account
// // ========================================

// createUser(
//     $conn,
//     "Parent",
//     "",
//     "Barangay",
//     "parent@barangay.com",
//     "password123",
//     "parent"
// );


// // ========================================
// // Close Connection
// // ========================================

// $conn->close();

?>