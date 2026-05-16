# PRD: modtrash

## Summary

Build an open source Laravel Zero CLI app that scans a user-provided folder for direct child projects containing a `node_modules` directory.

For each matching project, the app prompts the user before moving that project's `node_modules` folder to Trash.

The goal is to safely free disk space from old JavaScript projects without permanently deleting files by default.

## Problem

Old projects often keep large `node_modules` folders even when they are not actively being used.

These folders can consume significant disk space. Since dependencies can usually be restored later with `npm install`, the user wants a safe cleanup tool that finds these folders and asks before removing them.

## Target User

The primary user is a developer with many local project folders.

They understand that `node_modules` can be regenerated, but they still want an explicit confirmation step before removing anything.

## Goals

- Accept a folder path from the command line.
- Scan only one level down from the provided folder.
- Detect projects that contain a direct `node_modules` child folder.
- Prompt interactively for each project that has `node_modules`.
- Move selected `node_modules` folders to Trash.
- Show a final summary of what was found, skipped, and moved.
- Keep the first version focused only on `node_modules`.
- Package the tool as an open source Laravel Zero CLI app.
- Support macOS only in version 1.

## Non-Goals

- Do not recursively scan all nested descendants in version 1.
- Do not permanently delete folders in version 1.
- Do not delete other dependency or build folders such as `vendor`, `.venv`, `.next`, `dist`, or `build`.
- Do not add non-interactive bulk deletion in version 1.
- Do not run project-specific cleanup commands.
- Do not run `npm install`, `npm prune`, or package-manager commands.

## Example Usage

```bash
modtrash cleanup /Users/mauromartinez/Dropbox/dev/projects/_in_progress
```

The project and command name is `modtrash`.

## User Flow

1. User runs the CLI with a target folder.
2. App validates that the folder exists and is readable.
3. App scans direct child folders only.
4. App checks whether each direct child folder contains `node_modules`.
5. App skips projects without `node_modules`.
6. For each project with `node_modules`, app displays:
   - project name
   - project path
   - `node_modules` path
   - estimated folder size
7. App asks whether to move that `node_modules` folder to Trash.
8. User chooses yes, no, or quit.
9. App moves approved folders to Trash.
10. App prints a summary.

## Prompt Behavior

For each matching project:

```text
Project: my-old-app
Path: /Users/example/dev/my-old-app
node_modules: /Users/example/dev/my-old-app/node_modules
Size: 842 MB

Move node_modules to Trash? [y/N/q]
```

Choices:

- `y`: move this `node_modules` folder to Trash.
- `n`: skip this project.
- empty input: default to skip.
- `q`: stop scanning and show summary.

## Functional Requirements

### Folder Input

- The app must require one folder path argument.
- The app must expand `~` when used in the path.
- The app must reject missing or unreadable folders with a clear error.

### One-Level Scanning

- The app must inspect only the direct children of the provided folder.
- A direct child counts as a project candidate only if it is a directory.
- The app must check for a direct child named `node_modules` inside each project candidate.

Example:

```text
/root
  /project-a
    /node_modules
  /project-b
  /group
    /project-c
      /node_modules
```

If the user passes `/root`, version 1 should find `project-a` only.

It should not find `group/project-c` because that requires deeper recursive scanning.

### Size Calculation

- The app should estimate the size of each discovered `node_modules` folder before prompting.
- If size calculation fails, the app should still allow the user to decide.
- Size should be displayed in human-readable units.

### Trash Behavior

- Version 1 should move folders to Trash, not permanently delete them.
- Version 1 officially supports macOS only.
- The first implementation should support the current user's local Trash.
- Cross-platform Trash support can be designed behind an abstraction, but Linux and Windows support are out of scope for version 1.
- If Trash is unavailable or unsupported, the app should refuse to delete and explain the limitation.

### Summary

At the end of the run, show:

- total projects checked
- total `node_modules` folders found
- total moved to Trash
- total skipped
- total failed
- estimated space moved to Trash

## Safety Requirements

- The app must only target folders named exactly `node_modules`.
- The app must never delete the project folder itself.
- The app must never delete files outside the selected `node_modules` folder.
- The default answer must be no.
- The app must not follow symlinks into other directories.
- The app must handle permission errors without crashing the whole command.
- The app must show the exact path before asking for confirmation.

## Technical Direction

Use Laravel Zero because this is intended to become an open source CLI project.

Laravel Zero gives the project a clean command structure, console prompts, testing support, dependency management, and a familiar Laravel-style architecture.

Suggested app structure:

```text
app/
  Commands/
    CleanupNodeModulesCommand.php
  Services/
    ProjectScanner.php
    NodeModulesSizer.php
    TrashManager.php
  ValueObjects/
    CleanupCandidate.php
```

Suggested command signature:

```php
cleanup {path : Parent folder to scan}
```

Possible future flags:

```bash
--dry-run
--recursive
--yes
--max-depth=2
```

These flags are intentionally out of scope for version 1.

## Open Source Considerations

- Include a clear README with installation and usage examples.
- Include a strong safety warning that the tool moves `node_modules` to Trash.
- Include supported platform notes.
- Document macOS as the only supported platform for version 1.
- Use a permissive license such as MIT unless there is a reason to choose otherwise.
- Avoid collecting telemetry.
- Keep the command behavior predictable and boring.

## Implementation Phases

### Phase 1: Project Setup

Create a new Laravel Zero app.

Decide the package name, command name, and license.

Acceptance criteria:

- Laravel Zero app boots locally.
- Main cleanup command exists.
- README has initial usage notes.

### Phase 2: One-Level Scanner

Implement a scanner that accepts a root folder and returns direct child folders that contain `node_modules`.

Acceptance criteria:

- Missing folder gives a clear error.
- Unreadable folder gives a clear error.
- Direct child projects with `node_modules` are detected.
- Nested projects deeper than one level are ignored.
- Projects without `node_modules` are skipped silently.

### Phase 3: Interactive Review

Add interactive prompts for each discovered candidate.

Acceptance criteria:

- Each prompt shows project name and paths.
- Default choice is skip.
- User can skip, approve, or quit.
- Quit stops further prompts and prints a summary.

### Phase 4: Size Reporting

Add folder size estimation before each prompt.

Acceptance criteria:

- Size is displayed in human-readable units.
- Size failures are shown as unknown.
- Failed size calculation does not block cleanup.

### Phase 5: Move to Trash

Implement Trash movement for the first supported platform.

Acceptance criteria:

- Approved `node_modules` folders are moved to Trash.
- Unsupported platforms refuse to delete safely.
- Failed Trash operations are reported and counted.
- The project folder remains untouched.

### Phase 6: Final Summary and Polish

Add the final run summary and clean CLI output.

Acceptance criteria:

- Summary includes checked, found, moved, skipped, failed, and estimated space moved.
- Exit codes are meaningful.
- README explains limitations and recovery expectations.

### Phase 7: Release Prep

Prepare the repository for open source use.

Acceptance criteria:

- MIT license is added, unless another license is chosen.
- README includes install, usage, safety, and platform support.
- Basic command-level tests are added before public release.
- Packaged binary or Composer installation path is documented.

## Questions Before Implementation

No open product questions remain before implementation.
