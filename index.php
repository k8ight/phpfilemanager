<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$config=[
"dir"=> __DIR__,
"editor"=>true,
"viewer"=>true,
"create_file"=>true,
"delete_file"=>true
]; //"viewer"=true; means file musing video img view using openeditor editor  also textarea will be readonly unless editor is true (save button also render ins this case of editor true else no save button )
if (!is_dir($config['dir']) || !chdir($config['dir'])) {
    die('Unable to change to file manager directory.');
}
/* =====================================================
   K8 RECURSIVE DELETE
===================================================== */

function k8DeleteResolve($relative) {
    $root = realpath(__DIR__);

    $relative = str_replace('\\', '/', (string)$relative);
    $relative = preg_replace('#^(?:\./)+#', '', $relative);

    if (
        !$root ||
        $relative === '' ||
        str_starts_with($relative, '/') ||
        preg_match('#(^|/)\.\.?(/|$)#', $relative) ||
        str_contains($relative, "\0")
    ) {
        throw new RuntimeException('Invalid path.');
    }

    $parts = explode('/', $relative);
    $name = array_pop($parts);
    $parentRelative = implode(DIRECTORY_SEPARATOR, $parts);

    $parent = realpath(
        $root . ($parentRelative !== '' ? DIRECTORY_SEPARATOR . $parentRelative : '')
    );

    if (
        !$parent ||
        ($parent !== $root &&
         strpos($parent, $root . DIRECTORY_SEPARATOR) !== 0)
    ) {
        throw new RuntimeException('Directory is outside the file manager.');
    }

    $target = $parent . DIRECTORY_SEPARATOR . $name;

    if (!file_exists($target) && !is_link($target)) {
        throw new RuntimeException('File or folder does not exist.');
    }

    if ($target === $root || realpath($target) === $root) {
        throw new RuntimeException('Cannot delete the root directory.');
    }

    // Do not allow a target whose actual resolved path escapes the root.
    // Symlinks themselves are allowed, but will only be unlinked.
    if (!is_link($target)) {
        $resolved = realpath($target);

        if (
            !$resolved ||
            strpos($resolved, $root . DIRECTORY_SEPARATOR) !== 0
        ) {
            throw new RuntimeException('Invalid target location.');
        }
    }

    return $target;
}


function k8DeleteTreeInfo($path, &$stats) {
    $name = basename($path);

    // Never follow symlinks.
    if (is_link($path)) {
        $stats['links']++;

        return [
            'name' => $name,
            'type' => 'link',
            'size' => 0,
            'children' => []
        ];
    }

    if (is_file($path)) {
        $size = filesize($path);
        $size = ($size === false) ? 0 : $size;

        $stats['files']++;
        $stats['bytes'] += $size;

        return [
            'name' => $name,
            'type' => 'file',
            'size' => $size,
            'children' => []
        ];
    }

    if (!is_dir($path)) {
        return [
            'name' => $name,
            'type' => 'other',
            'size' => 0,
            'children' => []
        ];
    }

    $stats['folders']++;
    $children = [];
    $folderSize = 0;

    $items = scandir($path);

    if ($items === false) {
        throw new RuntimeException('Unable to read folder: ' . $name);
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $child = k8DeleteTreeInfo(
            $path . DIRECTORY_SEPARATOR . $item,
            $stats
        );

        $folderSize += $child['size'];
        $children[] = $child;
    }

    return [
        'name' => $name,
        'type' => 'folder',
        'size' => $folderSize,
        'children' => $children
    ];
}


function k8DeleteRecursive($path) {
    // Symlinks must be unlinked, never traversed.
    if (is_link($path) || is_file($path)) {
        return unlink($path);
    }

    if (!is_dir($path)) {
        return false;
    }

    $items = scandir($path);

    if ($items === false) {
        return false;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $child = $path . DIRECTORY_SEPARATOR . $item;

        if (!k8DeleteRecursive($child)) {
            return false;
        }
    }

    return rmdir($path);
}


/* ---------------- DELETE API ---------------- */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    in_array($_POST['k8_action'] ?? '', ['delete_preview', 'delete_entry'], true)
) {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($config['delete_file'])) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Delete operation is disabled.'
        ]);
        exit;
    }

    try {
        $target = k8DeleteResolve($_POST['file'] ?? '');

        if (($_POST['k8_action'] ?? '') === 'delete_preview') {
            $stats = [
                'files' => 0,
                'folders' => 0,
                'links' => 0,
                'bytes' => 0
            ];

            $tree = k8DeleteTreeInfo($target, $stats);

            echo json_encode([
                'success' => true,
                'tree' => $tree,
                'stats' => $stats,
                'is_folder' => $tree['type'] === 'folder'
            ]);
            exit;
        }

        if (!k8DeleteRecursive($target)) {
            throw new RuntimeException(
                'Unable to delete completely. Check file permissions.'
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Deleted successfully.'
        ]);
        exit;

    } catch (Throwable $e) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['k8_action'] ?? '') === 'create_entry'
) {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($config['create_file'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'File and folder creation is disabled.'
        ]);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $dir  = str_replace('\\', '/', trim($_POST['dir'] ?? ''));

    if (
        $name === '' ||
        !in_array($type, ['file', 'folder'], true) ||
        $name === '.' ||
        $name === '..' ||
        preg_match('/[\/\\\\\x00-\x1F]/', $name)
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid name or creation type.'
        ]);
        exit;
    }

    $root = realpath(__DIR__);
    $dir = preg_replace('#^(?:\./)+#', '', $dir);
    $dir = trim($dir, '/');

    $parent = realpath(
        $root . ($dir !== '' ? DIRECTORY_SEPARATOR . $dir : '')
    );

    if (
        !$parent ||
        !is_dir($parent) ||
        ($parent !== $root &&
            strpos($parent, $root . DIRECTORY_SEPARATOR) !== 0)
    ) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid destination directory.'
        ]);
        exit;
    }

    $target = $parent . DIRECTORY_SEPARATOR . $name;

    if (file_exists($target) || is_link($target)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'A file or folder with this name already exists.'
        ]);
        exit;
    }

    if ($type === 'file') {
        $handle = @fopen($target, 'x');

        if ($handle) {
            fclose($handle);
            $success = true;
        } else {
            $success = false;
        }
    } else {
        $success = @mkdir($target, 0755);
    }

    if (!$success) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to create ' . $type . '. Check directory permissions.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' created successfully.'
    ]);
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['k8_action'] ?? '') === 'save_file'
) {
    header('Content-Type: application/json; charset=utf-8');

    $relative = str_replace('\\', '/', $_POST['file'] ?? '');
    $content = $_POST['content'] ?? null;

    // Remove only leading ./ segments
    $relative = preg_replace('#^(?:\./)+#', '', $relative);
if (empty($config['editor'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Editing is disabled.'
    ]);
    exit;
}
    if (
        $relative === '' ||
        $content === null ||
        str_starts_with($relative, '/') ||
        preg_match('#(^|/)\.\.(/|$)#', $relative)
    ) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file path or content.'
        ]);
        exit;
    }

    $root = realpath(__DIR__);
    $target = realpath($root . DIRECTORY_SEPARATOR . $relative);

    // Only allow existing files inside this file manager directory
    if (
        !$target ||
        !is_file($target) ||
        strpos($target, $root . DIRECTORY_SEPARATOR) !== 0
    ) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'File not allowed.'
        ]);
        exit;
    }

    if (!is_writable($target)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'File is not writable.'
        ]);
        exit;
    }

    $saved = file_put_contents($target, $content, LOCK_EX);

    if ($saved === false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to save file.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'File saved successfully.',
        'bytes' => $saved
    ]);
    exit;
}

/* ---------------- PHP SOURCE PREVIEW API ----------------
   GET index.php?k8_action=source&file=relative/path.php
---------------------------------------------------------- */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    ($_GET['k8_action'] ?? '') === 'source'
) {
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Content-Type: text/plain; charset=utf-8');

    if (empty($config['viewer'])) {
        http_response_code(403);
        exit('File viewing is disabled.');
    }

    $relative = str_replace('\\', '/', (string)($_GET['file'] ?? ''));
    $relative = trim($relative);

    if (
        $relative === '' ||
        str_contains($relative, "\0") ||
        str_starts_with($relative, '/') ||
        preg_match('#^[A-Za-z]:#', $relative) ||
        preg_match('#(^|/)\\.\\.?(/|$)#', $relative)
    ) {
        http_response_code(400);
        exit('Invalid file path.');
    }

    $root = realpath($config['dir']);
    $target = $root
        ? realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))
        : false;

    if (
        !$root ||
        !$target ||
        !is_file($target) ||
        !is_readable($target) ||
        !str_starts_with($target, $root . DIRECTORY_SEPARATOR)
    ) {
        http_response_code(404);
        exit('Source file not found.');
    }

    $allowedExtensions = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'inc'];
    if (!in_array(strtolower(pathinfo($target, PATHINFO_EXTENSION)), $allowedExtensions, true)) {
        http_response_code(403);
        exit('Source preview is available only for PHP files.');
    }

    $size = filesize($target);
    if ($size === false || $size > 2 * 1024 * 1024) {
        http_response_code(413);
        exit('Source file is too large to preview (maximum 2 MB).');
    }

    $source = file_get_contents($target);
    if ($source === false) {
        http_response_code(500);
        exit('Unable to read source file.');
    }

    echo $source;
    exit;
}


?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> 
<html class="no-js" lang="en-us"> <!--<![endif]-->
<!-- Version: 1.5--><!--ownerInfo:SOUNAKKAR-->
<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width" />
	<title>FILE MANAGER</title>
<link rel="icon" type="image\ico"  href="favicon.ico">
 <link rel="preconnect" href="https://cdn.k8ight.com">
 <link rel="stylesheet" href="./k8.css">
 
 <link rel="stylesheet" href="https://cdn.k8ight.com/fontawesome/6.4.2/css/all.min.css">
 
 
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script>
const K8_CONFIG = <?= json_encode($config) ?>;
</script>
<!-- Language modes -->
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
 <style>
 fx{
	text-align:center;
	font-size:64px;
}
@font-face {
  font-display: fallback; /* Define how the browser behaves during download */
}

.mh{
	max-width:32px;
}
table  tr  td:nth-child(1) {
	font-size:32px;
	max-width:32px;
}
.k8-table{
min-width:512px;
}
.nff{
	display:none;
	left:50%;
	min-width:386px;
	position:fixed;
	z-index:50;
	margin:8px auto;
	transform:translate(-50%,0%);
}
 </style>
</head>
<?php
function pfs_fol ($dir)
{
    $siz = 0;

    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $siz += is_file($each) ? filesize($each) : pfs_fol($each);
    }
	$size=pfs($siz);
	
	return $size;
}
function pfs_fol_bits ($dir)
{
    $siz = 0;

    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $siz += is_file($each) ? filesize($each) : pfs_fol_bits($each);
    }
	$size=pfs_bits($siz);
	
	return $size;
}
function icon($ext){
	

$icons = array(

    // DEFAULT
    "default" => "fa-solid fa-file",

    // DIRECTORIES
    "dir" => "fa-solid fa-folder",
    "folder" => "fa-solid fa-folder",
    "folder-open" => "fa-solid fa-folder-open",

    // HTML
    "html" => "fa-brands fa-html5",
    "htm" => "fa-brands fa-html5",
    "xhtml" => "fa-brands fa-html5",

    // PHP
    "php" => "fa-brands fa-php",
    "php3" => "fa-brands fa-php",
    "php4" => "fa-brands fa-php",
    "php5" => "fa-brands fa-php",
    "php7" => "fa-brands fa-php",
    "php8" => "fa-brands fa-php",
    "phtml" => "fa-brands fa-php",
    "inc" => "fa-brands fa-php",

    // CSS
    "css" => "fa-brands fa-css3-alt",
    "scss" => "fa-brands fa-sass",
    "sass" => "fa-brands fa-sass",
    "less" => "fa-brands fa-less",

    // JAVASCRIPT
    "js" => "fa-brands fa-js",
    "mjs" => "fa-brands fa-js",
    "cjs" => "fa-brands fa-js",
    "jsx" => "fa-brands fa-react",
    "ts" => "fa-brands fa-js",
    "tsx" => "fa-brands fa-react",
    "vue" => "fa-brands fa-vuejs",
    "svelte" => "fa-solid fa-code",

    // JSON / XML / DATA
    "json" => "fa-solid fa-file-code",
    "jsonc" => "fa-solid fa-file-code",
    "xml" => "fa-solid fa-file-code",
    "xsd" => "fa-solid fa-file-code",
    "xsl" => "fa-solid fa-file-code",
    "yaml" => "fa-solid fa-file-code",
    "yml" => "fa-solid fa-file-code",
    "toml" => "fa-solid fa-file-code",
    "csv" => "fa-solid fa-file-csv",
    "tsv" => "fa-solid fa-file-csv",

    // DATABASE
    "sql" => "fa-solid fa-database",
    "sqlite" => "fa-solid fa-database",
    "db" => "fa-solid fa-database",
    "mysql" => "fa-solid fa-database",
    "pgsql" => "fa-solid fa-database",
    "prisma" => "fa-solid fa-database",

    // SHELL / TERMINAL
    "sh" => "fa-solid fa-terminal",
    "bash" => "fa-solid fa-terminal",
    "zsh" => "fa-solid fa-terminal",
    "fish" => "fa-solid fa-terminal",
    "cmd" => "fa-solid fa-terminal",
    "bat" => "fa-solid fa-terminal",
    "ps1" => "fa-brands fa-windows",
    "psm1" => "fa-brands fa-windows",
    "psd1" => "fa-brands fa-windows",

    // CONFIGURATION
    "conf" => "fa-solid fa-gears",
    "config" => "fa-solid fa-gears",
    "cfg" => "fa-solid fa-gears",
    "ini" => "fa-solid fa-gears",
    "env" => "fa-solid fa-gears",
    "properties" => "fa-solid fa-gears",
    "htaccess" => "fa-solid fa-gears",
    "htpasswd" => "fa-solid fa-lock",
    "gitignore" => "fa-brands fa-git-alt",
    "dockerfile" => "fa-brands fa-docker",
    "dockerignore" => "fa-brands fa-docker",
    "service" => "fa-solid fa-gears",
    "nginx" => "fa-solid fa-server",

    // PYTHON
    "py" => "fa-brands fa-python",
    "pyw" => "fa-brands fa-python",
    "pyi" => "fa-brands fa-python",
    "ipynb" => "fa-brands fa-python",

    // JAVA
    "java" => "fa-brands fa-java",
    "jar" => "fa-brands fa-java",
    "class" => "fa-brands fa-java",
    "kt" => "fa-solid fa-code",
    "kts" => "fa-solid fa-code",
    "gradle" => "fa-solid fa-code",

    // C / C++
    "c" => "fa-solid fa-file-code",
    "h" => "fa-solid fa-file-code",
    "cpp" => "fa-solid fa-file-code",
    "hpp" => "fa-solid fa-file-code",
    "cc" => "fa-solid fa-file-code",
    "cxx" => "fa-solid fa-file-code",
    "cs" => "fa-solid fa-file-code",

    // OTHER PROGRAMMING
    "go" => "fa-brands fa-golang",
    "rs" => "fa-solid fa-file-code",
    "rb" => "fa-solid fa-gem",
    "pl" => "fa-solid fa-file-code",
    "lua" => "fa-solid fa-file-code",
    "r" => "fa-solid fa-file-code",
    "dart" => "fa-brands fa-dart-lang",
    "swift" => "fa-brands fa-swift",
    "vue" => "fa-brands fa-vuejs",
    "m" => "fa-solid fa-file-code",
    "mm" => "fa-solid fa-file-code",
    "scala" => "fa-solid fa-file-code",
    "sol" => "fa-solid fa-file-code",
    "ex" => "fa-solid fa-file-code",
    "exs" => "fa-solid fa-file-code",

    // ARCHIVES
    "zip" => "fa-solid fa-file-zipper",
    "rar" => "fa-solid fa-file-zipper",
    "7z" => "fa-solid fa-file-zipper",
    "arc" => "fa-solid fa-file-zipper",
    "tar" => "fa-solid fa-file-zipper",
    "gz" => "fa-solid fa-file-zipper",
    "tgz" => "fa-solid fa-file-zipper",
    "bz2" => "fa-solid fa-file-zipper",
    "xz" => "fa-solid fa-file-zipper",
    "lz" => "fa-solid fa-file-zipper",
    "lzma" => "fa-solid fa-file-zipper",
    "zst" => "fa-solid fa-file-zipper",
    "cab" => "fa-solid fa-file-zipper",
    "arj" => "fa-solid fa-file-zipper",
    "lzh" => "fa-solid fa-file-zipper",

    // DISK IMAGES
    "img" => "fa-solid fa-compact-disc",
    "iso" => "fa-solid fa-compact-disc",
    "daa" => "fa-solid fa-compact-disc",
    "bin" => "fa-solid fa-compact-disc",
    "dmg" => "fa-brands fa-apple",
    "vhd" => "fa-solid fa-hard-drive",
    "vhdx" => "fa-solid fa-hard-drive",
    "vmdk" => "fa-solid fa-hard-drive",
    "qcow2" => "fa-solid fa-hard-drive",

    // PDF
    "pdf" => "fa-solid fa-file-pdf",

    // DOCUMENTS
    "doc" => "fa-solid fa-file-word",
    "docx" => "fa-solid fa-file-word",
    "odt" => "fa-solid fa-file-word",
    "rtf" => "fa-solid fa-file-word",
    "xls" => "fa-solid fa-file-excel",
    "xlsx" => "fa-solid fa-file-excel",
    "ods" => "fa-solid fa-file-excel",
    "ppt" => "fa-solid fa-file-powerpoint",
    "pptx" => "fa-solid fa-file-powerpoint",
    "odp" => "fa-solid fa-file-powerpoint",

    // TEXT / DOCUMENTATION
    "txt" => "fa-solid fa-file-lines",
    "text" => "fa-solid fa-file-lines",
    "md" => "fa-brands fa-markdown",
    "markdown" => "fa-brands fa-markdown",
    "rst" => "fa-solid fa-file-lines",
    "log" => "fa-solid fa-file-lines",
    "nfo" => "fa-solid fa-file-lines",
    "readme" => "fa-solid fa-file-lines",
    "license" => "fa-solid fa-file-lines",
    "lic" => "fa-solid fa-file-lines",
    "diff" => "fa-solid fa-file-lines",
    "patch" => "fa-solid fa-file-lines",

    // IMAGES
    "jpg" => "fa-solid fa-file-image",
    "jpeg" => "fa-solid fa-file-image",
    "png" => "fa-solid fa-file-image",
    "gif" => "fa-solid fa-file-image",
    "webp" => "fa-solid fa-file-image",
    "bmp" => "fa-solid fa-file-image",
    "ico" => "fa-solid fa-file-image",
    "avif" => "fa-solid fa-file-image",
    "tif" => "fa-solid fa-file-image",
    "tiff" => "fa-solid fa-file-image",
    "svg" => "fa-solid fa-file-image",
    "psd" => "fa-solid fa-file-image",
    "xcf" => "fa-solid fa-file-image",
    "raw" => "fa-solid fa-file-image",
    "heic" => "fa-solid fa-file-image",
    "heif" => "fa-solid fa-file-image",

    // VIDEO
    "mp4" => "fa-solid fa-file-video",
    "webm" => "fa-solid fa-file-video",
    "mkv" => "fa-solid fa-file-video",
    "mpeg" => "fa-solid fa-file-video",
    "mpg" => "fa-solid fa-file-video",
    "avi" => "fa-solid fa-file-video",
    "mov" => "fa-solid fa-file-video",
    "wmv" => "fa-solid fa-file-video",
    "flv" => "fa-solid fa-file-video",
    "m4v" => "fa-solid fa-file-video",
    "3gp" => "fa-solid fa-file-video",
    "ogv" => "fa-solid fa-file-video",
    "ts" => "fa-solid fa-file-video",

    // AUDIO
    "mp3" => "fa-solid fa-file-audio",
    "wav" => "fa-solid fa-file-audio",
    "ogg" => "fa-solid fa-file-audio",
    "oga" => "fa-solid fa-file-audio",
    "aac" => "fa-solid fa-file-audio",
    "m4a" => "fa-solid fa-file-audio",
    "flac" => "fa-solid fa-file-audio",
    "opus" => "fa-solid fa-file-audio",
    "wma" => "fa-solid fa-file-audio",
    "ac3" => "fa-solid fa-file-audio",
    "aiff" => "fa-solid fa-file-audio",
    "mid" => "fa-solid fa-file-audio",
    "midi" => "fa-solid fa-file-audio",

    // EXECUTABLES / INSTALLERS
    "exe" => "fa-brands fa-windows",
    "msi" => "fa-brands fa-windows",
    "apk" => "fa-brands fa-android",
    "aab" => "fa-brands fa-android",
    "deb" => "fa-brands fa-debian",
    "rpm" => "fa-brands fa-redhat",
    "app" => "fa-brands fa-apple",
    "dylib" => "fa-brands fa-apple",
    "dll" => "fa-solid fa-gears",
    "so" => "fa-solid fa-gears",
    "sys" => "fa-solid fa-gears",

    // FONTS
    "ttf" => "fa-solid fa-font",
    "otf" => "fa-solid fa-font",
    "woff" => "fa-solid fa-font",
    "woff2" => "fa-solid fa-font",
    "eot" => "fa-solid fa-font",

    // BACKUPS
    "bak" => "fa-solid fa-database",
    "backup" => "fa-solid fa-database",
    "old" => "fa-solid fa-clock-rotate-left",
    "tmp" => "fa-solid fa-file-circle-question",
    "temp" => "fa-solid fa-file-circle-question",

    // SECURITY / CERTIFICATES
    "pem" => "fa-solid fa-lock",
    "key" => "fa-solid fa-key",
    "crt" => "fa-solid fa-certificate",
    "cer" => "fa-solid fa-certificate",
    "cert" => "fa-solid fa-certificate",
    "csr" => "fa-solid fa-certificate",
    "p12" => "fa-solid fa-lock",
    "pfx" => "fa-solid fa-lock",
    "pub" => "fa-solid fa-key",

    // WEB / API
    "http" => "fa-solid fa-globe",
    "wsdl" => "fa-solid fa-globe",
    "graphql" => "fa-solid fa-code",
    "gql" => "fa-solid fa-code",

    // UNKNOWN / MISC
    "map" => "fa-solid fa-map",
    "manifest" => "fa-solid fa-list",
    "webmanifest" => "fa-solid fa-list",
    "desktop" => "fa-solid fa-desktop",
    "lock" => "fa-solid fa-lock",
    "checksum" => "fa-solid fa-shield-halved",
    "sha256" => "fa-solid fa-shield-halved",
    "sha512" => "fa-solid fa-shield-halved",
    "md5" => "fa-solid fa-shield-halved"

);

	if(array_key_exists($ext,$icons)){
	return $icons[$ext];
	}else{
		return "fa-solid fa-file-circle-question";
	}
}

function pfs($fileSizeInBytes) {
  $i = -1;
  $byteUnits = array(' KiB', ' MiB', ' GiB', ' TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
 do {
    $fileSizeInBytes = $fileSizeInBytes / 1024;
    $i++;
  } while ($fileSizeInBytes > 1024);
 return  round($fileSizeInBytes,2).$byteUnits[$i];
}

function pfs_bits($fileSizeInBytes) {
  $i = -1;
  $byteUnits = array(' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB');
 do {
    $fileSizeInBytes = $fileSizeInBytes / 1000;
    $i++;
  } while ($fileSizeInBytes > 1000);
 return  round($fileSizeInBytes,2).$byteUnits[$i];
}
function mask($param){
	$mask=array(0=>"-",1=>"x",2=>"w",3=>"wx",4=>"r",5=>"rx",6=>"rw",7=>"rwx");
	$ret="";
	$dx=str_split($param);
	for($i=0;$i<=3;$i++){
	$ret .=	$mask[$dx[$i]].(($i==3)? "": "-");
	}
	return $ret;
}
function is_site($dir){
	if(file_exists($dir."/index.php")||file_exists($dir."/index.html")){
		return true;
	}else{
		return false;
	}
}
if(isset($_POST['anf'])){
	$cdirx=$_POST['cdir'];
	$str=$_POST['nf'];
	if(!empty($str)){
	if(preg_match("/\./", $str)){
	 file_put_contents($cdirx.$str, '');	
	}else{
	mkdir($cdirx.$str);
	}
	header("LOCATION: ./?dir=".substr($cdirx, 0, strrpos($cdirx, '/'))."");
	}
}
function fp_perm($dec){
return mask(substr(decoct($dec), -4));
}

if(isset($_REQUEST['dir'])){
    $directory =(empty($_REQUEST['dir'])? "./":$_REQUEST['dir']."/");
}else{
    $directory ="./";
}
$cdir=rtrim($directory,"/");
$blocked=array("index");
?>
<body style="overflow-x:scroll">
<div class="k8-card k8-white k8-center k8-border k8-row nff" >
<div class="k8-col s9 k8-left-align k8-margin" style="font-size:50%;"><i class="fa-regular fa-folder-open" style="font-size:initial;"></i> : <?php echo $directory;?></div>
<div class="k8-col s1 k8-right-align k8-margin"><button class="k8-button k8-green" onclick="this.parentElement.parentElement.style='display:none;';"><i class="fa-solid fa-xmark"></i></button></div>
<form action="" method="post" class="k8-padding">
<input type="hidden" name="cdir" value="<?php echo $directory; ?>" />
<input type="text" name="nf" class="k8-input" required placeholder="NEW FILE (*.*) /FOLDER (*) NAME"  />
<button type="submit" name="anf" class="k8-button k8-green k8-margin-top" onclick="return confirm('New file / folder will be created on the following directory as : <?php echo $directory;?>'+this.previousElementSibling.value);"><i class="fa-solid fa-square-plus"></i> CREATE / ADD</button>
<h6 class="k8-text-red">Use format *.* for adding file 7 use * as to add folder<br />[demo file hello.txt/ for folder hello]</h6>
</form>
</div>
 <div class="k8-padding" >
 <table class="k8-table k8-bordered k8-border k8-white">
<tr style="font-size:18px;">
<th colspan="6" class="k8-indigo"><i class="fa-regular fa-folder-open"></i> K8-CDN : <?php echo ((substr($directory, 0, strrpos($directory, '/'))==".")? "< root >":substr($directory, 0, strrpos($directory, '/')));?></th>
<th colspan="1" class="k8-indigo"><div style="min-width:220px;">
<a href="./?dir=<?php echo ((substr($cdir, 0, strrpos($cdir, '/'))==".")? "":substr($cdir, 0, strrpos($cdir, '/')));?>" title="BACK"><button class="k8-button"><i class="fa-solid fa-right-to-bracket" style="transform:rotate(180deg);"></i></button></a>
</div>
</th>
</tr>

<tr class="k8-green">
<th class='mh'>#</th>
<th colspan="2" style="min-width:256px;">Name <input type="text" placeholder="Search Table...." class="filter k8-border k8-border-indigo" style="max-width:256px;float:right;" onkeyup="searchTable(this)"> <?php if (!empty($config['create_file'])): ?>
<button type="button" class="k8-indigo"
        onclick="add()" style="float:right;">
    <i class="fa-solid fa-plus"></i> ADD
</button>
<?php endif; ?></th>
<th >Type</th>
<th >Param</th>
<th >Size</th>
<th >Date Modified</th>
</tr>
<tbody class="sctable" id="uptable">
<?php
$fol=array();
$fil=array();
$dirs = glob($directory."*");
foreach($dirs as $dir) {
if(is_dir($dir))
 {
    $filx = pathinfo($dir);

	$time=filemtime($filx['dirname'].'/'.$filx['basename']);
	$modtime= date("F d Y",$time);
	$dirx=explode("/",$dir)[1];
  $fol[] = array ('name'=>basename($dir),'parent'=>$dirx,'size'=>"-",'ext'=>"< directory >","modtime"=>$modtime,"parm"=>fp_perm(fileperms($dir)),"link"=>$dir);


}else{
	$filx = pathinfo($dir);
	
	$name=$filx['filename'];
	$extention=$filx['extension'] ?? $name.'-File';
	$time=filemtime($filx['dirname'].'/'.$filx['basename']);
	$modtime= date("F d Y",$time);
	$dirx=explode("/",$dir)[1];
	$sizx=filesize($filx['dirname'].'/'.$filx['basename']);
	if(!preg_match('/index/',$name)){
    $fil[] = array ('name'=>basename($dir),'parent'=>$dirx,'size'=>pfs($sizx),'ext'=>$extention,"modtime"=>$modtime,"parm"=>fp_perm(fileperms($dir)),"link"=>$dir);
}}
}

foreach($fol as $k){
	
	echo ((in_array($k['name'], $blocked)&& substr( $string_n, 0, 1 )!== ".")? "" :"<tr><td class='mh'><a href='./?dir=".$k['link']."'><i class='fa-solid fa-folder k8-text-green'></i></a></td>
 <td colspan=2 class='k8-strong'><a class='k8-btn' href='./?dir=".$k['link']."'>".$k['name']." </a></td>
  <td>".$k['ext']." <br / ><a href='".$k['link']."' target='_blank'>< webopen ></a></td>
   <td>".$k['parm']."
".(!empty($config['delete_file']) ? "
<br>
<button type='button'
        class='k8-red'
        onclick=\"deleteEntry('".base64_encode($k['link'])."','".base64_encode($k['name'])."')\">
    <i class='fa-solid fa-trash'></i> Delete
</button>
" : "")."
</td>
  <td>".$k['size']."</td>
  <td>".$k['modtime']."</td>
  </tr>");
}
foreach($fil as $k){
	
	
echo ((in_array($k['name'], $blocked)&& substr( $string_n, 0, 1 )!== ".")? "" :"<tr><td class='mh'><a href='./?dir=".$k['link']."'><i class='".icon($k['ext'])." k8-text-green'></a></td>
 <td colspan=2 class='k8-strong'><a class='k8-btn' href='".(($config['viewer'])? "javascript:void(0)" : $k['link'] )."' onclick='".(($config['viewer'])? "openFile(this);" : "" )."' data-file='".base64_encode($k['link'])."'>".$k['name']." </a></td>
  <td>".$k['ext']."</td>
  <td>".$k['parm']."
".(!empty($config['delete_file']) ? "
<br>
<button type='button'
        class='k8-red'
        onclick=\"deleteEntry('".base64_encode($k['link'])."','".base64_encode($k['name'])."')\">
    <i class='fa-solid fa-trash'></i> Delete
</button>
" : "")."
</td>
  <td>".$k['size']."</td>
  <td>".$k['modtime']."</td>
  </tr>");
}
?>
</tbody>
</table>
<h6 class="k8-text-red"> Leading . file/folder & index is hidden here.also can use blocked array to block more</h6>
    </div>
	
<script src="./k8.js"></script>	
	<script>
	function searchTable(input) {
    var  filter, found, table, tr, td, i, j;
    filter = input.value.toUpperCase();
    table = document.getElementsByClassName("sctable");
    tr = table[0].getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td");
        for (j = 0; j < td.length; j++) {
            if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                found = true;
            }
        }
        if (found) {
            tr[i].style.display = "";
            found = false;
        } else {
            tr[i].style.display = "none";
        }
    }
}


async function add() {
    const modal = document.getElementById('modal');
    const title = modal?.querySelector('.title');
    const body = modal?.querySelector('.body');

    if (!modal || !title || !body) {
        console.error('K8 modal not initialized.');
        return;
    }

    if (K8_CONFIG?.create_file !== true) return;

    title.textContent = 'Create File / Folder';

    body.innerHTML = `
        <div class="k8-padding">

            <p class="k8-text-grey">
                Create a new entry in the current directory.
            </p>

            <label class="k8-strong" for="createEntryName">
                File / Folder Name
            </label>

            <input type="text"
                   id="createEntryName"
                   class="k8-input"
                   placeholder="Enter file or folder name"
                   autocomplete="off"
                   required>

            <div id="createEntryMessage"
                 class="k8-padding"></div>

            <div class="k8-row k8-margin-top">

                <div class="k8-col s6">
                    <button type="button"
                            class="k8-button k8-green"
                            style="width:100%;"
                            onclick="createEntry('file')">
                        <i class="fa-solid fa-file-circle-plus"></i>
                        Create File
                    </button>
                </div>

                <div class="k8-col s6">
                    <button type="button"
                            class="k8-button k8-blue"
                            style="width:100%;"
                            onclick="createEntry('folder')">
                        <i class="fa-solid fa-folder-plus"></i>
                        Create Folder
                    </button>
                </div>

            </div>
        </div>
    `;

    modal.style.display = 'block';
    v_blur();

    document.getElementById('createEntryName').focus();
}

async function createEntry(type) {
    const nameInput = document.getElementById('createEntryName');
    const message = document.getElementById('createEntryMessage');

    const name = nameInput.value.trim();

    if (!name) {
        message.innerHTML = `
            <span class="k8-text-red">
                Please enter a name.
            </span>`;
        nameInput.focus();
        return;
    }

    if (!['file', 'folder'].includes(type)) return;

    const buttons = document.querySelectorAll(
        '#modal .body button'
    );

    buttons.forEach(button => button.disabled = true);

    message.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Creating ${type}...
    `;

    try {
        const data = new FormData();

        data.append('k8_action', 'create_entry');
        data.append('type', type);
        data.append('name', name);
        data.append('dir', <?= json_encode($directory) ?>);

        const response = await fetch(location.href, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Creation failed.');
        }

        message.innerHTML = `
            <span class="k8-text-green">
                <i class="fa-solid fa-circle-check"></i>
                ${result.message}
            </span>
        `;

        // Close modal and refresh the current directory.
        setTimeout(() => {
            const modal = document.getElementById('modal');

            if (modal) {
                modal.style.display = 'none';
            }

            location.reload();
        }, 400);

    } catch (error) {
        message.innerHTML = `
            <span class="k8-text-red">
                <i class="fa-solid fa-circle-exclamation"></i>
                ${String(error.message)}
            </span>
        `;

        buttons.forEach(button => button.disabled = false);
    }
}


/* =====================================================
   K8 DELETE UI
===================================================== */

async function deleteEntry(encodedPath, encodedName) {
    if (K8_CONFIG?.delete_file !== true) return;

    const file = atob(encodedPath);
    const name = atob(encodedName);

    const modal = document.getElementById('modal');
    const title = modal?.querySelector('.title');
    const body = modal?.querySelector('.body');

    if (!modal || !title || !body) {
        console.error('K8 modal not initialized.');
        return;
    }

    const escapeHTML = value => String(value).replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[c]);

    const formatSize = bytes => {
        if (!bytes) return '0 Bytes';

        const units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.min(
            Math.floor(Math.log(bytes) / Math.log(1024)),
            units.length - 1
        );

        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + units[i];
    };

    title.innerHTML = '<i class="fa-solid fa-trash k8-text-red"></i> Delete: ' +
        escapeHTML(name);

    body.innerHTML = `
        <div class="k8-padding k8-center">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <p>Scanning contents...</p>
        </div>
    `;

    modal.style.display = 'block';
    v_blur();

    try {
        const data = new FormData();
        data.append('k8_action', 'delete_preview');
        data.append('file', file);

        const response = await fetch(location.href, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Unable to scan item.');
        }

        const stats = result.stats;
        const tree = result.tree;

        function renderTree(node, level = 0) {
            const isFolder = node.type === 'folder';
            const isLink = node.type === 'link';

            const icon = isFolder
                ? 'fa-folder'
                : isLink
                    ? 'fa-link'
                    : 'fa-file';

            const color = isFolder
                ? 'k8-text-orange'
                : isLink
                    ? 'k8-text-grey'
                    : 'k8-text-indigo';

            const children = (node.children || []).map(child =>
                renderTree(child, level + 1)
            ).join('');

            const row = `
                <div class="k8-row k8-padding-small"
                     style="border-bottom:1px solid #eee;align-items:center;">

                    <div class="k8-col s8"
                         style="overflow-wrap:anywhere;padding-left:${level * 18}px;">
                        <i class="fa-solid ${icon} ${color}"></i>
                        ${escapeHTML(node.name)}
                    </div>

                    <div class="k8-col s4 k8-right-align">
                        <small>${isFolder ? 'Folder' : isLink ? 'Link' : formatSize(node.size)}</small>
                    </div>

                </div>
            `;

            if (!children) return row;

            return `
                <details ${level < 1 ? 'open' : ''}>
                    <summary style="list-style:none;cursor:pointer;">
                        ${row}
                    </summary>
                    <div>${children}</div>
                </details>
            `;
        }

        const summary = `
            <div class="k8-row k8-padding k8-light-grey k8-center">

                <div class="k8-col s6 m3">
                    <i class="fa-solid fa-file"></i>
                    <div class="k8-strong">${stats.files}</div>
                    <small>Files</small>
                </div>

                <div class="k8-col s6 m3">
                    <i class="fa-solid fa-folder"></i>
                    <div class="k8-strong">${stats.folders}</div>
                    <small>Folders</small>
                </div>

                <div class="k8-col s6 m3">
                    <i class="fa-solid fa-link"></i>
                    <div class="k8-strong">${stats.links}</div>
                    <small>Links</small>
                </div>

                <div class="k8-col s6 m3">
                    <i class="fa-solid fa-hard-drive"></i>
                    <div class="k8-strong">${formatSize(stats.bytes)}</div>
                    <small>Total Size</small>
                </div>

            </div>
        `;

        const isFolder = result.is_folder;

        body.innerHTML = `
            <div class="k8-padding">

                <div class="k8-padding k8-pale-red k8-border k8-border-red">
                    <i class="fa-solid fa-triangle-exclamation k8-text-red"></i>
                    <strong>Permanent deletion</strong>
                    <p>
                        ${
                            isFolder
                                ? 'This folder and all its contents will be permanently deleted.'
                                : 'This file will be permanently deleted.'
                        }
                    </p>
                    <strong>${escapeHTML(name)}</strong>
                </div>

                ${summary}

                <h4 class="k8-strong">
                    <i class="fa-solid fa-list"></i>
                    ${isFolder ? 'Recursive contents' : 'File details'}
                </h4>

                <div class="k8-border"
                     style="max-height:350px;overflow:auto;">

                    ${renderTree(tree)}

                </div>

                <div class="k8-row k8-margin-top">

                    <div class="k8-col s6">
                        <button type="button"
                                class="k8-button k8-grey"
                                style="width:100%;"
                                onclick="document.getElementById('modal').style.display='none'">
                            Cancel
                        </button>
                    </div>

                    <div class="k8-col s6">
                        <button type="button"
                                class="k8-button k8-red"
                                style="width:100%;"
                                onclick="confirmDelete('${encodedPath}')">
                            <i class="fa-solid fa-trash"></i>
                            Delete Permanently
                        </button>
                    </div>

                </div>

            </div>
        `;

    } catch (error) {
        body.innerHTML = `
            <div class="k8-padding k8-text-red">
                <i class="fa-solid fa-circle-exclamation"></i>
                ${escapeHTML(error.message)}
            </div>
        `;
    }
}


/* ---------------- CONFIRM DELETE ---------------- */

async function confirmDelete(encodedPath) {
    if (K8_CONFIG?.delete_file !== true) return;

    const modal = document.getElementById('modal');
    const body = modal?.querySelector('.body');

    if (!body) return;

    body.innerHTML = `
        <div class="k8-padding k8-center">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <p>Deleting... Please wait.</p>
        </div>
    `;

    try {
        const data = new FormData();
        data.append('k8_action', 'delete_entry');
        data.append('file', atob(encodedPath));

        const response = await fetch(location.href, {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Delete failed.');
        }

        body.innerHTML = `
            <div class="k8-padding k8-center">
                <i class="fa-solid fa-circle-check fa-3x k8-text-green"></i>
                <h3>Deleted successfully</h3>
                <p>Refreshing directory...</p>
            </div>
        `;

        setTimeout(() => location.reload(), 700);

    } catch (error) {
        body.innerHTML = `
            <div class="k8-padding k8-text-red">
                <i class="fa-solid fa-circle-exclamation"></i>
                ${String(error.message).replace(/[&<>"']/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                })[c])}
            </div>
            <div class="k8-padding">
                <button class="k8-button k8-grey"
                        onclick="location.reload()">
                    Close
                </button>
            </div>
        `;
    }
}

/* =====================================================
   K8 FILE VIEWER
   Usage: openFile("path/to/file.ext");
   Uses existing K8 modal from k8.js
===================================================== */
async function openFile(e) {
    const file = atob(e.dataset.file || '');
const canEdit = K8_CONFIG?.editor === true; const canView =
K8_CONFIG?.viewer === true;

    const modal = document.getElementById('modal');
    const title = modal?.querySelector('.title');
    const body = modal?.querySelector('.body');

    if (!modal || !title || !body) {
        console.error('K8 modal not initialized.');
        return;
    }

    /* ---------------- BASIC HELPERS ---------------- */

    const escapeHTML = value => String(value).replace(/[&<>"']/g, c => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[c]);

    const formatSize = bytes => {
        if (!Number.isFinite(bytes) || bytes < 0) return 'Unknown';
        if (bytes === 0) return '0 Bytes';

        const units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

        const i = Math.min(
            Math.floor(Math.log(bytes) / Math.log(1024)),
            units.length - 1
        );

        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + units[i];
    };

    /* ---------------- FILE URL ---------------- */

    let url;

    try {
        url = new URL(file, location.href);

        if (
            !['http:', 'https:'].includes(url.protocol) ||
            url.origin !== location.origin
        ) {
            throw new Error('Invalid file location.');
        }

    } catch (e) {
        alert(e.message);
        return;
    }

    let name;

    try {
        name = decodeURIComponent(url.pathname.split('/').pop());
    } catch {
        name = url.pathname.split('/').pop();
    }

    name = name || 'Unknown file';

    const ext = name.includes('.')
        ? name.split('.').pop().toLowerCase()
        : '';

    const safeName = escapeHTML(name);
    const safeURL = escapeHTML(url.href);

    /* ---------------- EXTENSIONLESS FILES ---------------- */

    const specialFiles = {
        'dockerfile': 'docker',
        'containerfile': 'docker',
        'makefile': 'make',
        'gnumakefile': 'make',
        'license': 'text',
        'licence': 'text',
        'readme': 'text',
        'changelog': 'text',
        'copying': 'text',
        'authors': 'text',
        'contributors': 'text',
        'notice': 'text',
        'todo': 'text',
        'install': 'text',
        'procfile': 'text',
        'gemfile': 'ruby',
        'rakefile': 'ruby',
        'brewfile': 'text',
        'podfile': 'ruby',
        'fastfile': 'ruby',
        'vagrantfile': 'ruby',
        'jenkinsfile': 'text'
    };

    const basename = name.toLowerCase();

    const dotFiles = [
        '.env', '.env.local', '.env.example',
        '.gitignore', '.gitattributes', '.gitmodules',
        '.gitconfig', '.editorconfig', '.npmrc',
        '.nvmrc', '.browserslistrc', '.htaccess',
        '.htpasswd', '.user.ini', '.dockerignore',
        '.prettierrc', '.eslintrc', '.babelrc',
        '.npmignore', '.yarnrc', '.stylelintrc'
    ];

    const isSpecial = Object.prototype.hasOwnProperty.call(
        specialFiles, basename
    ) || dotFiles.includes(basename);

    /* ---------------- FILE GROUPS ---------------- */

    const audio = [
        'mp3', 'wav', 'ogg', 'oga', 'aac',
        'm4a', 'flac', 'opus', 'wma', 'aiff',
        'aif', 'mid', 'midi', 'amr', '3gp'
    ];

    const video = [
        'mp4', 'webm', 'ogv', 'ogg', 'mov',
        'm4v', 'mkv', 'avi', 'mpeg', 'mpg',
        'wmv', 'flv', '3gp', 'ts', 'mts',
        'm2ts', 'vob', 'mxf'
    ];

    const images = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'bmp', 'svg', 'ico', 'avif', 'tif',
        'tiff', 'jfif'
    ];

    const archives = [
        'zip', 'rar', '7z', 'tar', 'gz',
        'tgz', 'bz2', 'xz', 'lz', 'lzma',
        'zst', 'cab', 'arc', 'arj', 'lzh',
        'iso', 'dmg', 'deb', 'rpm', 'apk',
        'jar', 'war', 'ear', 'phar'
    ];

    const editable = [

        // Text and documentation
        'txt', 'text', 'md', 'markdown', 'mdown',
        'mkd', 'rst', 'adoc', 'asciidoc',
        'nfo', 'log', 'out', 'err',

        // PHP
        'php', 'php3', 'php4', 'php5', 'php7',
        'php8', 'phtml', 'inc', 'module',

        // Frontend
        'html', 'htm', 'xhtml', 'shtml', 'xht',
        'css', 'scss', 'sass', 'less', 'styl',
        'js', 'mjs', 'cjs', 'jsx',
        'ts', 'mts', 'cts', 'tsx',
        'vue', 'svelte', 'astro',

        // Data and markup
        'json', 'jsonc', 'json5', 'xml', 'xsd',
        'xsl', 'xslt', 'dtd', 'yaml', 'yml',
        'toml', 'csv', 'tsv', 'ini', 'properties',
        'graphql', 'gql', 'proto', 'plist',

        // Database
        'sql', 'ddl', 'dml', 'pgsql', 'mysql',
        'psql', 'prisma',

        // Configuration
        'conf', 'config', 'cfg', 'cnf',
        'env', 'dist', 'example', 'sample',
        'htaccess', 'htpasswd', 'user.ini',
        'editorconfig', 'gitignore', 'gitattributes',
        'gitmodules', 'gitconfig', 'npmrc',
        'nvmrc', 'browserslistrc', 'dockerignore',
        'nginx', 'vhost', 'service', 'socket',
        'target', 'mount', 'timer',

        // Shell
        'sh', 'bash', 'zsh', 'fish', 'ksh',
        'csh', 'bat', 'cmd', 'ps1', 'psm1',
        'psd1', 'vbs', 'wsf', 'reg', 'inf',

        // Python
        'py', 'pyw', 'pyi', 'pyx', 'pxd',
        'pxi', 'requirements',

        // Ruby and Perl
        'rb', 'rake', 'gemspec', 'pl', 'pm',
        'pod', 't',

        // Java and JVM
        'java', 'kt', 'kts', 'groovy',
        'gradle', 'scala', 'sc', 'clj',
        'cljs', 'cljc', 'edn',

        // C and C++
        'c', 'h', 'cc', 'cpp', 'cxx',
        'hh', 'hpp', 'hxx', 'ipp', 'inl',

        // C# and .NET
        'cs', 'csx', 'vb', 'fs', 'fsi',
        'fsx', 'razor', 'cshtml',

        // Mobile development
        'dart', 'swift', 'm', 'mm',
        'storyboard', 'xib', 'strings',

        // Systems programming
        'go', 'rs', 'zig', 'nim', 'nims',
        'asm', 's', 'v',

        // Other languages
        'lua', 'r', 'rmd', 'jl',
        'ex', 'exs', 'erl', 'hrl',
        'hs', 'lhs', 'elm', 'purs',
        'sol', 'vy', 'plsql', 'pks', 'pkb',
        'f', 'for', 'f90', 'f95',
        'cob', 'cbl', 'pas', 'pp',
        'lisp', 'lsp', 'scm', 'ss',
        'tcl', 'awk', 'sed',

        // Build and dependency
        'make', 'mk', 'mak', 'cmake',
        'lock', 'mod', 'sum', 'pom',
        'gemfile', 'podfile', 'fastfile',
        'procfile', 'brewfile',

        // API and testing
        'http', 'rest', 'wsdl', 'wadl',
        'feature', 'robot', 'spec', 'test',
        'snap',

        // Patches
        'diff', 'patch',

        // Technical documents
        'tex', 'latex', 'bib', 'cls',
        'sty', 'bst',

        // Other text
        'manifest', 'webmanifest', 'desktop',
        'list', 'lst', 'checksum',
        'sha256', 'sha512', 'md5'
    ];

    const isPHP = [
        'php', 'php3', 'php4', 'php5',
        'php7', 'php8', 'phtml', 'inc'
    ].includes(ext);

    const isArchive = archives.includes(ext);
    const isAudio = audio.includes(ext);
    const isVideo = video.includes(ext);
    const isImage = images.includes(ext);
    const isPDF = ext === 'pdf';

    const isText = isSpecial || editable.includes(ext);

    /* ---------------- MODAL OPEN ---------------- */

    title.textContent = name;

    body.innerHTML = `
        <div class="k8-padding k8-center">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <p>Loading file...</p>
        </div>
    `;

    if (modal.style.display !== 'block') {
        modal.style.display = 'block';
        v_blur();
    }

    /* ---------------- FILE INFORMATION ---------------- */

    let size = 0;
    let mime = '';

    try {
        const head = await fetch(url.href, {
            method: 'HEAD',
            credentials: 'same-origin'
        });

        if (head.ok) {
            size = Number(head.headers.get('content-length')) || 0;
            mime = head.headers.get('content-type') || '';
        }
    } catch (e) {
        console.warn('File metadata unavailable:', e);
    }

    // Do not interrupt preview if HEAD is unsupported.

    const metadata = `
        <div class="k8-padding k8-border-bottom">
            <div class="k8-strong">
                <i class="fa-solid fa-file"></i> ${safeName}
            </div>
            <small class="k8-text-grey">
                ${escapeHTML(ext.toUpperCase() || 'FILE')}
                &nbsp; | &nbsp; ${formatSize(size)}
                ${mime ? ' | ' + escapeHTML(mime) : ''}
            </small>
        </div>
    `;

    const download = `
        <div class="k8-padding k8-center k8-border-top">
            <a href="${safeURL}"
               download="${safeName}"
               class="k8-button k8-green">
                <i class="fa-solid fa-download"></i>
                Download ${safeName}
            </a>
        </div>
    `;

    const wrap = content => `
        ${metadata}
        <div class="k8-padding">${content}</div>
        ${download}
    `;

    // Viewer disabled: do not render media previews, but keep download available.
    if (!canView && (isAudio || isVideo || isPDF || isImage)) {
        body.innerHTML = wrap(`
            <div class="k8-center k8-padding">
                <i class="fa-solid fa-eye-slash fa-3x k8-text-grey"></i>
                <p>File preview is disabled.</p>
            </div>
        `);
        return;
    }

    /* ---------------- AUDIO PLAYER ---------------- */

    if (isAudio) {

        body.innerHTML = wrap(`
            <div class="k8-center k8-padding">
                <i class="fa-solid fa-music fa-4x k8-text-indigo"></i>
                <h4>${safeName}</h4>
            </div>

            <audio controls preload="metadata"
                   style="width:100%">
                <source src="${safeURL}">
                Audio playback is not supported.
            </audio>
        `);

        return;
    }

    /* ---------------- VIDEO PLAYER ---------------- */

    if (isVideo) {

        body.innerHTML = wrap(`
            <video controls preload="metadata"
                   playsinline
                   style="width:100%;max-height:65vh;
                   background:#000;object-fit:contain">
                <source src="${safeURL}">
                Video playback is not supported.
            </video>
        `);

        return;
    }

    /* ---------------- PDF VIEWER ---------------- */

    if (isPDF) {

        body.innerHTML = `
            ${metadata}

            <iframe
                src="${safeURL}"
                title="${safeName}"
                style="width:100%;height:70vh;border:0">
            </iframe>

            ${download}
        `;

        return;
    }

    /* ---------------- IMAGE VIEWER ---------------- */

    if (isImage) {

    body.innerHTML = wrap(`
        <div class="k8-padding">

            <div class="k8-row k8-middle k8-margin-bottom">
                <div class="k8-col s3">
                    <strong>Zoom</strong>
                </div>

                <div class="k8-col s7">
                    <input
                        type="range"
                        id="k8ImageZoom"
                        min="25"
                        max="300"
                        value="100"
                        step="5"
                        style="width:100%"
                    >
                </div>

                <div class="k8-col s2 k8-right-align">
                    <span id="k8ZoomValue">100%</span>
                </div>
            </div>

            <div id="k8ImageViewport"
                 style="
                    width:100%;
                    height:65vh;
                    overflow:auto;
                    background:#222;
                    text-align:center;
                 ">

                <img id="k8PreviewImage"
                     src="${safeURL}"
                     alt="${safeName}"
                     style="
                        width:100%;
                        max-width:none;
                        height:auto;
                        display:block;
                        margin:auto;
                     ">
            </div>
        </div>
    `);

    const zoom = body.querySelector('#k8ImageZoom');
    const image = body.querySelector('#k8PreviewImage');
    const zoomValue = body.querySelector('#k8ZoomValue');

    zoom.addEventListener('input', function () {
        image.style.width = this.value + '%';
        zoomValue.textContent = this.value + '%';
    });

    return;

}

    /* ---------------- ARCHIVE VIEWER ---------------- */

    if (isArchive) {

        body.innerHTML = wrap(`
            <div class="k8-center k8-padding">
                <i class="fa-solid fa-file-zipper
                          fa-5x k8-text-indigo"></i>

                <h3>${safeName}</h3>

                <h5>${ext.toUpperCase()} Archive</h5>

                <p>File size: ${formatSize(size)}</p>

                <p class="k8-text-grey">
                    Archive extraction and browsing are not
                    available in the browser preview.
                </p>
            </div>
        `);

        return;
    }

    /* ---------------- EDITOR ---------------- */

    if (isText) {

    body.innerHTML = `
        ${metadata}

        <div class="k8-padding">

            <div class="k8-row k8-middle k8-margin-bottom">

                <div class="k8-col s6">
                    ${canEdit ? `
                        <button type="button"
                                id="k8SaveFile"
                                class="k8-button k8-green"
                                disabled>
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Changes
                        </button>
                    ` : `
                        <span class="k8-text-grey">
                            <i class="fa-solid fa-lock"></i> Read-only mode
                        </span>
                    `}
                </div>

                <div class="k8-col s6 k8-right-align">
                    <small id="k8SaveStatus">Loading file...</small>
                </div>

            </div>

            <textarea
                id="k8FileEditor"
                spellcheck="false"
                readonly
            ></textarea>

        </div>

        ${download}
    `;

    const editor = body.querySelector('#k8FileEditor');
    const saveButton = body.querySelector('#k8SaveFile');
    const saveStatus = body.querySelector('#k8SaveStatus');

    let content = '';

    // PHP source requires your configured source endpoint
    if (isPHP) {

        try {
            // PHP source is served by the API handler in this same index.php.
            const sourceURL = new URL(location.pathname, location.href);
            sourceURL.searchParams.set('k8_action', 'source');
            sourceURL.searchParams.set('file', file);

            const response = await fetch(sourceURL.href, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            content = await response.text();

        } catch (e) {
            editor.value = 'Unable to load PHP source: ' + e.message;
            saveStatus.textContent = 'Load failed';
            return;
        }

    } else {

        try {
            const response = await fetch(url.href, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            content = await response.text();

            if (content.includes('\\u0000')) {
                throw new Error('Binary file detected.');
            }

        } catch (e) {
            editor.value = 'Unable to read file: ' + e.message;
            saveStatus.textContent = 'Load failed';
            return;
        }
    }

    editor.value = content;

editor.readOnly = !K8_CONFIG.editor;

    // Choose syntax highlighting mode
    const modes = {
        php: 'application/x-httpd-php',
        html: 'htmlmixed',
        htm: 'htmlmixed',
        xhtml: 'htmlmixed',
        css: 'css',
        scss: 'text/x-scss',
        js: 'javascript',
        mjs: 'javascript',
        cjs: 'javascript',
        jsx: 'javascript',
        ts: 'javascript',
        tsx: 'javascript',
        json: 'application/json',
        jsonc: 'application/json',
        xml: 'xml',
        yaml: 'yaml',
        yml: 'yaml',
        md: 'markdown',
        markdown: 'markdown',
        sql: 'text/x-sql',
        py: 'python',
        pyw: 'python',
        sh: 'shell',
        bash: 'shell',
        txt: 'text/plain',
        log: 'text/plain',
        conf: 'text/plain',
        ini: 'text/plain',
        java: 'text/x-java',
        c: 'text/x-csrc',
        cpp: 'text/x-c++src',
        h: 'text/x-csrc',
        cs: 'text/x-csharp'
    };

    const mode = modes[ext] || 'text/plain';

    // Attach CodeMirror to the existing textarea
    const codeEditor = CodeMirror.fromTextArea(editor, {
        mode: mode,
        theme: 'material-darker',
        lineNumbers: true,
        lineWrapping: false,
        indentUnit: 4,
        tabSize: 4,
        indentWithTabs: false,
        matchBrackets: true,
        autofocus: false,
        viewportMargin: Infinity,
        readOnly: !canEdit
    });

    // Explicitly enforce the permission on CodeMirror itself.
    codeEditor.setOption('readOnly', !canEdit);
    codeEditor.setSize('100%', '65vh');
    codeEditor.refresh();

    saveStatus.textContent = canEdit ? 'Ready' : 'Read-only';

    // Only create and bind save behaviour when editing is enabled.
    if (canEdit && saveButton) {
        saveButton.disabled = false;

        saveButton.addEventListener('click', async function () {

        const newContent = codeEditor.getValue();

        saveButton.disabled = true;
        saveStatus.textContent = 'Saving...';

        try {

            const formData = new FormData();

            formData.append('k8_action', 'save_file');
            formData.append('file', file);
            formData.append('content', newContent);

            const response = await fetch(location.pathname + location.search, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Save failed.');
            }

            saveStatus.textContent = 'Saved successfully';
            saveStatus.style.color = 'green';

        } catch (e) {

            saveStatus.textContent = e.message;
            saveStatus.style.color = 'red';

        } finally {
            saveButton.disabled = false;
        }
        });
    }

    return;

} /* —————- UNKNOWN FILE —————- */

    body.innerHTML = wrap(`
        <div class="k8-center k8-padding">
            <i class="fa-solid fa-file-circle-question
                      fa-5x k8-text-indigo"></i>

            <h3>${safeName}</h3>

            <h5>${ext.toUpperCase() || 'UNKNOWN FORMAT'}</h5>

            <p>${formatSize(size)}</p>

            <p class="k8-text-grey">
                No browser preview is available for this file type.
            </p>
        </div>
    `);

}


	</script>
</body></html>