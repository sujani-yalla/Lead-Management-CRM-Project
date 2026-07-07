<?php
session_start();
include "db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare(
        "SELECT id, name, password, role 
         FROM users 
         WHERE email = ? 
         LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

          // 🔹 Secure password verification
if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['logged_in'] = true;

            if ($user['role'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: employee-dashboard.php");
            }
            exit;

        } else {
            header("Location: index.php?error=Invalid password");
            exit;

        }

    } else {
        header("Location: index.php?error=User not found");
        exit;

    }

    $stmt->close();

}
?>
