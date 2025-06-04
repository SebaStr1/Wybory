<?php
include 'db.php';

function isValidPESEL($pesel) {
    if (!preg_match('/^[0-9]{11}$/', $pesel)) {
        return false;
    }

    $weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += $weights[$i] * intval($pesel[$i]);
    }
    $checkDigit = (10 - ($sum % 10)) % 10;

    return $checkDigit == intval($pesel[10]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $surname = $_POST["surname"];
    $pesel = $_POST["pesel"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $hash = password_hash($password, PASSWORD_DEFAULT);

   
    if (!isValidPESEL($pesel)) {
        echo "Nieprawidłowy numer PESEL!";
        exit;
    }

    
    $stmt = $conn->prepare("SELECT id FROM users WHERE pesel = ?");
    $stmt->bind_param("s", $pesel);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "Użytkownik z tym PESEL już istnieje.";
        exit;
    }

    
    $stmt = $conn->prepare("INSERT INTO users (name, surname, pesel, email, password_hash, is_admin) VALUES (?, ?, ?, ?, ?, 0)");
    $stmt->bind_param("sssss", $name, $surname, $pesel, $email, $hash);

    if ($stmt->execute()) {
        echo "Rejestracja zakończona sukcesem!";
    } else {
        echo "Błąd: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rejestracja</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<h2>Rejestracja</h2>
<form method="POST">
    <input type="text" name="name" placeholder="Imię" required><br>
    <input type="text" name="surname" placeholder="Nazwisko" required><br>
    <input type="text" name="pesel" placeholder="PESEL" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Hasło" required><br>
    <button type="submit">Zarejestruj</button>
</form>


<p>Masz już konto? <a href="login.php"><button>Mam konto – Zaloguj się</button></a></p>

</body>
</html>

