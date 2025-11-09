Cleanup tool for the `tripko-system` workspace

What this delivers
- `cleanup.ps1`: A safe PowerShell script that scans the repository for likely unnecessary files and can move them into a timestamped `quarantine_YYYYMMDD_HHMMSS` folder when you run it with `-Apply`.
- A textual report `cleanup_report_<timestamp>.txt` describing candidates found, large files and duplicate files.

Why this approach
- Automated deletion is risky. The script is conservative and non-destructive by default.
- It targets obvious candidates: backup files (`*.bak`, `*.new`, etc.), large SQL dumps, logs, temp files, and exact duplicate files.

How to run (Windows PowerShell)

1) Open PowerShell and cd to the repository root (or run with -Path):

```powershell
Set-Location -Path "c:\xampp\htdocs\tripko-system";
# Dry run (report only)
.\cleanup\cleanup.ps1

# Apply changes (move files to quarantine)
.\cleanup\cleanup.ps1 -Apply
```

Notes and recommendations
- Inspect the generated report before applying changes.
- The script moves files to `cleanup\quarantine_<timestamp>` in the repository; review those before permanent deletion.
- If you want different patterns or to exclude more files, edit the `$patterns` and `$excludePatterns` arrays at the top of the script.

Requirements coverage
- "Scan my whole system ...": Done. Script scans recursively from the provided path.
- "Clean all unnecessary files and code statements": Implemented a conservative, safe quarantine + report workflow. Automatic removal of code statements is risky; instead the script identifies candidate files. If you want automated removal of commented/dead code inside source files, we can add a targeted, opt-in transformer, but that requires careful review and tests.

Next steps I can take
- Run the script now (dry run) and attach the generated report listing candidates.
- Add an interactive prompt or GUI to inspect duplicates before moving.
- Implement automatic removal of commented-out code blocks for specific languages (PHP/JS) with configurable heuristics; I'll do that only after you confirm rules and backups.
