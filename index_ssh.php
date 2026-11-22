<?php
/**
 * K8 File Manager - SSH/SFTP edition
 * Requires the PHP SSH2 extension (php-ssh2 / PECL ssh2).
 * Temporary transfer/editor files are kept under __DIR__/.k8ssh_tmp.
 * Nginx: deny direct access with `location ~ /\.k8ssh_tmp/ { deny all; return 404; }`.
 * Keep this file behind HTTPS and an authenticated administrator boundary (VPN / web-server auth).
 */
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('K8SSHSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$config = [
    'title' => 'K8 SFTP File Manager',
    'editor' => true,
    'viewer' => true,
    'create_file' => true,
    'delete_file' => true,
    'upload_file' => true,
    'max_editor_bytes' => 5 * 1024 * 1024,
    'temp_dir' => __DIR__ . DIRECTORY_SEPARATOR . '.k8ssh_tmp'
];

if (empty($_SESSION['k8ssh_csrf'])) {
    $_SESSION['k8ssh_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['k8ssh_csrf'];

function k8ssh_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES);
    exit;
}

function k8ssh_csrf_valid(): bool
{
    $provided = (string)($_POST['csrf'] ?? $_SERVER['HTTP_X_K8_CSRF'] ?? '');
    return isset($_SESSION['k8ssh_csrf']) && hash_equals($_SESSION['k8ssh_csrf'], $provided);
}

function k8ssh_fingerprint_hex(string $fingerprint): string|false
{
    $value = trim($fingerprint);
    if (preg_match('/^SHA1:([A-Za-z0-9+\/=]+)$/i', $value, $match)) {
        $raw = base64_decode($match[1], true);
        return ($raw !== false && strlen($raw) === 20) ? bin2hex($raw) : false;
    }

    $value = preg_replace('/^(?:SHA1:)?/i', '', $value);
    $value = strtolower(preg_replace('/[^a-f0-9]/i', '', $value));
    return (strlen($value) === 40 && ctype_xdigit($value)) ? $value : false;
}

function k8ssh_valid_host(string $host): bool
{
    $host = trim($host);
    if ($host === '' || strlen($host) > 253 || preg_match('/[\s\x00-\x20\/\\@]/', $host)) {
        return false;
    }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return true;
    }
    return (bool)preg_match('/^(?=.{1,253}$)(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)(?:\.(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?))*$/', $host);
}

function k8ssh_connect_from(array $details): array
{
    if (!extension_loaded('ssh2') || !function_exists('ssh2_connect')) {
        throw new RuntimeException('PHP SSH2 extension is not installed or enabled. Install php-ssh2 and restart PHP-FPM/web server.');
    }
    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        throw new RuntimeException('allow_url_fopen must be enabled because the SSH2 SFTP stream wrapper is used for remote file transfers.');
    }

    $connection = @ssh2_connect($details['host'], (int)$details['port']);
    if (!$connection) {
        throw new RuntimeException('Could not establish SSH connection. Check host, port and firewall.');
    }

    $actual = @ssh2_fingerprint($connection, SSH2_FINGERPRINT_SHA1 | SSH2_FINGERPRINT_HEX);
    $actual = is_string($actual) ? strtolower(preg_replace('/[^a-f0-9]/i', '', $actual)) : '';
    $expected = (string)($details['fingerprint'] ?? '');
    if ($actual === '' || !hash_equals(strtolower($expected), $actual)) {
        throw new RuntimeException('SSH host-key fingerprint mismatch. Connection rejected.');
    }

    if (!@ssh2_auth_password($connection, $details['username'], $details['password'])) {
        throw new RuntimeException('SSH authentication failed. Check username and password.');
    }

    if (function_exists('ssh2_set_timeout')) {
        @ssh2_set_timeout($connection, 20);
    }

    $sftp = @ssh2_sftp($connection);
    if (!$sftp) {
        throw new RuntimeException('Unable to start the SFTP subsystem on the remote server.');
    }

    $root = @ssh2_sftp_realpath($sftp, $details['remote_root']);
    if (!is_string($root) || $root === '') {
        throw new RuntimeException('Remote root directory does not exist or cannot be accessed.');
    }
    $root = '/' . trim(str_replace('\\', '/', $root), '/');
    if ($root === '/') $root = '/';

    $rootUri = 'ssh2.sftp://' . (int)$sftp . $root;
    if (!@is_dir($rootUri)) {
        throw new RuntimeException('Configured remote root is not a directory.');
    }

    return [$connection, $sftp, $root];
}

function k8ssh_context(): array
{
    if (empty($_SESSION['k8ssh'])) {
        throw new RuntimeException('SSH session expired. Please sign in again.');
    }
    return k8ssh_connect_from($_SESSION['k8ssh']);
}

function k8ssh_in_root(string $path, string $root): bool
{
    $root = rtrim($root, '/');
    if ($root === '') $root = '/';
    if ($path === $root) return true;
    return $root === '/' ? str_starts_with($path, '/') : str_starts_with($path, $root . '/');
}

/** Resolve a relative UI path beneath the session's configured remote root. */
function k8ssh_resolve_path($sftp, string $root, string $relative, bool $allowMissing = true): string
{
    $relative = str_replace('\\', '/', trim($relative));
    if (str_contains($relative, "\0") || str_starts_with($relative, '/') || preg_match('#(^|/)\.\.?(/|$)#', $relative)) {
        throw new RuntimeException('Invalid remote path.');
    }

    $relative = trim($relative, '/');
    if ($relative === '') return $root;

    $segments = explode('/', $relative);
    $name = array_pop($segments);
    if ($name === '' || $name === '.' || $name === '..') {
        throw new RuntimeException('Invalid remote path.');
    }

    $parentInput = rtrim($root, '/') . (count($segments) ? '/' . implode('/', $segments) : '');
    if ($parentInput === '') $parentInput = '/';
    $parentReal = @ssh2_sftp_realpath($sftp, $parentInput);
    if (!is_string($parentReal) || !k8ssh_in_root($parentReal, $root)) {
        throw new RuntimeException('Remote directory is outside the configured root or does not exist.');
    }

    $target = rtrim($parentReal, '/') . '/' . $name;
    if ($target === '') $target = '/';

    if (!$allowMissing) {
        $resolved = @ssh2_sftp_realpath($sftp, $target);
        if (!is_string($resolved) || !k8ssh_in_root($resolved, $root)) {
            throw new RuntimeException('Remote item does not exist or is outside the configured root.');
        }
    }

    return $target;
}

function k8ssh_url($sftp, string $path): string
{
    $segments = explode('/', $path);
    $encoded = implode('/', array_map('rawurlencode', $segments));
    return 'ssh2.sftp://' . (int)$sftp . $encoded;
}

function k8ssh_mode_type(array $stat): int
{
    return ((int)($stat['mode'] ?? 0)) & 0170000;
}

function k8ssh_permissions(array $stat): string
{
    return substr(sprintf('%o', ((int)($stat['mode'] ?? 0)) & 07777), -4);
}

function k8ssh_list_dir($sftp, string $path, string $root): array
{
    $uri = k8ssh_url($sftp, rtrim($path, '/') . '/');
    $handle = @opendir($uri);
    if (!$handle) throw new RuntimeException('Unable to read this remote directory.');

    $folders = [];
    $files = [];
    while (($name = readdir($handle)) !== false) {
        if ($name === '.' || $name === '..' || str_starts_with($name, '.')) continue;
        if (str_contains($name, '/') || str_contains($name, "\0")) continue;

        $full = rtrim($path, '/') . '/' . $name;
        $stat = @ssh2_sftp_lstat($sftp, $full);
        if (!is_array($stat)) continue;
        $type = k8ssh_mode_type($stat);
        if ($type === 0120000) continue; // Do not traverse symbolic links.

        $rootPrefix = rtrim($root, '/');
        $relativeParent = ($path === $root) ? '' : ltrim(substr($path, strlen($rootPrefix)), '/');
        $relativePath = trim(($relativeParent !== '' ? $relativeParent . '/' : '') . $name, '/');
        $item = [
            'name' => $name,
            'path' => $relativePath,
            'type' => $type === 0040000 ? 'folder' : 'file',
            'extension' => $type === 0040000 ? '< directory >' : (strtolower(pathinfo($name, PATHINFO_EXTENSION)) ?: 'File'),
            'size' => (int)($stat['size'] ?? 0),
            'mtime' => (int)($stat['mtime'] ?? 0),
            'permissions' => k8ssh_permissions($stat)
        ];

        if ($type === 0040000) $folders[] = $item;
        elseif ($type === 0100000) $files[] = $item;
    }
    closedir($handle);

    usort($folders, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    usort($files, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
    return array_merge($folders, $files);
}

function k8ssh_dir_size($sftp, string $path, int $depth = 0, int &$visited = 0): int
{
    if ($depth > 64 || ++$visited > 50000) return 0;
    $handle = @opendir(k8ssh_url($sftp, rtrim($path, '/') . '/'));
    if (!$handle) return 0;
    $size = 0;

    while (($name = readdir($handle)) !== false) {
        if ($name === '.' || $name === '..') continue;
        $full = rtrim($path, '/') . '/' . $name;
        $stat = @ssh2_sftp_lstat($sftp, $full);
        if (!is_array($stat)) continue;
        $type = k8ssh_mode_type($stat);
        if ($type === 0120000) continue;
        if ($type === 0040000) $size += k8ssh_dir_size($sftp, $full, $depth + 1, $visited);
        elseif ($type === 0100000) $size += (int)($stat['size'] ?? 0);
    }
    closedir($handle);
    return $size;
}

function k8ssh_delete_tree($sftp, string $path, int $depth = 0): bool
{
    if ($depth > 64) return false;
    $stat = @ssh2_sftp_lstat($sftp, $path);
    if (!is_array($stat)) return false;
    $type = k8ssh_mode_type($stat);

    if ($type !== 0040000) return @ssh2_sftp_unlink($sftp, $path);

    $handle = @opendir(k8ssh_url($sftp, rtrim($path, '/') . '/'));
    if (!$handle) return false;
    $ok = true;
    while (($name = readdir($handle)) !== false) {
        if ($name === '.' || $name === '..') continue;
        if (!k8ssh_delete_tree($sftp, rtrim($path, '/') . '/' . $name, $depth + 1)) $ok = false;
    }
    closedir($handle);
    return $ok && @ssh2_sftp_rmdir($sftp, $path);
}

function k8ssh_tree_info($sftp, string $path, array &$stats, int $depth = 0): array
{
    $name = basename($path);
    $stat = @ssh2_sftp_lstat($sftp, $path);
    if (!is_array($stat)) return ['name' => $name, 'type' => 'unknown', 'size' => 0, 'children' => []];
    $type = k8ssh_mode_type($stat);

    if ($type === 0120000) {
        $stats['links']++;
        return ['name' => $name, 'type' => 'link', 'size' => 0, 'children' => []];
    }
    if ($type !== 0040000) {
        $bytes = (int)($stat['size'] ?? 0);
        $stats['files']++;
        $stats['bytes'] += $bytes;
        return ['name' => $name, 'type' => 'file', 'size' => $bytes, 'children' => []];
    }

    $stats['folders']++;
    $children = [];
    if ($depth >= 16 || $stats['nodes'] >= 2000) {
        return ['name' => $name, 'type' => 'folder', 'size' => 0, 'children' => [], 'truncated' => true];
    }

    $handle = @opendir(k8ssh_url($sftp, rtrim($path, '/') . '/'));
    if ($handle) {
        while (($item = readdir($handle)) !== false) {
            if ($item === '.' || $item === '..') continue;
            if (++$stats['nodes'] > 2000) break;
            $children[] = k8ssh_tree_info($sftp, rtrim($path, '/') . '/' . $item, $stats, $depth + 1);
        }
        closedir($handle);
    }
    return ['name' => $name, 'type' => 'folder', 'size' => array_sum(array_column($children, 'size')), 'children' => $children];
}

function k8ssh_tmp_dir(): string
{
    global $config;
    $base = $config['temp_dir'];
    if (!is_dir($base) && !@mkdir($base, 0700, true) && !is_dir($base)) {
        throw new RuntimeException('Unable to create the local temporary directory.');
    }
    @chmod($base, 0700);

    // Remove stale per-session workspaces after 24 hours.
    foreach (glob($base . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $staleDir) {
        if ((filemtime($staleDir) ?: time()) < time() - 86400) {
            foreach (glob($staleDir . DIRECTORY_SEPARATOR . '*') ?: [] as $staleFile) {
                if (is_file($staleFile) || is_link($staleFile)) @unlink($staleFile);
            }
            @rmdir($staleDir);
        }
    }

    $deny = $base . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($deny)) @file_put_contents($deny, "Require all denied\nDeny from all\n");
    $index = $base . DIRECTORY_SEPARATOR . 'index.php';
    if (!is_file($index)) @file_put_contents($index, "<?php http_response_code(404); exit;\n");

    $sessionDir = $base . DIRECTORY_SEPARATOR . hash('sha256', session_id());
    if (!is_dir($sessionDir) && !@mkdir($sessionDir, 0700, true) && !is_dir($sessionDir)) {
        throw new RuntimeException('Unable to create this session temporary directory.');
    }
    @chmod($sessionDir, 0700);

    // Remove abandoned transfer/editor files left by interrupted requests.
    foreach (glob($sessionDir . DIRECTORY_SEPARATOR . '*.tmp') ?: [] as $oldFile) {
        if (is_file($oldFile) && (filemtime($oldFile) ?: time()) < time() - 86400) {
            @unlink($oldFile);
        }
    }
    return $sessionDir;
}

function k8ssh_tmp_file(): string
{
    return k8ssh_tmp_dir() . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.tmp';
}

function k8ssh_remote_to_temp($sftp, string $remotePath): string
{
    $temp = k8ssh_tmp_file();
    if (!@copy(k8ssh_url($sftp, $remotePath), $temp)) {
        @unlink($temp);
        throw new RuntimeException('Unable to download the remote file to the temporary workspace.');
    }
    return $temp;
}

function k8ssh_temp_to_remote($sftp, string $temp, string $remotePath): void
{
    $input = @fopen($temp, 'rb');
    $output = @fopen(k8ssh_url($sftp, $remotePath), 'wb');
    if (!$input || !$output) {
        if (is_resource($input)) fclose($input);
        if (is_resource($output)) fclose($output);
        throw new RuntimeException('Unable to open local or remote file for transfer.');
    }
    $copied = stream_copy_to_stream($input, $output);
    fclose($input);
    fclose($output);
    if ($copied === false) throw new RuntimeException('Remote upload failed.');
}

function k8ssh_format_size(int $bytes): string
{
    if ($bytes <= 0) return '0 Bytes';
    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $index = min((int)floor(log($bytes, 1024)), count($units) - 1);
    return number_format($bytes / (1024 ** $index), 2) . ' ' . $units[$index];
}

function k8ssh_icon(string $extension, bool $folder = false): string
{
    if ($folder) return 'fa-solid fa-folder';
    $map = [
        'php'=>'fa-brands fa-php','html'=>'fa-brands fa-html5','htm'=>'fa-brands fa-html5',
        'css'=>'fa-brands fa-css3-alt','js'=>'fa-brands fa-js','json'=>'fa-solid fa-file-code',
        'xml'=>'fa-solid fa-file-code','sql'=>'fa-solid fa-database','md'=>'fa-brands fa-markdown',
        'txt'=>'fa-solid fa-file-lines','pdf'=>'fa-solid fa-file-pdf','zip'=>'fa-solid fa-file-zipper',
        'gz'=>'fa-solid fa-file-zipper','png'=>'fa-solid fa-file-image','jpg'=>'fa-solid fa-file-image',
        'jpeg'=>'fa-solid fa-file-image','gif'=>'fa-solid fa-file-image','webp'=>'fa-solid fa-file-image',
        'svg'=>'fa-solid fa-file-image','mp4'=>'fa-solid fa-file-video','mkv'=>'fa-solid fa-file-video',
        'mp3'=>'fa-solid fa-file-audio','wav'=>'fa-solid fa-file-audio','sh'=>'fa-solid fa-terminal',
        'conf'=>'fa-solid fa-gears','ini'=>'fa-solid fa-gears','env'=>'fa-solid fa-lock',
        'pem'=>'fa-solid fa-lock','key'=>'fa-solid fa-key'
    ];
    return $map[$extension] ?? 'fa-solid fa-file';
}

function k8ssh_name_valid(string $name): bool
{
    return $name !== '' && $name !== '.' && $name !== '..'
        && !str_contains($name, '/') && !str_contains($name, '\\')
        && !str_contains($name, "\0") && !preg_match('/[\x00-\x1F]/', $name);
}

function k8ssh_require_auth(): void
{
    if (empty($_SESSION['k8ssh'])) k8ssh_json(['success' => false, 'message' => 'SSH session expired. Please sign in again.'], 401);
}

/* ---------------- LOGIN / LOGOUT ---------------- */
$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

if ($requestMethod === 'POST' && $action === 'login') {
    if (!k8ssh_csrf_valid()) {
        $loginError = 'Security token expired. Reload the page and try again.';
    } elseif (!extension_loaded('ssh2') || !function_exists('ssh2_connect')) {
        $loginError = 'PHP SSH2 extension is unavailable. Install php-ssh2 and restart PHP-FPM/web server.';
    } else {
        $host = trim((string)($_POST['host'] ?? ''));
        $port = filter_var($_POST['port'] ?? 22, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $fingerprint = k8ssh_fingerprint_hex((string)($_POST['fingerprint'] ?? ''));
        $remoteRoot = trim((string)($_POST['remote_root'] ?? '.'));

        if (!k8ssh_valid_host($host) || !$port || $username === '' || $password === '' || $fingerprint === false) {
            $loginError = 'Enter a valid host, port, username, password and SHA1 host-key fingerprint.';
        } elseif (str_contains($remoteRoot, "\0") || str_contains($remoteRoot, '..')) {
            $loginError = 'Invalid remote root directory.';
        } else {
            $remoteRoot = ($remoteRoot === '' ? '.' : $remoteRoot);
            try {
                $details = [
                    'host' => $host,
                    'port' => (int)$port,
                    'username' => $username,
                    'password' => $password,
                    'fingerprint' => $fingerprint,
                    'remote_root' => $remoteRoot
                ];
                [$connection, $sftp, $root] = k8ssh_connect_from($details);
                $details['remote_root'] = $root;
                session_regenerate_id(true);
                $_SESSION['k8ssh'] = $details;
                $_SESSION['k8ssh_csrf'] = bin2hex(random_bytes(32));
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                exit;
            } catch (Throwable $e) {
                $loginError = $e->getMessage();
            }
        }
    }
}

if ($requestMethod === 'POST' && $action === 'logout') {
    if (!k8ssh_csrf_valid()) {
        http_response_code(403);
        exit('Invalid security token.');
    }
    unset($_SESSION['k8ssh']);
    session_regenerate_id(true);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

/* ---------------- SFTP API ---------------- */
if ($action !== '' && !in_array($action, ['login', 'logout'], true)) {
    k8ssh_require_auth();
    if ($requestMethod === 'POST' && !k8ssh_csrf_valid()) {
        k8ssh_json(['success' => false, 'message' => 'Invalid security token.'], 403);
    }

    try {
        [$connection, $sftp, $root] = k8ssh_context();
        $relative = (string)($_POST['path'] ?? $_GET['path'] ?? '');
        $remotePath = k8ssh_resolve_path($sftp, $root, $relative, false);

        if ($action === 'sizes' && $requestMethod === 'GET') {
            $items = k8ssh_list_dir($sftp, $remotePath, $root);
            $sizes = [];
            foreach ($items as $item) {
                $visited = 0;
                $sizes[$item['name']] = $item['type'] === 'folder'
                    ? k8ssh_dir_size($sftp, k8ssh_resolve_path($sftp, $root, $item['path'], false), 0, $visited)
                    : $item['size'];
            }
            k8ssh_json(['success' => true, 'sizes' => $sizes]);
        }

        if ($action === 'read' && $requestMethod === 'GET') {
            $resolved = k8ssh_resolve_path($sftp, $root, $relative, false);
            $stat = @ssh2_sftp_lstat($sftp, $resolved);
            if (!is_array($stat) || k8ssh_mode_type($stat) !== 0100000) throw new RuntimeException('Remote file not found or is a symbolic link.');
            $bytes = (int)($stat['size'] ?? 0);
            if ($bytes > $config['max_editor_bytes']) throw new RuntimeException('File is too large to open in the editor (5 MB maximum).');
            $temp = k8ssh_remote_to_temp($sftp, $resolved);
            try {
                $content = file_get_contents($temp);
                if ($content === false || str_contains($content, "\0")) throw new RuntimeException('Binary file cannot be opened in the text editor.');
            } finally { @unlink($temp); }
            k8ssh_json(['success' => true, 'content' => $content, 'bytes' => $bytes]);
        }

        if ($action === 'download' && $requestMethod === 'GET') {
            $resolved = k8ssh_resolve_path($sftp, $root, $relative, false);
            $stat = @ssh2_sftp_lstat($sftp, $resolved);
            if (!is_array($stat) || k8ssh_mode_type($stat) !== 0100000) throw new RuntimeException('Remote file not found or is a symbolic link.');
            $temp = k8ssh_remote_to_temp($sftp, $resolved);
            $name = basename($resolved);
            $mime = 'application/octet-stream';
            if (function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) { $detected = finfo_file($fi, $temp); if (is_string($detected)) $mime = $detected; finfo_close($fi); }
            }
            $inline = (($_GET['inline'] ?? '') === '1');
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($temp));
            $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'download';
            header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            readfile($temp);
            @unlink($temp);
            exit;
        }

        if ($action === 'create' && $requestMethod === 'POST') {
            if (!$config['create_file']) throw new RuntimeException('Creation is disabled.');
            $name = (string)($_POST['name'] ?? '');
            $type = (string)($_POST['type'] ?? '');
            if (!k8ssh_name_valid($name) || !in_array($type, ['file', 'folder'], true)) throw new RuntimeException('Invalid name or type.');
            $target = rtrim($remotePath, '/') . '/' . $name;
            if (@ssh2_sftp_lstat($sftp, $target) !== false) throw new RuntimeException('An item with that name already exists.');
            if ($type === 'folder') $ok = @ssh2_sftp_mkdir($sftp, $target, 0755, false);
            else { $stream = @fopen(k8ssh_url($sftp, $target), 'x'); $ok = (bool)$stream; if ($stream) fclose($stream); }
            if (!$ok) throw new RuntimeException('Unable to create remote ' . $type . '.');
            k8ssh_json(['success' => true, 'message' => ucfirst($type) . ' created.']);
        }

        if ($action === 'delete_preview' && $requestMethod === 'POST') {
            if (!$config['delete_file']) throw new RuntimeException('Deletion is disabled.');
            $target = k8ssh_resolve_path($sftp, $root, $relative, false);
            $stats = ['files' => 0, 'folders' => 0, 'links' => 0, 'bytes' => 0, 'nodes' => 0];
            $tree = k8ssh_tree_info($sftp, $target, $stats);
            k8ssh_json(['success' => true, 'tree' => $tree, 'stats' => $stats, 'is_folder' => $tree['type'] === 'folder']);
        }

        if ($action === 'delete' && $requestMethod === 'POST') {
            if (!$config['delete_file']) throw new RuntimeException('Deletion is disabled.');
            $target = k8ssh_resolve_path($sftp, $root, $relative, false);
            if ($target === $root) throw new RuntimeException('Cannot delete the configured remote root.');
            if (!k8ssh_delete_tree($sftp, $target)) throw new RuntimeException('Remote deletion failed. Check permissions and directory contents.');
            k8ssh_json(['success' => true, 'message' => 'Remote item deleted.']);
        }

        if ($action === 'save' && $requestMethod === 'POST') {
            if (!$config['editor']) throw new RuntimeException('Editing is disabled.');
            $target = k8ssh_resolve_path($sftp, $root, $relative, false);
            $stat = @ssh2_sftp_lstat($sftp, $target);
            if (!is_array($stat) || k8ssh_mode_type($stat) !== 0100000) throw new RuntimeException('Remote file not found or is a symbolic link.');
            $content = (string)($_POST['content'] ?? '');
            if (strlen($content) > $config['max_editor_bytes']) throw new RuntimeException('Editor content exceeds 5 MB.');
            $temp = k8ssh_tmp_file();
            try {
                if (file_put_contents($temp, $content, LOCK_EX) === false) throw new RuntimeException('Unable to write the local temporary file.');
                k8ssh_temp_to_remote($sftp, $temp, $target);
            } finally { @unlink($temp); }
            k8ssh_json(['success' => true, 'message' => 'Remote file saved.', 'bytes' => strlen($content)]);
        }

        if ($action === 'upload' && $requestMethod === 'POST') {
            if (!$config['upload_file']) throw new RuntimeException('Uploads are disabled.');
            if (empty($_FILES['upload']) || ($_FILES['upload']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Select a file to upload.');
            $name = basename((string)$_FILES['upload']['name']);
            if (!k8ssh_name_valid($name)) throw new RuntimeException('Invalid upload filename.');
            $target = rtrim($remotePath, '/') . '/' . $name;
            if (@ssh2_sftp_lstat($sftp, $target) !== false) throw new RuntimeException('A remote item with this name already exists.');
            $temp = k8ssh_tmp_file();
            try {
                if (!move_uploaded_file($_FILES['upload']['tmp_name'], $temp)) throw new RuntimeException('Unable to stage the uploaded file locally.');
                k8ssh_temp_to_remote($sftp, $temp, $target);
            } finally { @unlink($temp); }
            k8ssh_json(['success' => true, 'message' => 'Uploaded ' . $name . '.']);
        }

        if ($action === 'rename' && $requestMethod === 'POST') {
            $old = k8ssh_resolve_path($sftp, $root, $relative, false);
            $newName = (string)($_POST['new_name'] ?? '');
            if (!k8ssh_name_valid($newName) || $old === $root) throw new RuntimeException('Invalid new name.');
            $parent = rtrim(dirname($old), '/');
            $new = ($parent === '' ? '/' : $parent . '/') . $newName;
            if (@ssh2_sftp_lstat($sftp, $new) !== false) throw new RuntimeException('An item with that name already exists.');
            if (!@ssh2_sftp_rename($sftp, $old, $new)) throw new RuntimeException('Remote rename failed.');
            k8ssh_json(['success' => true, 'message' => 'Renamed successfully.']);
        }

        k8ssh_json(['success' => false, 'message' => 'Unknown API action.'], 404);
    } catch (Throwable $e) {
        k8ssh_json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

/* ---------------- LOGIN UI ---------------- */
if (empty($_SESSION['k8ssh'])):
    if (!empty($_SESSION['k8ssh_error'])) {
        $loginError = (string)$_SESSION['k8ssh_error'];
        unset($_SESSION['k8ssh_error']);
    }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($config['title'], ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="./k8.css">
<link rel="stylesheet" href="https://cdn.k8ight.com/fontawesome/6.4.2/css/all.min.css">
<style>
.k8ssh-wrap{max-width:560px;margin:6vh auto;padding:16px}.k8ssh-logo{font-size:42px}.k8ssh-note{font-size:13px;line-height:1.55}.k8ssh-input{width:100%;box-sizing:border-box}
</style>
</head>
<body class="k8-light-grey">
<div class="k8ssh-wrap">
 <div class="k8-card k8-white k8-border k8-padding">
  <div class="k8-center k8-padding">
   <i class="fa-solid fa-server k8-text-indigo k8ssh-logo"></i>
   <h2 class="k8-strong">K8 SFTP File Manager</h2>
   <p class="k8-text-grey">Connect to a remote server using SSH / SFTP.</p>
  </div>
  <?php if (!empty($loginError)): ?><div class="k8-panel k8-pale-red k8-border k8-border-red"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if (!extension_loaded('ssh2')): ?><div class="k8-panel k8-pale-yellow k8-border k8-border-yellow"><strong>SSH2 extension missing.</strong><br>Install <code>php-ssh2</code>, restart PHP-FPM/web server, then reload this page.</div><?php endif; ?>
  <form method="post" autocomplete="off">
   <input type="hidden" name="action" value="login">
   <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
   <label class="k8-strong">SSH Host / IP</label>
   <input class="k8-input k8ssh-input k8-margin-bottom" name="host" placeholder="server.example.com" required>
   <div class="k8-row-padding">
    <div class="k8-half"><label class="k8-strong">Port</label><input class="k8-input k8ssh-input k8-margin-bottom" type="number" name="port" value="22" min="1" max="65535" required></div>
    <div class="k8-half"><label class="k8-strong">Username</label><input class="k8-input k8ssh-input k8-margin-bottom" name="username" autocomplete="username" required></div>
   </div>
   <label class="k8-strong">Password</label>
   <input class="k8-input k8ssh-input k8-margin-bottom" type="password" name="password" autocomplete="current-password" required>
   <label class="k8-strong">SHA1 SSH Host-Key Fingerprint</label>
   <input class="k8-input k8ssh-input k8-margin-bottom" name="fingerprint" placeholder="SHA1:base64... or 40-character SHA1 hex" required>
   <p class="k8-text-grey k8ssh-note">Obtain this from the remote server console using <code>ssh-keygen -E sha1 -lf /etc/ssh/ssh_host_ed25519_key.pub</code>. The fingerprint is checked before password authentication; mismatches are rejected.</p>
   <label class="k8-strong">Remote Root Directory</label>
   <input class="k8-input k8ssh-input k8-margin-bottom" name="remote_root" value="." placeholder=". or /var/www">
   <button class="k8-button k8-indigo" type="submit" style="width:100%" <?= !extension_loaded('ssh2') ? 'disabled' : '' ?>><i class="fa-solid fa-right-to-bracket"></i> Connect using SFTP</button>
  </form>
 </div>
 <p class="k8-center k8-text-grey k8ssh-note">Credentials stay server-side in the PHP session. Protect this page with VPN or web-server authentication; the SFTP login is not a substitute for access control on this page. Use HTTPS. The included <code>.htaccess</code> denies Apache access to <code>.k8ssh_tmp</code>; Nginx must also deny direct requests to this hidden directory.</p>
</div>
</body>
</html>
<?php
exit;
endif;

/* ---------------- AUTHENTICATED FILE MANAGER ---------------- */
try {
    [$connection, $sftp, $root] = k8ssh_context();
    $currentRelative = str_replace('\\', '/', (string)($_GET['dir'] ?? ''));
    $currentPath = k8ssh_resolve_path($sftp, $root, $currentRelative, false);
    $entries = k8ssh_list_dir($sftp, $currentPath, $root);
} catch (Throwable $e) {
    unset($_SESSION['k8ssh']);
    $_SESSION['k8ssh_error'] = $e->getMessage();
    session_regenerate_id(true);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$remoteDetails = $_SESSION['k8ssh'];
$csrfToken = $_SESSION['k8ssh_csrf'];
$parentRelative = trim(dirname($currentRelative), '.');
$parentRelative = trim($parentRelative, '/');
if ($currentRelative === '') $parentRelative = '';
$segments = $currentRelative === '' ? [] : explode('/', trim($currentRelative, '/'));
$breadcrumbs = [['name' => 'Remote Root', 'path' => '']];
$accumulated = [];
foreach ($segments as $segment) {
    $accumulated[] = $segment;
    $breadcrumbs[] = ['name' => $segment, 'path' => implode('/', $accumulated)];
}
$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($config['title'], ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="./k8.css">
<link rel="stylesheet" href="https://cdn.k8ight.com/fontawesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">
<style>
.k8ssh-top{display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap}.k8ssh-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.k8ssh-table{min-width:850px}.k8ssh-name{overflow-wrap:anywhere}.k8ssh-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.62);z-index:9999;padding:3vh 2vw;overflow:auto}.k8ssh-modal{max-width:1100px;margin:0 auto;min-height:180px}.k8ssh-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px}.k8ssh-modal-body{max-height:78vh;overflow:auto}.k8ssh-editor{width:100%;min-height:60vh;box-sizing:border-box;font-family:monospace}.k8ssh-spin{opacity:.7}.k8ssh-muted{color:#777}.k8ssh-path{overflow-wrap:anywhere}.k8ssh-size{white-space:nowrap}.k8ssh-small{font-size:12px}.k8ssh-thumb{max-width:100%;max-height:65vh;object-fit:contain}.CodeMirror{height:65vh}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/yaml/yaml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/markdown/markdown.min.js"></script>
</head>
<body>
<div class="k8-padding">
 <div class="k8-card k8-white k8-border k8-padding">
  <div class="k8ssh-top">
   <div><h3 class="k8-strong"><i class="fa-solid fa-server k8-text-indigo"></i> K8 SFTP File Manager</h3><div class="k8-text-grey k8ssh-small"><?= htmlspecialchars($remoteDetails['username'] . '@' . $remoteDetails['host'] . ':' . $remoteDetails['port'], ENT_QUOTES, 'UTF-8') ?> · Root: <?= htmlspecialchars($root, ENT_QUOTES, 'UTF-8') ?></div></div>
   <form method="post" class="k8ssh-actions"><input type="hidden" name="action" value="logout"><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="k8-button k8-red"><i class="fa-solid fa-right-from-bracket"></i> Disconnect</button></form>
  </div>
 </div>
 <div class="k8-card k8-white k8-border k8-padding k8-margin-top">
  <div class="k8ssh-top">
   <div class="k8ssh-path"><i class="fa-regular fa-folder-open"></i>
    <?php foreach ($breadcrumbs as $i => $crumb): ?>
      <?= $i ? '<i class="fa-solid fa-angle-right k8-text-grey"></i>' : '' ?>
      <a href="<?= htmlspecialchars($baseUrl . ($crumb['path'] !== '' ? '?dir=' . rawurlencode($crumb['path']) : ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($crumb['name'], ENT_QUOTES, 'UTF-8') ?></a>
    <?php endforeach; ?>
   </div>
   <div class="k8ssh-actions">
    <?php if ($currentRelative !== ''): ?><a class="k8-button k8-grey" href="<?= htmlspecialchars($baseUrl . ($parentRelative !== '' ? '?dir=' . rawurlencode($parentRelative) : ''), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-arrow-up"></i> Up</a><?php endif; ?>
    <?php if ($config['create_file']): ?><button class="k8-button k8-indigo" type="button" onclick="showCreate()"><i class="fa-solid fa-plus"></i> Create</button><?php endif; ?>
    <?php if ($config['upload_file']): ?><button class="k8-button k8-green" type="button" onclick="document.getElementById('uploadInput').click()"><i class="fa-solid fa-upload"></i> Upload</button><?php endif; ?>
   </div>
  </div>
  <div class="k8-margin-top k8-margin-bottom">
   <input class="k8-input" id="tableSearch" placeholder="Search this directory..." oninput="searchRows(this.value)">
   <form id="uploadForm" style="display:none"><input id="uploadInput" type="file" name="upload" onchange="uploadFile()"></form>
  </div>
  <div style="overflow-x:auto">
   <table class="k8-table k8-bordered k8-border k8-white k8ssh-table">
    <thead><tr class="k8-green"><th>#</th><th>Name</th><th>Type</th><th>Permissions</th><th>Size</th><th>Date Modified</th><th>Actions</th></tr></thead>
    <tbody id="uptable">
    <?php foreach ($entries as $index => $entry): $encodedPath = base64_encode($entry['path']); $ext = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION)); ?>
     <tr data-size-name="<?= htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(strtolower($entry['name'] . ' ' . $entry['extension']), ENT_QUOTES, 'UTF-8') ?>">
      <td><?= $index + 1 ?></td>
      <td class="k8ssh-name">
       <?php if ($entry['type'] === 'folder'): ?><a class="k8-strong" href="<?= htmlspecialchars($baseUrl . '?dir=' . rawurlencode($entry['path']), ENT_QUOTES, 'UTF-8') ?>"><i class="<?= k8ssh_icon('', true) ?> k8-text-green"></i> <?= htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8') ?></a>
       <?php else: ?><button type="button" class="k8-btn k8-strong" onclick="openRemoteFile('<?= htmlspecialchars($encodedPath, ENT_QUOTES, 'UTF-8') ?>')"><i class="<?= k8ssh_icon($ext) ?> k8-text-indigo"></i> <?= htmlspecialchars($entry['name'], ENT_QUOTES, 'UTF-8') ?></button><?php endif; ?>
      </td>
      <td><?= htmlspecialchars($entry['extension'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($entry['permissions'], ENT_QUOTES, 'UTF-8') ?></td>
      <td class="k8ssh-size"><i class="fa-solid fa-spinner fa-spin k8ssh-spin"></i></td>
      <td><?= $entry['mtime'] ? htmlspecialchars(date('M d Y H:i', $entry['mtime']), ENT_QUOTES, 'UTF-8') : '-' ?></td>
      <td class="k8ssh-actions">
       <?php if ($entry['type'] === 'file'): ?><a class="k8-button k8-small k8-blue" href="<?= htmlspecialchars($baseUrl . '?action=download&path=' . rawurlencode($entry['path']), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-download"></i></a><?php endif; ?>
       <button type="button" class="k8-button k8-small k8-grey" onclick="renameRemote('<?= htmlspecialchars($encodedPath, ENT_QUOTES, 'UTF-8') ?>')"><i class="fa-solid fa-pen"></i></button>
       <?php if ($config['delete_file']): ?><button type="button" class="k8-button k8-small k8-red" onclick="deleteRemote('<?= htmlspecialchars($encodedPath, ENT_QUOTES, 'UTF-8') ?>')"><i class="fa-solid fa-trash"></i></button><?php endif; ?>
      </td>
     </tr>
    <?php endforeach; ?>
    <?php if (!$entries): ?><tr><td colspan="7" class="k8-center k8-text-grey">This directory is empty.</td></tr><?php endif; ?>
    </tbody>
   </table>
  </div>
  <p class="k8-text-grey k8ssh-small">Hidden files and symbolic links are not displayed. Folder sizes are fetched separately and may take longer on large remote directories.</p>
 </div>
</div>

<div class="k8ssh-overlay" id="k8sshOverlay" onclick="if(event.target===this)closeModal()">
 <div class="k8-card k8-white k8ssh-modal">
  <div class="k8-padding k8-border-bottom k8ssh-modal-head"><strong id="modalTitle">Remote file</strong><button class="k8-button k8-grey" type="button" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button></div>
  <div class="k8ssh-modal-body k8-padding" id="modalBody"></div>
 </div>
</div>

<script>
const K8SSH = {
    csrf: <?= json_encode($csrfToken) ?>,
    directory: <?= json_encode($currentRelative) ?>,
    editor: <?= json_encode($config['editor']) ?>,
    viewer: <?= json_encode($config['viewer']) ?>,
    create: <?= json_encode($config['create_file']) ?>,
    remove: <?= json_encode($config['delete_file']) ?>,
    upload: <?= json_encode($config['upload_file']) ?>
};
const API_URL = location.pathname;

function decodePath64(value) {
    const binary = atob(value);
    const bytes = Uint8Array.from(binary, char => char.charCodeAt(0));
    return new TextDecoder().decode(bytes);
}
function formatSize(bytes) {
    bytes = Number(bytes);
    if (!Number.isFinite(bytes) || bytes < 0) return 'Unknown';
    if (bytes === 0) return '0 Bytes';
    const units = ['Bytes','KB','MB','GB','TB','PB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return (bytes / Math.pow(1024, index)).toFixed(2) + ' ' + units[index];
}
function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function apiUrl(action, path = '') {
    const url = new URL(API_URL, location.href);
    url.searchParams.set('action', action);
    if (path !== '') url.searchParams.set('path', path);
    return url;
}
async function postApi(action, values = {}) {
    const data = new FormData();
    data.append('action', action);
    data.append('csrf', K8SSH.csrf);
    Object.entries(values).forEach(([key, value]) => data.append(key, value));
    const response = await fetch(API_URL, {method:'POST', body:data, credentials:'same-origin', headers:{'X-K8-CSRF':K8SSH.csrf}});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || 'Request failed.');
    return result;
}

async function loadSizes() {
    const rows = document.querySelectorAll('#uptable tr[data-size-name]');
    if (!rows.length) return;
    try {
        const response = await fetch(apiUrl('sizes', K8SSH.directory), {credentials:'same-origin'});
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Unable to fetch sizes.');
        rows.forEach(row => {
            const cell = row.cells[4];
            const name = row.dataset.sizeName;
            if (cell && Object.prototype.hasOwnProperty.call(result.sizes, name)) cell.textContent = formatSize(result.sizes[name]);
        });
    } catch (error) {
        rows.forEach(row => { if (row.cells[4]) row.cells[4].textContent = 'Unavailable'; });
        console.warn('SFTP size lookup failed:', error);
    }
}
document.addEventListener('DOMContentLoaded', loadSizes);

function searchRows(query) {
    query = String(query).toLowerCase();
    document.querySelectorAll('#uptable tr[data-search]').forEach(row => {
        row.style.display = row.dataset.search.includes(query) ? '' : 'none';
    });
}
function showModal(title, html) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('k8sshOverlay').style.display = 'block';
}
function closeModal() {
    document.getElementById('k8sshOverlay').style.display = 'none';
    document.getElementById('modalBody').innerHTML = '';
}

function showCreate() {
    showModal('Create remote item', `
      <div class="k8-padding">
       <label class="k8-strong">Name</label><input id="createName" class="k8-input k8-margin-bottom" placeholder="file.txt or folder-name">
       <div class="k8-row-padding">
        <div class="k8-half"><button class="k8-button k8-indigo" style="width:100%" onclick="createRemote('file')"><i class="fa-solid fa-file-circle-plus"></i> Create File</button></div>
        <div class="k8-half"><button class="k8-button k8-green" style="width:100%" onclick="createRemote('folder')"><i class="fa-solid fa-folder-plus"></i> Create Folder</button></div>
       </div><p id="createMessage" class="k8-margin-top"></p>
      </div>`);
}
async function createRemote(type) {
    const name = document.getElementById('createName').value.trim();
    const msg = document.getElementById('createMessage');
    try {
        await postApi('create', {path:K8SSH.directory, name, type});
        msg.textContent = 'Created successfully.';
        location.reload();
    } catch (e) { msg.className='k8-text-red'; msg.textContent=e.message; }
}

async function uploadFile() {
    const input = document.getElementById('uploadInput');
    if (!input.files.length) return;
    const data = new FormData();
    data.append('action','upload'); data.append('csrf',K8SSH.csrf); data.append('path',K8SSH.directory); data.append('upload',input.files[0]);
    try {
        const response = await fetch(API_URL,{method:'POST',body:data,credentials:'same-origin',headers:{'X-K8-CSRF':K8SSH.csrf}});
        const result=await response.json();
        if(!response.ok||!result.success) throw new Error(result.message||'Upload failed.');
        location.reload();
    } catch(e) { alert(e.message); input.value=''; }
}

async function deleteRemote(encodedPath) {
    if (!K8SSH.remove) return;
    const path = decodePath64(encodedPath);
    const name = path.split('/').pop();
    showModal('Delete remote item', '<div class="k8-center k8-padding"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p>Scanning remote contents...</p></div>');
    try {
        const result = await postApi('delete_preview', {path});
        const stats = result.stats;
        const formatNode = node => `<div class="k8-padding-small" style="border-bottom:1px solid #eee"><i class="fa-solid ${node.type==='folder'?'fa-folder k8-text-orange':node.type==='link'?'fa-link':'fa-file'}"></i> ${escapeHTML(node.name)} <span style="float:right">${node.type==='folder'?'Folder':node.type==='link'?'Link':formatSize(node.size)}</span></div>` + (node.children||[]).map(formatNode).join('');
        showModal('Delete: ' + name, `
          <div class="k8-padding k8-pale-red k8-border k8-border-red"><strong>Permanent remote deletion</strong><p>This operation deletes the selected item and its contents.</p></div>
          <div class="k8-row k8-padding k8-light-grey k8-center">
           <div class="k8-col s4"><b>${stats.files}</b><br><small>Files</small></div>
           <div class="k8-col s4"><b>${stats.folders}</b><br><small>Folders</small></div>
           <div class="k8-col s4"><b>${formatSize(stats.bytes)}</b><br><small>Total size</small></div>
          </div>
          <div class="k8-border k8-margin-top" style="max-height:300px;overflow:auto">${formatNode(result.tree)}</div>
          ${stats.nodes>=2000?'<p class="k8-text-orange">Preview truncated at 2,000 entries.</p>':''}
          <div class="k8-row k8-margin-top"><div class="k8-half"><button class="k8-button k8-grey" style="width:100%" onclick="closeModal()">Cancel</button></div><div class="k8-half"><button class="k8-button k8-red" style="width:100%" onclick="confirmRemoteDelete('${encodedPath}')"><i class="fa-solid fa-trash"></i> Delete permanently</button></div></div>`);
    } catch(e) { showModal('Delete failed', `<p class="k8-text-red">${escapeHTML(e.message)}</p>`); }
}
async function confirmRemoteDelete(encodedPath) {
    const path = decodePath64(encodedPath);
    showModal('Deleting', '<div class="k8-center k8-padding"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p>Deleting remote item...</p></div>');
    try { await postApi('delete',{path}); location.reload(); }
    catch(e) { showModal('Delete failed', `<p class="k8-text-red">${escapeHTML(e.message)}</p>`); }
}

async function renameRemote(encodedPath) {
    const path = decodePath64(encodedPath);
    const oldName = path.split('/').pop();
    const next = prompt('New name:', oldName);
    if (next === null || next.trim() === '' || next.trim() === oldName) return;
    try { await postApi('rename',{path,new_name:next.trim()}); location.reload(); }
    catch(e) { alert(e.message); }
}

async function openRemoteFile(encodedPath) {
    const path = decodePath64(encodedPath);
    const name = path.split('/').pop();
    const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
    const imageExt=['jpg','jpeg','png','gif','webp','bmp','svg','ico','avif'];
    const videoExt=['mp4','webm','ogv','mov','m4v','mkv','avi','mpeg','mpg'];
    const audioExt=['mp3','wav','ogg','aac','m4a','flac','opus'];
    const url = apiUrl('download',path).href;
    const inlineUrl = apiUrl('download',path); inlineUrl.searchParams.set('inline','1');

    if (K8SSH.viewer && imageExt.includes(ext)) {
        showModal(name, `<div class="k8-center"><img class="k8ssh-thumb" src="${inlineUrl.href}" alt="${escapeHTML(name)}"></div><p class="k8-center"><a class="k8-button k8-green" href="${url}"><i class="fa-solid fa-download"></i> Download</a></p>`);
        return;
    }
    if (K8SSH.viewer && videoExt.includes(ext)) {
        showModal(name, `<video controls playsinline style="width:100%;max-height:65vh;background:#000"><source src="${inlineUrl.href}"></video><p><a class="k8-button k8-green" href="${url}">Download</a></p>`);
        return;
    }
    if (K8SSH.viewer && audioExt.includes(ext)) {
        showModal(name, `<audio controls preload="metadata" style="width:100%"><source src="${inlineUrl.href}"></audio><p><a class="k8-button k8-green" href="${url}">Download</a></p>`);
        return;
    }
    if (K8SSH.viewer && ext === 'pdf') {
        showModal(name, `<iframe src="${inlineUrl.href}" title="${escapeHTML(name)}" style="width:100%;height:70vh;border:0"></iframe><p><a class="k8-button k8-green" href="${url}">Download</a></p>`);
        return;
    }
    if (!K8SSH.editor) {
        showModal(name, `<p>Editor is disabled.</p><a class="k8-button k8-green" href="${url}">Download file</a>`);
        return;
    }

    showModal(name, '<div class="k8-center k8-padding"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p>Downloading into local temporary workspace...</p></div>');
    try {
        const response = await fetch(apiUrl('read',path),{credentials:'same-origin'});
        const result=await response.json();
        if(!response.ok||!result.success) throw new Error(result.message||'Unable to read file.');
        showModal(name, `
          <div class="k8ssh-top k8-margin-bottom"><small id="editorStatus" class="k8-text-grey">${formatSize(result.bytes)} · local temporary copy</small><div class="k8ssh-actions"><a class="k8-button k8-grey" href="${url}"><i class="fa-solid fa-download"></i> Download</a>${K8SSH.editor?'<button class="k8-button k8-green" id="saveRemote"><i class="fa-solid fa-floppy-disk"></i> Save to SFTP</button>':''}</div></div>
          <textarea id="remoteEditor" class="k8ssh-editor" spellcheck="false"></textarea>`);
        const textarea=document.getElementById('remoteEditor');
        textarea.value=result.content;
        let cm=null;
        if (window.CodeMirror) {
            const modes={php:'application/x-httpd-php',html:'htmlmixed',htm:'htmlmixed',css:'css',js:'javascript',json:'application/json',xml:'xml',yaml:'yaml',yml:'yaml',md:'markdown',sql:'text/x-sql',py:'python',sh:'shell',java:'text/x-java',c:'text/x-csrc',cpp:'text/x-c++src'};
            cm=CodeMirror.fromTextArea(textarea,{mode:modes[ext]||'text/plain',theme:'material-darker',lineNumbers:true,lineWrapping:false,indentUnit:4,tabSize:4,matchBrackets:true});
            cm.setSize('100%','65vh'); cm.refresh();
        }
        const save=document.getElementById('saveRemote');
        if(save) save.addEventListener('click',async()=>{
            save.disabled=true;
            const status=document.getElementById('editorStatus'); status.textContent='Uploading changes...';
            try { await postApi('save',{path,content:cm?cm.getValue():textarea.value}); status.textContent='Saved to remote server'; status.className='k8-text-green'; }
            catch(e) { status.textContent=e.message; status.className='k8-text-red'; }
            finally { save.disabled=false; }
        });
    } catch(e) { showModal('Unable to open file', `<p class="k8-text-red">${escapeHTML(e.message)}</p><a class="k8-button k8-green" href="${url}">Download instead</a>`); }
}
</script>
</body>
</html>
