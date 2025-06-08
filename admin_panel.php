<?php
session_start();
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        header("Location: login.php");
        exit;
    }
include 'db.php';
include 'csrf_protection.php';
// Debug informacje (można usunąć po naprawie)
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'BRAK'));
    error_log("POST CSRF token: " . ($_POST['csrf_token'] ?? 'BRAK'));
// ✅ Sprawdzenie CSRF dla wszystkich POST requestów
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            error_log("CSRF verification failed in admin_panel.php");
            http_response_code(403);
            die('Błąd bezpieczeństwa: Nieprawidłowy token CSRF. <a href="admin_panel.php">Odśwież stronę</a> i spróbuj ponownie.');
        }
    }
// Dodawanie nowych wyborów
if (isset($_POST['create_election'])) {
    $name = trim($_POST['election_name']);
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    if (empty($name) || empty($start) || empty($end)) {
        $error = "Wszystkie pola są wymagane.";
    } elseif (strtotime($start) >= strtotime($end)) {
        $error = "Data rozpoczęcia musi być wcześniejsza niż data zakończenia.";
    } else {
        $stmt = $conn->prepare("INSERT INTO elections (name, start_time, end_time) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $start, $end);
        
        if ($stmt->execute()) {
            $success = "Wybory zostały utworzone pomyślnie!";
        } else {
            $error = "Błąd podczas tworzenia wyborów: " . $conn->error;
        }
        $stmt->close();
    }
}
// Dodawanie nowego kandydata
if (isset($_POST['add_candidate'])) {
    $election_id = (int)$_POST['election_id'];
    $name = trim($_POST['candidate_name']);
    $description = trim($_POST['candidate_description']);

    if (empty($name) || empty($description) || $election_id <= 0) {
        $error = "Wszystkie pola są wymagane.";
    } else {
        // Sprawdź czy wybory istnieją
        $check_stmt = $conn->prepare("SELECT id FROM elections WHERE id = ?");
        $check_stmt->bind_param("i", $election_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            $error = "Wybrane wybory nie istnieją.";
        } else {
            $stmt = $conn->prepare("INSERT INTO candidates (name, description, election_id, votes) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("ssi", $name, $description, $election_id);
            
            if ($stmt->execute()) {
                $success = "Kandydat został dodany pomyślnie!";
            } else {
                $error = "Błąd podczas dodawania kandydata: " . $conn->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
$elections = $conn->query("SELECT * FROM elections ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Admina</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Panel Admina</h2>
    <a href="logout.php" class="logout-link">Wyloguj się</a>
    <?php if (isset($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <!-- Debug informacje (usuń w produkcji) -->
    <div class="debug-info">
        <strong>Debug:</strong><br>
        Session ID: <?= session_id() ?><br>
        CSRF Token w sesji: <?= isset($_SESSION['csrf_token']) ? 'TAK' : 'NIE' ?><br>
        User ID: <?= $_SESSION['user_id'] ?? 'BRAK' ?><br>
        Is Admin: <?= $_SESSION['is_admin'] ? 'TAK' : 'NIE' ?>
    </div>
    <h3>Dodaj nowe wybory</h3>
    <form method="POST">
        <?= getCSRFInput() ?>
        <label>Nazwa wyborów:</label>
        <input type="text" name="election_name" required maxlength="255">
        <label>Data rozpoczęcia:</label>
        <input type="datetime-local" name="start_time" required>
        <label>Data zakończenia:</label>
        <input type="datetime-local" name="end_time" required>
        <button type="submit" name="create_election">Utwórz wybory</button>
    </form>
    <h3>Dodaj kandydata</h3>
        <form method="POST">
            <?= getCSRFInput() ?>
            <label>Wybory:</label>
            <select name="election_id" required>
                <option value="">-- wybierz wybory --</option>
                <?php
                $res = $conn->query("SELECT * FROM elections ORDER BY id DESC");
                while ($row = $res->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>" . htmlspecialchars($row['name']) . "</option>";
                }
                ?>
            </select>
            <label>Imię i nazwisko kandydata:</label>
            <input type="text" name="candidate_name" required maxlength="255">
            <label>Opis kandydata:</label>
            <textarea name="candidate_description" required maxlength="1000" rows="4"></textarea>
            <button type="submit" name="add_candidate">Dodaj kandydata</button>
        </form>
    <h3>Lista wyborów</h3>
    <?php if ($elections && $elections->num_rows > 0): ?>
        <?php while ($row = $elections->fetch_assoc()): ?>
            <div class="election-item">
                <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                <?= htmlspecialchars($row['start_time']) ?> - <?= htmlspecialchars($row['end_time']) ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Brak wyborów w systemie.</p>
    <?php endif; ?>
</div>
</body>
</html>