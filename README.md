# PHP File Manager

A lightweight, web-based PHP file manager with a clean interface powered by **Font Awesome Free** and the K8 UI framework.

Browse and manage files and directories directly from your web browser without requiring a separate desktop application.

## Features

### Available

- [x] **File Browsing** – Browse files and directories through a web interface.
- [x] **File Information** – View file sizes, permissions, types and modification dates.
- [x] **File Preview** – Preview supported media and document formats.
- [x] **Code Editor** – Edit supported text and source-code files using CodeMirror.
- [x] **Read-only Mode** – Configure the editor and viewer independently.
- [x] **Create Files** – Create new files, including extensionless files.
- [x] **Create Folders** – Create directories in the current location.
- [x] **Recursive Delete** – Delete files and directories, including nested contents, with a confirmation preview.
- [x] **Directory Size** – Calculate recursive directory sizes.
- [x] **File Permissions** – View file and directory permissions.
- [x] **File Upload** – Upload files through the web interface.
- [x] **Directory Search** – Search files and folders in the current directory using JavaScript.
- [x] **Configurable Features** – Enable or disable individual file manager features.

### In Development

- [ ] **System-wide Search** – Indexed search across directories and files, beyond the currently opened directory.

## Screenshots

Add screenshots of the file manager interface here.

```text
screenshots/
├── file-browser.png
├── file-preview.png
├── code-editor.png
├── create-entry.png
└── delete-confirmation.png
```

## Requirements

- PHP 8.0 or newer
- Web server (Apache, Nginx, etc.)
- PHP filesystem permissions for the directories you want to manage
- Internet connection for CDN-hosted frontend dependencies

## Installation

1. Clone or download this repository.

```bash
git clone YOUR_REPOSITORY_URL
```

2. Copy the project files into your web server's document root.

3. Ensure the PHP process has the necessary filesystem permissions.

4. Open the file manager through your browser.

```text
https://your-domain.com/filemanager/
```

## Project Structure

```text
phpfilemanager/
├── index.php       # Main application
├── k8.css          # K8 UI stylesheet
├── k8.js           # K8 UI JavaScript
├── favicon.ico     # Optional browser icon
└── README.md
```

The application does not require separate local PHP API files or JavaScript modules for its current features.

## Configuration

Features can be enabled or disabled through the configuration array in `index.php`.

```php
$config = [
    "editor"      => true,
    "viewer"      => true,
    "create_file" => true,
    "delete_file" => true
];
```

| Option | Description |
|---|---|
| `editor` | Enables or disables file editing and saving. |
| `viewer` | Enables or disables supported file previews. |
| `create_file` | Enables or disables file and folder creation. |
| `delete_file` | Enables or disables file and folder deletion. |

Set an option to `false` to disable that feature.

## Frontend Dependencies

The project uses the following frontend libraries:

| Library | Purpose |
|---|---|
| Font Awesome Free 6.4.2 | Icons |
| K8 CSS | Interface styling |
| K8 JS | Modal and UI functions |
| CodeMirror 5.65.16 | Source-code editing |

Font Awesome and CodeMirror are loaded from external CDNs. K8 CSS and K8 JS are included locally.

## Search

### Current Directory Search

The current search feature uses JavaScript to filter the files and folders displayed in the currently opened directory.

### System-wide Indexed Search

A broader search system is planned to index files and directories across the managed filesystem. This feature is currently under development.

## Recursive Deletion

The delete feature supports both files and directories.

Before deleting a directory, the confirmation window displays:

- Recursive directory contents.
- File and folder counts.
- Individual file sizes.
- Total directory size.
- Symbolic links.

Deletion is permanent. The application does not provide a recycle bin or automatic recovery.

## Security Notice

**Do not expose this file manager publicly without proper access protection.**

Recommended precautions:

- Protect the application with authentication.
- Restrict access to trusted users and IP addresses.
- Keep
