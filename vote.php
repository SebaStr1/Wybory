<?php
include 'db.php';
$token = $_GET['token'] ?? '';
$now = date("Y-m-d H:i:s");

$stmt = $conn->prepare("SELECT * FROM vote_tokens WHERE token = ? AND used = 0 AND expires_at >= ?");
$stmt->bind_param("ss", $token, $now);
$stmt->execute();
$token_data = $stmt->get_result()->fetch_assoc();

if (!$token_data) {
    die("Token nieważny.");
}

$election_id = $token_data["election_id"];
$candidates = $conn->query("SELECT * FROM candidates WHERE election_id = $election_id");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $candidate_id = $_POST["candidate_id"];
    $conn->query("UPDATE candidates SET votes = votes + 1 WHERE id = $candidate_id");
    $conn->query("UPDATE vote_tokens SET used = 1 WHERE id = " . $token_data["id"]);
    $message = "Głos został oddany!";
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Głosowanie</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>

<h2>Głosowanie</h2>

<div class="container">
    <?php if (isset($message)): ?>
        <div class="message">
            <?= $message ?>
        </div>
        <a href="dashboard.php" class="back-button">Powrót do panelu</a>
    <?php else: ?>
        <h3>Wybierz kandydata:</h3>
        <form method="POST" class="vote-form">
            <?php while ($c = $candidates->fetch_assoc()): ?>
                <div>
                    <input type="radio" id="candidate_<?= $c['id'] ?>" name="candidate_id" value="<?= $c['id'] ?>" required>
                    <label for="candidate_<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></label>
                </div>
            <?php endwhile; ?>
            <button type="submit">Oddaj głos</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

