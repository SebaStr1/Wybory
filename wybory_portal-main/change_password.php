<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (password_verify($current, $row['password_hash'])) {
        $new_hashed = password_hash($new, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $update->bind_param("si", $new_hashed, $user_id);
        $update->execute();
        $success = "Hasło zostało zmienione.";
    } else {
        $error = "Nieprawidłowe aktualne hasło.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Zmień hasło</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>

<div class="container">
    <h2>Zmień hasło</h2>
    <form method="POST">
        <label>Aktualne hasło:</label>
        <input type="password" name="current_password" required>

        <label>Nowe hasło:</label>
        <input type="password" id="new_password" name="new_password" required>
        <div id="password-requirements" class="info">
            Hasło musi mieć min. 8 znaków, zawierać wielką i małą literę oraz cyfrę.
        </div>

        <button type="submit" id="submitBtn" disabled>Zmień hasło</button>
    </form>

    <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <a href="dashboard.php" class="back-link">← Powrót do panelu</a>
</div>

<script>
    const passwordInput = document.getElementById('new_password');
    const submitBtn = document.getElementById('submitBtn');
    const requirements = document.getElementById('password-requirements');

    passwordInput.addEventListener('input', () => {
        const pwd = passwordInput.value;
        const strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

        if (strong.test(pwd)) {
            requirements.style.color = 'green';
            requirements.textContent = "Hasło jest wystarczająco silne.";
            submitBtn.disabled = false;
        } else {
            requirements.style.color = 'red';
            requirements.textContent = "Hasło musi mieć min. 8 znaków, zawierać wielką i małą literę oraz cyfrę.";
            submitBtn.disabled = true;
        }
    });
</script>

</body>
</html>
