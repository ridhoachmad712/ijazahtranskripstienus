<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';

function app_config(): array
{
    static $config;
    if ($config === null) {
        $config = [
            'base_url' => '',
            'db_path' => APP_ROOT . '/storage/app.sqlite',
            'storage_root' => APP_ROOT . '/storage',
            'timezone' => 'Asia/Makassar',
            'verify_contact' => '',
        ];
        $local = APP_ROOT . '/config.local.php';
        if (is_file($local)) {
            $custom = require $local;
            if (!is_array($custom)) {
                throw new RuntimeException('config.local.php harus mengembalikan array.');
            }
            $config = array_replace($config, $custom);
        }
        if (($testDb = getenv('STIE_DB_PATH')) !== false && $testDb !== '') $config['db_path'] = $testDb;
        if (($storageRoot = getenv('STIE_STORAGE_ROOT')) !== false && $storageRoot !== '') $config['storage_root'] = $storageRoot;
        if (($baseUrl = getenv('STIE_BASE_URL')) !== false && $baseUrl !== '') $config['base_url'] = $baseUrl;
        date_default_timezone_set((string) $config['timezone']);
    }
    return $config;
}

function db(): PDO
{
    static $db;
    if ($db === null) {
        $path = (string) app_config()['db_path'];
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new RuntimeException('Direktori database tidak dapat dibuat.');
        }
        $db = new PDO('sqlite:' . $path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA busy_timeout = 5000');
        schema($db);
    }
    return $db;
}

function schema(PDO $db): void
{
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS admins (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 username TEXT NOT NULL UNIQUE,
 password_hash TEXT NOT NULL,
 created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS login_attempts (
 bucket TEXT PRIMARY KEY,
 attempts INTEGER NOT NULL,
 locked_until INTEGER NOT NULL,
 updated_at INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS graduates (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 nim TEXT NOT NULL UNIQUE,
 name TEXT NOT NULL,
 birth_place TEXT NOT NULL DEFAULT '',
 birth_date TEXT NOT NULL DEFAULT '',
 program_code TEXT NOT NULL,
 education_program TEXT NOT NULL DEFAULT 'S1',
 graduation_date TEXT NOT NULL DEFAULT '',
 diploma_number TEXT NOT NULL DEFAULT '',
 transcript_number TEXT NOT NULL DEFAULT '',
 issue_date TEXT NOT NULL DEFAULT '',
 thesis_title TEXT NOT NULL DEFAULT '',
 official_credits REAL,
 official_gpa REAL,
 created_at TEXT NOT NULL,
 updated_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS grades (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 graduate_id INTEGER NOT NULL REFERENCES graduates(id) ON DELETE CASCADE,
 position INTEGER NOT NULL,
 course_code TEXT NOT NULL,
 course_name TEXT NOT NULL,
 group_name TEXT NOT NULL DEFAULT '',
 credits REAL NOT NULL,
 grade_letter TEXT NOT NULL,
 grade_weight REAL NOT NULL,
 UNIQUE(graduate_id, course_code)
);
CREATE INDEX IF NOT EXISTS grades_graduate_position ON grades(graduate_id, position);
CREATE TABLE IF NOT EXISTS curriculum_courses (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 program_code TEXT NOT NULL,
 position INTEGER NOT NULL,
 course_code TEXT NOT NULL,
 course_name TEXT NOT NULL,
 credits REAL NOT NULL,
 UNIQUE(program_code, course_code)
);
CREATE INDEX IF NOT EXISTS curriculum_program_position ON curriculum_courses(program_code, position);
CREATE TABLE IF NOT EXISTS grade_scale (
 letter TEXT PRIMARY KEY,
 weight REAL NOT NULL,
 position INTEGER NOT NULL
);
CREATE TABLE IF NOT EXISTS templates (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 kind TEXT NOT NULL CHECK(kind IN ('diploma','transcript')),
 program_code TEXT NOT NULL,
 version INTEGER NOT NULL,
 label TEXT NOT NULL,
 settings_json TEXT NOT NULL,
 active INTEGER NOT NULL DEFAULT 1,
 created_at TEXT NOT NULL,
 UNIQUE(kind, program_code, version)
);
CREATE TABLE IF NOT EXISTS documents (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 graduate_id INTEGER NOT NULL REFERENCES graduates(id),
 kind TEXT NOT NULL CHECK(kind IN ('diploma','transcript')),
 document_number TEXT NOT NULL,
 status TEXT NOT NULL CHECK(status IN ('issued','revoked')),
 template_id INTEGER NOT NULL REFERENCES templates(id),
 snapshot_json TEXT NOT NULL,
 qr_token TEXT UNIQUE,
 pdf_path TEXT NOT NULL DEFAULT '',
 pdf_sha256 TEXT NOT NULL DEFAULT '',
 issued_at TEXT NOT NULL,
 revoked_at TEXT,
 revoked_reason TEXT NOT NULL DEFAULT '',
 replaces_id INTEGER REFERENCES documents(id)
);
CREATE INDEX IF NOT EXISTS documents_graduate_kind ON documents(graduate_id, kind, status);
CREATE INDEX IF NOT EXISTS documents_number ON documents(kind, document_number);
CREATE TABLE IF NOT EXISTS document_events (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 document_id INTEGER NOT NULL REFERENCES documents(id),
 admin_id INTEGER REFERENCES admins(id),
 action TEXT NOT NULL,
 reason TEXT NOT NULL DEFAULT '',
 created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS audit_events (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 admin_id INTEGER REFERENCES admins(id),
 entity TEXT NOT NULL,
 entity_id INTEGER,
 action TEXT NOT NULL,
 detail TEXT NOT NULL DEFAULT '',
 created_at TEXT NOT NULL
);
SQL);
    $documentColumns = array_column($db->query('PRAGMA table_info(documents)')->fetchAll(), 'name');
    if (!in_array('verify_count', $documentColumns, true)) $db->exec('ALTER TABLE documents ADD COLUMN verify_count INTEGER NOT NULL DEFAULT 0');
    if (!in_array('last_verified_at', $documentColumns, true)) $db->exec('ALTER TABLE documents ADD COLUMN last_verified_at TEXT');
    // Versi lama menyimpan SKS/IPK resmi yang kosong sebagai teks ''.
    $db->exec("UPDATE graduates SET official_credits=NULL WHERE official_credits=''");
    $db->exec("UPDATE graduates SET official_gpa=NULL WHERE official_gpa=''");
    seed_templates($db);
    seed_grade_scale($db);
}

function now_local(): string
{
    return date('Y-m-d H:i:s');
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function route_url(string $route, array $query = []): string
{
    return 'index.php?' . http_build_query(['r' => $route] + $query);
}

function public_base_url(): string
{
    $configured = trim((string) app_config()['base_url']);
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    return ($secure ? 'https://' : 'http://') . $host . $path;
}

function qr_base_url_problem(string $url): ?string
{
    $url = trim($url);
    if ($url === '') return 'URL publik QR (base_url) belum diatur di config.local.php.';
    $parts = parse_url($url);
    if (!is_array($parts)) return 'URL publik QR harus berupa alamat HTTPS lengkap.';
    $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
    if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || $host === '') return 'URL publik QR harus berupa alamat HTTPS lengkap.';
    if ($host === 'localhost' || str_ends_with($host, '.localhost') || $host === '::1' || $host === '0.0.0.0' || str_starts_with($host, '127.')) return 'URL publik QR masih menunjuk alamat lokal.';
    return null;
}

function verify_url(string $token): string
{
    return public_base_url() . '/' . route_url('verify', ['t' => $token]);
}

function redirect_to(string $route, array $query = []): never
{
    header('Location: ' . route_url($route, $query), true, 303);
    exit;
}

function start_web_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    session_name('STIE_DOC_ADMIN');
    session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function check_csrf(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.');
    }
}

function current_admin(): ?array
{
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, username FROM admins WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function login_bucket(string $username): string
{
    return hash('sha256', mb_strtolower(trim($username)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
}

function login_allowed(string $username): bool
{
    $stmt = db()->prepare('SELECT locked_until FROM login_attempts WHERE bucket=?');
    $stmt->execute([login_bucket($username)]);
    return (int) ($stmt->fetchColumn() ?: 0) <= time();
}

function record_login_failure(string $username): void
{
    $bucket = login_bucket($username);
    $stmt = db()->prepare('SELECT attempts,updated_at FROM login_attempts WHERE bucket=?');
    $stmt->execute([$bucket]);
    $row = $stmt->fetch();
    $count = $row && (int) $row['updated_at'] > time()-900 ? (int) $row['attempts'] + 1 : 1;
    $lock = $count >= 5 ? time()+300 : 0;
    db()->prepare('INSERT INTO login_attempts(bucket,attempts,locked_until,updated_at) VALUES(?,?,?,?) ON CONFLICT(bucket) DO UPDATE SET attempts=excluded.attempts,locked_until=excluded.locked_until,updated_at=excluded.updated_at')->execute([$bucket,$count,$lock,time()]);
}

function clear_login_failure(string $username): void
{
    db()->prepare('DELETE FROM login_attempts WHERE bucket=?')->execute([login_bucket($username)]);
}

const ADMIN_IDLE_SECONDS = 7200;

function require_admin(): array
{
    $admin = current_admin();
    if ($admin === null) {
        redirect_to('login');
    }
    if (time() - (int) ($_SESSION['last_activity'] ?? time()) > ADMIN_IDLE_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('warning', 'Sesi berakhir karena tidak aktif lebih dari 2 jam. Silakan masuk kembali.');
        redirect_to('login');
    }
    $_SESSION['last_activity'] = time();
    return $admin;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

function audit(string $entity, ?int $entityId, string $action, string $detail = ''): void
{
    $stmt = db()->prepare('INSERT INTO audit_events(admin_id,entity,entity_id,action,detail,created_at) VALUES(?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['admin_id'] ?? null, $entity, $entityId, $action, $detail, now_local()]);
}

function valid_iso_date(string $value): bool
{
    if ($value === '') {
        return true;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function format_date_id(string $value): string
{
    if (!valid_iso_date($value) || $value === '') {
        return $value;
    }
    $months = [1 => 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $date = new DateTimeImmutable($value);
    return $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}
