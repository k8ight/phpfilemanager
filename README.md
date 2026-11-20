# PHP File Manager

A lightweight, browser-based file manager built with PHP, the K8 UI framework, Font Awesome Free and CodeMirror.

It provides a web interface for browsing directories, viewing supported files, editing text and source files, creating entries and deleting files or folders.

## Features

### Available

- [x] **File and directory browsing** – Browse directory contents through the browser.
- [x] **Directory navigation** – Open directories using the `dir` request parameter.
- [x] **Configurable root directory** – Set the directory managed by the file manager in the PHP configuration.
- [x] **File information** – View file type, size, permissions and modification date.
- [x] **File preview** – Preview supported image, audio, video and PDF files.
- [x] **Text and source-code editor** – Edit supported text and code files with CodeMirror.
- [x] **Read-only mode** – Disable editing while retaining supported viewing functionality.
- [x] **Create files** – Create files, including files without an extension.
- [x] **Create folders** – Create directories in the current location.
- [x] **Recursive deletion** – Review a file or folder and its contents before permanent deletion.
- [x] **Directory size information** – Display recursive directory size in the deletion preview.
- [x] **File permissions** – Display filesystem permission information.
- [x] **Current-directory search** – Filter the currently displayed table using JavaScript.
- [x] **Configurable features** – Enable or disable editor, viewer, creation and deletion features.

### In development

- [ ] **System-wide indexed search** – Search across directories and files beyond the currently displayed directory.

> **Note:** The current search field filters only the rows already rendered for the open directory. It does not perform a server-side or system-wide search.

## Screenshots

Screenshots can be added to a `screenshots/` directory and linked here.

Suggested screenshots:

- File browser
- File preview
- Code editor
- Create file/folder dialog
- Recursive delete confirmation

## Requirements

- PHP 8.0 or newer
- Apache, Nginx or another PHP-compatible web server
- PHP filesystem permissions appropriate for the directories being managed
- Internet access for the CDN-hosted frontend libraries

The web server's PHP process must have the required read, write and directory permissions for the operations you enable.

## Installation

Clone the repository:

```bash
git clone https://github.com/k8ight/phpfilemanager.git
```

Place the project directory in a location served by your web server. Ensure the required local assets are present, then open the corresponding URL in a browser.

Example:

```text
https://your-domain.com/phpfilemanager/
```

## Project Structure

```text
phpfilemanager/
├── index.php       # Main application and request handlers
├── k8.css          # K8 UI stylesheet
├── k8.js           # K8 UI JavaScript and modal functions
├── favicon.ico     # Optional browser icon
└── README.md
```

The current application keeps its PHP request handlers and page-level JavaScript in `index.php`; it does not require separate local API files for the documented core features.

## Configuration

The configuration is defined near the beginning of `index.php`:

```php
$config = [
    "dir"         => __DIR__,
    "editor"      => true,
    "viewer"      => true,
    "create_file" => true,
    "delete_file" => true
];

chdir($config['dir']);
```

### Root directory

The `dir` option sets the file manager's starting/root directory.

- By default, `__DIR__` uses the directory containing `index.php`.
- You can provide another absolute filesystem path, for example `"/var/www/example/public_html"`.
- `chdir($config['dir'])` changes PHP's current working directory for that request. This allows the existing relative directory operations and folder navigation to work from the configured root.

Make sure the PHP process has the required permissions for the configured directory. The configured path should exist and be a directory.

### Feature options

| Option | Description |
|---|---|
| `editor` | Enables or disables editing and saving supported text/source files. |
| `viewer` | Enables or disables supported media and document previews. |
| `create_file` | Enables or disables file and folder creation. |
| `delete_file` | Enables or disables file and folder deletion. |

Set an option to `false` to disable the corresponding feature. These settings are feature switches; they are **not** a replacement for authentication or access control.

## Frontend Dependencies

| Library | Purpose |
|---|---|
| Font Awesome Free 6.4.2 | Icons |
| CodeMirror 5.65.16 | Text and source-code editing |
| K8 CSS | Application styling |
| K8 JS | Modal and UI functions |

Font Awesome and CodeMirror are loaded from CDNs. The K8 CSS and JavaScript files are included locally in the project.

An internet connection is therefore required for the CDN-hosted dependencies unless you change the page to use local copies.

## File Browser and Navigation

The main browser lists files and directories and displays available metadata such as type, permissions, size and modification date.

Directory navigation uses the `dir` request parameter relative to the configured root directory. For example, to open a subdirectory named `uploads`:

```text
https://your-domain.com/phpfilemanager/?dir=uploads
```

The current directory is rendered by PHP; the search box filters the rows currently present in the page using JavaScript.

The application should be deployed with filesystem permissions appropriate to the directories that the PHP process is expected to manage. Do not assume that hiding a directory in the interface makes it inaccessible.

## File Viewer and Editor

The viewer supports browser-previewable formats, including:

- Images
- Audio files
- Video files
- PDF documents

Supported text and source-code files can be opened in the CodeMirror editor.

When `editor` is disabled, the editor is read-only and the save control is not provided. When `viewer` is disabled, supported media previews are disabled. These options are independent.

### PHP source files

PHP files are executed by the web server when requested normally. The browser viewer does not obtain PHP source code from that normal response.

The editor contains an optional integration point named `window.K8_FILE_SOURCE_ENDPOINT` for a separate source-reading endpoint. Configure that endpoint only if you have implemented and secured it. It is not included as a standalone endpoint in this repository.

## Create Files and Folders

The create dialog provides separate actions for:

- Creating a file
- Creating a folder

File names can be extensionless. Creation is handled by the PHP request handler in `index.php` and is controlled by the `create_file` configuration option.

## Recursive Deletion

The delete action supports files and directories. Before deletion, the confirmation dialog requests a recursive preview and displays the discovered contents and summary information, including:

- File and folder counts
- Symbolic-link count
- Individual file sizes
- Total size of regular files in the selected tree

Deletion is permanent. There is no recycle bin or automatic restore function. Check the selected path and preview carefully before confirming.

Symbolic links are identified separately; the deletion implementation is intended not to traverse a link to its target.

## Search

### Current-directory search

The search field uses JavaScript to filter the rows in the currently open directory. It does not search the complete filesystem or directories that have not been opened.

### System-wide indexed search

A server-side indexed search across directories and files is planned and remains under development.

## Security and Deployment

**Do not expose this file manager to the public internet without adding suitable access protection.**

This application can interact with the filesystem using the permissions of the PHP process. A mistake or unauthorized request can therefore have consequences beyond the browser interface.

Before production deployment:

1. Put the application behind authentication (for example, web-server authentication or an authenticated application gateway).
2. Restrict access to trusted users and, where practical, trusted IP addresses or a private network.
3. Use HTTPS.
4. Run PHP with the least filesystem privileges needed for the intended directories and operations.
5. Keep backups of files that may be edited or deleted.
6. Disable features that are not required by setting their configuration options to `false`.
7. Keep PHP error display disabled in production and send errors to server logs.
8. Review and test path validation for every filesystem operation, especially when configuring a root directory outside the application directory.

The feature configuration array does not provide user authentication, authorization, or CSRF protection. Add appropriate access control and request protection at the application or web-server layer before public deployment.

## Roadmap

- [ ] System-wide indexed search
- [ ] Additional file-management operations
- [ ] Further UI and usability improvements
- [ ] Additional security hardening and error handling
- [ ] Add project screenshots and usage examples

## License

This project is licensed under the **GNU General Public License v3.0 (GPL-3.0)**.
See the [LICENSE](LICENSE) file for details.
