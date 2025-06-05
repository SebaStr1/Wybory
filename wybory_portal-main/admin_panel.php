<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}
include 'db.php';

// Dodawanie nowych wyborów
if (isset($_POST['create_election'])) {
    $name = $_POST['election_name'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    $stmt = $conn->prepare("INSERT INTO elections (name, start_time, end_time) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $start, $end);
    $stmt->execute();
}

// Dodawanie nowego kandydata
if (isset($_POST['add_candidate'])) {
    $election_id = $_POST['election_id'];
    $name = $_POST['candidate_name'];
    $description = $_POST['candidate_description'];

    $stmt = $conn->prepare("INSERT INTO candidates (name, description, election_id, votes) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("ssi", $name, $description, $election_id);
    $stmt->execute();
}

$elections = $conn->query("SELECT * FROM elections ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Admina</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>

<div class="container">
    <h2>Panel Admina</h2>
    <a href="logout.php" class="logout-link">Wyloguj się</a>

    <h3>Dodaj nowe wybory</h3>
    <form method="POST">
        <label>Nazwa wyborów:</label>
        <input type="text" name="election_name" required>

        <label>Data rozpoczęcia:</label>
        <input type="datetime-local" name="start_time" required>

        <label>Data zakończenia:</label>
        <input type="datetime-local" name="end_time" required>

        <button type="submit" name="create_election">Utwórz wybory</button>
    </form>

    <h3>Dodaj kandydata</h3>
    <form method="POST">
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
        <input type="text" name="candidate_name" required>

        <label>Opis kandydata:</label>
        <textarea name="candidate_description" required></textarea>

        <button type="submit" name="add_candidate">Dodaj kandydata</button>
    </form>

    <h3>Lista wyborów</h3>
    <?php while ($row = $elections->fetch_assoc()): ?>
        <div class="election-item">
            <strong><?= htmlspecialchars($row['name']) ?></strong><br>
            <?= $row['start_time'] ?> - <?= $row['end_time'] ?>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>
