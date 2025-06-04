<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pesel = $_POST["pesel"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password_hash, is_admin FROM users WHERE pesel = ?");
    $stmt->bind_param("s", $pesel);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $password_hash, $is_admin);
        $stmt->fetch();

        if (password_verify($password, $password_hash)) {
            $_SESSION["user_id"] = $id;
            $_SESSION["is_admin"] = $is_admin;

            if ($is_admin) {
                header("Location: admin_panel.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Nieprawidłowe hasło.";
        }
    } else {
        $error = "Nie znaleziono użytkownika o podanym PESEL.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logowanie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<h2>Logowanie</h2>
<form method="POST">
    <input type="text" name="pesel" placeholder="PESEL" required><br>
    <input type="password" name="password" placeholder="Hasło" required><br>
    <button type="submit">Zaloguj się</button>
</form>


<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>


<p>Nie masz jeszcze konta? <a href="register.php"><button>Zarejestruj się</button></a></p>

</body>
</html>
