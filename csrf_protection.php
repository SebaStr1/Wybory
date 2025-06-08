<?php
/**
 * Prosty system ochrony CSRF
 * Włącz ten plik w formularzach, które wymagają ochrony
 */

/**
 * Bezpiecznie rozpoczyna sesję tylko jeśli nie istnieje
 */
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Generuje token CSRF i zapisuje go w sesji
 * @return string Token CSRF
 */
function generateCSRFToken() {
    ensureSession();
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Weryfikuje token CSRF
 * @param string $token Token do weryfikacji
 * @return bool True jeśli token jest prawidłowy
 */
function verifyCSRFToken($token) {
    ensureSession();
    
    // Sprawdź czy token istnieje w sesji
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Sprawdź czy token nie wygasł (ważny przez 1 godzinę)
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    // Porównaj tokeny używając hash_equals dla bezpieczeństwa
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    
    // Jeśli token jest prawidłowy, wygeneruj nowy (one-time use)
    if ($isValid) {
        generateCSRFToken();
    }
    
    return $isValid;
}

/**
 * Zwraca HTML input z tokenem CSRF
 * @return string HTML input z tokenem
 */
function getCSRFInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Sprawdza token CSRF z POST i kończy skrypt jeśli nieprawidłowy
 * ✅ NAPRAWKA: Nie wywołuje session_start() - zakłada że sesja już istnieje
 */
function checkCSRFOrDie() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            die('Błąd bezpieczeństwa: Nieprawidłowy token CSRF. Odśwież stronę i spróbuj ponownie.');
        }
    }
}
?>