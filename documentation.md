# Dokumentacja Systemu Wyborczego

## Spis treści
1. [Opis projektu](#opis-projektu)
2. [Architektura systemu](#architektura-systemu)
3. [Wymagania techniczne](#wymagania-techniczne)
4. [Instalacja i konfiguracja](#instalacja-i-konfiguracja)
5. [Struktura bazy danych](#struktura-bazy-danych)
6. [Funkcjonalności](#funkcjonalności)
7. [Bezpieczeństwo](#bezpieczeństwo)
8. [API](#api)
9. [Zarządzanie](#zarządzanie)
10. [Rozwiązywanie problemów](#rozwiązywanie-problemów)

## Opis projektu

System Wyborczy to webowa aplikacja umożliwiająca przeprowadzanie bezpiecznych głosowań online. System wykorzystuje architekturę kontenerową Docker i zapewnia wysokie standardy bezpieczeństwa.

### Główne cechy:
- Bezpieczne uwierzytelnianie użytkowników (PESEL + hasło)
- System tokenów głosowania z ograniczonym czasem ważności
- Panel administracyjny do zarządzania wyborami
- Wizualizacja wyników w czasie rzeczywistym
- Ochrona CSRF i inne mechanizmy bezpieczeństwa
- Responsywny interfejs użytkownika

## Architektura systemu

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Przeglądarka  │    │   Kontener PHP  │    │ Kontener MySQL  │
│                 │◄──►│                 │◄──►│                 │
│   (Frontend)    │    │   (Backend)     │    │   (Baza danych) │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │  phpMyAdmin     │
                       │ (Zarządzanie BD)│
                       └─────────────────┘
```

### Technologie użyte:
- **Backend**: PHP 8.1 + Apache
- **Baza danych**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript, Chart.js
- **Konteneryzacja**: Docker + Docker Compose
- **Zarządzanie BD**: phpMyAdmin

## Wymagania techniczne

### Minimalne wymagania:
- Docker 20.0+
- Docker Compose 2.0+
- 2GB RAM
- 5GB miejsca na dysku
- Przeglądarka z obsługą JavaScript

### Porty używane:
- `8080` - Aplikacja główna
- `8081` - phpMyAdmin
- `3306` - MySQL (wewnętrzny)

## Instalacja i konfiguracja

### 1. Klonowanie projektu
```bash
git clone <repository-url>
cd wybory-system
```

### 2. Konfiguracja środowiska
Utwórz plik `.env`:
```env
MYSQL_ROOT_PASSWORD=secure_root_password
MYSQL_DATABASE=moja_baza
MYSQL_USER=user
MYSQL_PASSWORD=password
```

### 3. Uruchomienie kontenerów
```bash
# Uruchomienie w tle
docker-compose up -d

# Sprawdzenie statusu
docker-compose ps
```

### 4. Inicjalizacja bazy danych
```bash
# Używając skryptu inicjalizującego
chmod +x init_db.sh
./init_db.sh

# Lub ręcznie
docker exec -i mysql-db mysql -u user -ppassword moja_baza < wybory_portal.sql
```

### 5. Dostęp do aplikacji
- **Aplikacja główna**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081

## Struktura bazy danych

### Tabela `users`
Przechowuje informacje o użytkownikach systemu.

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | INT AUTO_INCREMENT | Klucz główny |
| name | VARCHAR(100) | Imię użytkownika |
| surname | VARCHAR(100) | Nazwisko użytkownika |
| pesel | CHAR(11) | Numer PESEL (unikalny) |
| email | VARCHAR(255) | Adres email |
| password_hash | VARCHAR(255) | Hash hasła |
| is_admin | TINYINT(1) | Czy użytkownik jest administratorem |

### Tabela `elections`
Definiuje dostępne wybory.

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | INT AUTO_INCREMENT | Klucz główny |
| name | VARCHAR(255) | Nazwa wyborów |
| start_time | DATETIME | Data rozpoczęcia |
| end_time | DATETIME | Data zakończenia |

### Tabela `candidates`
Lista kandydatów w wyborach.

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | INT AUTO_INCREMENT | Klucz główny |
| name | VARCHAR(255) | Imię i nazwisko kandydata |
| description | TEXT | Opis kandydata |
| election_id | INT | ID wyborów (klucz obcy) |
| votes | INT | Liczba głosów |

### Tabela `vote_tokens`
Tokeny do głosowania.

| Kolumna | Typ | Opis |
|---------|-----|------|
| id | INT AUTO_INCREMENT | Klucz główny |
| user_id | INT | ID użytkownika (klucz obcy) |
| election_id | INT | ID wyborów (klucz obcy) |
| token | VARCHAR(64) | Token dostępu |
| expires_at | DATETIME | Data wygaśnięcia |
| used | TINYINT(1) | Czy token został użyty |

## Funkcjonalności

### Dla użytkowników zwykłych:
1. **Rejestracja i logowanie**
   - Walidacja numeru PESEL
   - Hashowanie haseł
   - Sesje użytkowników

2. **Generowanie tokenów głosowania**
   - Jeden token na wybory
   - Czas wygaśnięcia: 1 godzina
   - Bezpieczne generowanie

3. **Głosowanie**
   - Wybór kandydata
   - Jedноrazowe użycie tokenu
   - Transakcyjne zapisywanie głosów

4. **Przeglądanie wyników**
   - Wykresy w czasie rzeczywistym
   - Lista kandydatów
   - Statystyki głosowań

### Dla administratorów:
1. **Zarządzanie wyborami**
   - Tworzenie nowych wyborów
   - Ustawianie dat rozpoczęcia/zakończenia
   - Edycja parametrów

2. **Zarządzanie kandydatami**
   - Dodawanie kandydatów
   - Edycja opisów
   - Przypisywanie do wyborów

3. **Monitoring systemu**
   - Przegląd wszystkich wyborów
   - Statystyki uczestnictwa
   - Zarządzanie użytkownikami

## Bezpieczeństwo

### Implementowane mechanizmy:

#### 1. Ochrona CSRF
```php
// Generowanie tokenu
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

// Weryfikacja
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Błąd CSRF');
}
```

#### 2. Prepared Statements
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE pesel = ?");
$stmt->bind_param("s", $pesel);
$stmt->execute();
```

#### 3. Walidacja danych wejściowych
- Walidacja numeru PESEL (algorytm kontrolny)
- Sanityzacja danych wyjściowych (`htmlspecialchars`)
- Walidacja typów danych

#### 4. Bezpieczne hasła
- Hashing z `password_hash()`
- Wymagania dotyczące siły hasła
- Weryfikacja z `password_verify()`

#### 5. Nagłówki bezpieczeństwa
```php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'");
```

#### 6. Kontrola sesji
- Regeneracja ID sesji
- Timeout sesji
- Bezpieczne wylogowywanie

## API

### Endpoint: `/results_api.php`

**Pobierz wyniki głosowania**

```http
GET /results_api.php?election_id=1
```

**Odpowiedź:**
```json
{
  "names": ["Jan Kowalski", "Anna Nowak"],
  "votes": [150, 98],
  "total_candidates": 2,
  "total_votes": 248
}
```

**Kody błędów:**
- `400` - Nieprawidłowe ID wyborów
- `404` - Wybory nie zostały znalezione
- `500` - Błąd serwera

## Zarządzanie

### Komendy Docker

```bash
# Restart systemu
docker-compose restart

# Logi aplikacji
docker-compose logs php

# Logi bazy danych
docker-compose logs mysql

# Zatrzymanie systemu
docker-compose down

# Zatrzymanie z usunięciem wolumenów
docker-compose down -v
```

### Backup bazy danych

```bash
# Utworzenie backupu
docker exec mysql-db mysqldump -u user -ppassword moja_baza > backup.sql

# Przywracanie z backupu
docker exec -i mysql-db mysql -u user -ppassword moja_baza < backup.sql
```

### Monitoring

#### Sprawdzenie statusu kontenerów:
```bash
docker-compose ps
```

#### Sprawdzenie użycia zasobów:
```bash
docker stats
```

#### Logi w czasie rzeczywistym:
```bash
docker-compose logs -f
```

## Rozwiązywanie problemów

### Problem: Kontener MySQL nie startuje

**Objawy:** Błąd połączenia z bazą danych

**Rozwiązanie:**
1. Sprawdź logi: `docker-compose logs mysql`
2. Sprawdź miejsce na dysku: `df -h`
3. Restart kontenera: `docker-compose restart mysql`

### Problem: Błąd "Tabela nie istnieje"

**Objawy:** Błąd na stronie głównej o brakujących tabelach

**Rozwiązanie:**
```bash
# Zaimportuj strukturę bazy danych
docker exec -i mysql-db mysql -u user -ppassword moja_baza < wybory_portal.sql

# Lub użyj skryptu
./init_db.sh
```

### Problem: Błąd CSRF

**Objawy:** "Błąd bezpieczeństwa: Nieprawidłowy token CSRF"

**Rozwiązanie:**
1. Wyczyść cookies przeglądarki
2. Odśwież stronę
3. Spróbuj ponownie

### Problem: Powolne działanie

**Rozwiązanie:**
1. Zwiększ zasoby Docker Desktop
2. Sprawdź użycie CPU/RAM: `docker stats`
3. Zoptymalizuj bazę danych:
```sql
OPTIMIZE TABLE users, elections, candidates, vote_tokens;
```

### Problem: Port zajęty

**Objawy:** "Port 8080 is already in use"

**Rozwiązanie:**
1. Znajdź proces: `lsof -i :8080`
2. Zabij proces: `kill -9 <PID>`
3. Lub zmień port w `docker-compose.yml`

## Konfiguracja produkcyjna

### Zmienne środowiskowe
```env
# Silne hasła
MYSQL_ROOT_PASSWORD=very_secure_root_password_123!
MYSQL_PASSWORD=secure_password_456!

# Tryb produkcyjny
PHP_ENV=production
DEBUG_MODE=false
```

### Dodatkowe nagłówki bezpieczeństwa
```php
// Tylko HTTPS w produkcji
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Dodatkowe CSP
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net");
```

### Backup automatyczny
```bash
# Cron job dla codziennego backupu (crontab -e)
0 2 * * * docker exec mysql-db mysqldump -u user -ppassword moja_baza > /backups/wybory_$(date +\%Y\%m\%d).sql
```

## Kontakt i wsparcie

W przypadku problemów lub pytań:
1. Sprawdź logi systemu
2. Przejrzyj dokumentację
3. Sprawdź znane problemy w sekcji rozwiązywania problemów

## Licencja

Ten projekt jest dostępny na licencji MIT. Zobacz plik LICENSE dla szczegółów.