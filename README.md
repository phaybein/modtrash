# modtrash

A macOS-only Laravel Zero CLI for finding projects with `node_modules` folders and moving those folders to Trash after interactive confirmation.

This project is intentionally conservative. Version 1 scans the folder you pass, prompts before every cleanup action, and targets only folders named exactly `node_modules`.

## Usage

```bash
php modtrash cleanup /path/to/projects
```

Examples:

```bash
php modtrash cleanup ~/Dropbox/dev/projects/_in_progress
php modtrash cleanup ~/Dropbox/dev/projects/_in_progress/bank_app
```

If the folder you pass directly contains `node_modules`, that folder is treated as the cleanup candidate. Otherwise, modtrash scans the folder's direct child folders.

For each matching project, the command will show the project path, the `node_modules` path, and the estimated size before asking whether to move it to Trash.

Choices:

- `y` moves that project's `node_modules` folder to Trash.
- `n` skips it.
- `q` stops prompting and prints a summary.

## Scope

- macOS only for version 1.
- Direct project folder or one-level child project scan only.
- Interactive cleanup only.
- Moves to Trash instead of permanent deletion.
- Only targets `node_modules`.

## Product Plan

See [PRD.md](PRD.md).

## License

MIT
