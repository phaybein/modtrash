# modtrash

A macOS-only Laravel Zero CLI for finding direct child projects with `node_modules` folders and moving those folders to Trash after interactive confirmation.

This project is intentionally conservative. Version 1 scans one level down only, prompts before every cleanup action, and targets only folders named exactly `node_modules`.

## Planned Usage

```bash
php modtrash cleanup /path/to/projects
```

Example:

```bash
php modtrash cleanup ~/Dropbox/dev/projects/_in_progress
```

For each matching project, the command will show the project path, the `node_modules` path, and the estimated size before asking whether to move it to Trash.

## Scope

- macOS only for version 1.
- One-level scan only.
- Interactive cleanup only.
- Moves to Trash instead of permanent deletion.
- Only targets `node_modules`.

## Product Plan

See [PRD.md](PRD.md).

## License

MIT
