# modtrash

A macOS-only Laravel Zero CLI for finding projects with `node_modules` folders and moving those folders to Trash after interactive confirmation.

This project is intentionally conservative. Version 1 scans the folder you pass, prompts before every cleanup action, and targets only folders named exactly `node_modules`.

## Requirements

- macOS
- PHP 8.2 or newer
- Composer, when installing from source

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/phaybein/modtrash.git
cd modtrash
composer install
```

Run it from the project directory:

```bash
php modtrash cleanup /path/to/projects
```

To make it available from any directory during local development, create a symlink from a directory on your `PATH`:

```bash
ln -s /path/to/modtrash/modtrash ~/.local/bin/modtrash
```

Then run:

```bash
modtrash cleanup /path/to/projects
```

## Usage

```bash
php modtrash cleanup /path/to/projects
```

Examples:

```bash
php modtrash cleanup ~/Dropbox/dev/projects/_in_progress
php modtrash cleanup ~/Dropbox/dev/projects/_in_progress/bank_app
```

## Folder Modes

modtrash supports two folder shapes.

Pass a parent folder when you want to scan its direct child projects:

```text
_in_progress/
  example_app/
    node_modules/
  modtrash/
  another_project/
    node_modules/
```

Pass a project folder when that folder directly contains `node_modules`:

```text
example_app/
  node_modules/
  package.json
  src/
```

If the folder you pass directly contains `node_modules`, that folder is treated as the cleanup candidate. Otherwise, modtrash scans the folder's direct child folders.

For each matching project, the command will show the project path, the `node_modules` path, and the estimated size before asking whether to move it to Trash.

Choices:

- `y` moves that project's `node_modules` folder to Trash.
- `n` skips it.
- `q` stops prompting and prints a summary.

## Example Output

```text
Project: example_app
Path: /Users/example/dev/example_app
node_modules: /Users/example/dev/example_app/node_modules
Size: 842 MB

Move node_modules to Trash? [y/N/q]:
```

After the run, modtrash prints a summary with projects checked, folders found, folders moved, skipped prompts, failures, and estimated moved size.

## Safety

- Only targets folders named exactly `node_modules`.
- Never deletes the project folder itself.
- Refuses symlinked `node_modules` folders.
- Moves selected folders to macOS Trash instead of permanently deleting them.
- Defaults to skip when pressing Enter.
- Shows the exact path before asking for confirmation.

## Scope

- macOS only for version 1.
- Direct project folder or one-level child project scan only.
- Interactive cleanup only.
- Moves to Trash instead of permanent deletion.
- Only targets `node_modules`.

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
./vendor/bin/pest
```

Format code:

```bash
./vendor/bin/pint
```

## Product Plan

See [PRD.md](PRD.md).

## License

MIT
