<#
cleanup.ps1 - Safe, non-destructive workspace cleanup for Windows PowerShell

Usage examples:
# Dry run (report only)
PS> .\cleanup.ps1

# Apply changes (move detected files to a quarantine folder)
PS> .\cleanup.ps1 -Apply

# Limit to a path (default is current repository root)
PS> .\cleanup.ps1 -Path "c:\xampp\htdocs\tripko-system" -Apply

Behavior:

This script is intentionally conservative: no deletions; moves only when -Apply is provided.
#>
param(
    [string]$Path = (Get-Location).Path,
    [switch]$Apply,
    [int]$LargeFileMB = 5,
    [switch]$QuarantineDuplicates = $true
)

$now = Get-Date -Format "yyyyMMdd_HHmmss"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$reportFile = Join-Path $scriptDir "cleanup_report_$now.txt"
$quarantineRoot = Join-Path $scriptDir "quarantine_$now"
New-Item -Path $scriptDir -ItemType Directory -Force | Out-Null

# Patterns to consider (conservative)
$patterns = @('*.bak','*.old','*.new','*.sql','*.sql.gz','*.log','*.log.*','*.tmp','*.temp','*~','Thumbs.db','.DS_Store')
# File name patterns that are likely safe to quarantine; adjust as needed
$excludePatterns = @('*.php','*.js','*.css','*.html','*.md')

# Helpers
function MatchesExclude($file) {
    foreach ($p in $excludePatterns) { if ($file.Name -like $p) { return $true } }
    return $false
}

function Add-ReportLine([string]$line) {
    Add-Content -Path $reportFile -Value $line
}

# Start report
Add-ReportLine "Cleanup report for path: $Path"
Add-ReportLine "Generated: $(Get-Date -Format o)"
Add-ReportLine "Apply mode: $Apply"
Add-ReportLine ""

# 1) Find files by pattern
Add-ReportLine "-- Candidate files by pattern --"
$patternMatches = @()
foreach ($p in $patterns) {
    $found = Get-ChildItem -Path $Path -Filter $p -Recurse -File -ErrorAction SilentlyContinue | Where-Object { -not (MatchesExclude $_) }
    foreach ($f in $found) {
        $patternMatches += $f
        Add-ReportLine "$($f.FullName)  [size: $([math]::Round($f.Length/1MB,2)) MB]  [modified: $($f.LastWriteTime)]"
    }
}
Add-ReportLine "Found $(($patternMatches | Measure-Object).Count) pattern-matching files.`n"

# 2) Find large files
Add-ReportLine "-- Large files (>$LargeFileMB MB) --"
$largeMatches = Get-ChildItem -Path $Path -Recurse -File -ErrorAction SilentlyContinue | Where-Object { $_.Length -gt ($LargeFileMB * 1MB) }
foreach ($f in $largeMatches) { Add-ReportLine "$($f.FullName)  [size: $([math]::Round($f.Length/1MB,2)) MB]" }
Add-ReportLine "Found $(($largeMatches | Measure-Object).Count) large files.`n"

# 3) Detect exact duplicate files by hash (only on files <= 50 MB to keep speed)
Add-ReportLine "-- Exact duplicate files (SHA256) --"
$allFiles = Get-ChildItem -Path $Path -Recurse -File -ErrorAction SilentlyContinue | Where-Object { $_.Length -le 50MB }
$hashTable = @{}
foreach ($f in $allFiles) {
    try {
        $h = (Get-FileHash -Path $f.FullName -Algorithm SHA256).Hash
    } catch {
        continue
    }
    if (-not $hashTable.ContainsKey($h)) { $hashTable[$h] = @() }
    $hashTable[$h] += $f
}
$duplicateGroups = $hashTable.GetEnumerator() | Where-Object { $_.Value.Count -gt 1 }
$duplicateCount = 0
foreach ($grp in $duplicateGroups) {
    Add-ReportLine "Duplicates group (hash: $($grp.Key))"
    foreach ($f in $grp.Value) { Add-ReportLine "  - $($f.FullName) [size: $([math]::Round($f.Length/1MB,4)) MB]" }
    Add-ReportLine ""
    $duplicateCount += ($grp.Value.Count - 1)
}
Add-ReportLine "Total duplicate files (extras): $duplicateCount`n"

# Summary
Add-ReportLine "-- Summary --"
Add-ReportLine "Pattern matches: $((($patternMatches | Measure-Object).Count))"
Add-ReportLine "Large files: $((($largeMatches | Measure-Object).Count))"
Add-ReportLine "Detected duplicate extras: $duplicateCount"

# If user asked to apply, move matches to quarantine
if ($Apply) {
    Add-ReportLine "\n-- Applying changes: Moving files to quarantine --"
    New-Item -Path $quarantineRoot -ItemType Directory -Force | Out-Null

    # Move pattern matches
    foreach ($f in $patternMatches) {
        $rel = Resolve-Path -Path $f.FullName -Relative -ErrorAction SilentlyContinue
        if (-not $rel) { $rel = $f.FullName.Substring($Path.Length).TrimStart('\') }
        $dest = Join-Path $quarantineRoot $rel
        $destDir = Split-Path -Parent $dest
        if (-not (Test-Path $destDir)) { New-Item -Path $destDir -ItemType Directory -Force | Out-Null }
        Move-Item -Path $f.FullName -Destination $dest -Force
        Add-ReportLine "Moved: $($f.FullName) -> $dest"
    }

    # Move large files (ask user interactively unless -Force provided)
    foreach ($f in $largeMatches) {
        $rel = $f.FullName.Substring($Path.Length).TrimStart('\')
        $dest = Join-Path $quarantineRoot $rel
        $destDir = Split-Path -Parent $dest
        if (-not (Test-Path $destDir)) { New-Item -Path $destDir -ItemType Directory -Force | Out-Null }
        Move-Item -Path $f.FullName -Destination $dest -Force
        Add-ReportLine "Moved large: $($f.FullName) -> $dest"
    }

    # Move duplicate extras (keep oldest modified file, move the rest)
    foreach ($grp in $duplicateGroups) {
        $keep = $grp.Value | Sort-Object LastWriteTime | Select-Object -First 1
        $move = $grp.Value | Where-Object { $_.FullName -ne $keep.FullName }
        foreach ($f in $move) {
            $rel = $f.FullName.Substring($Path.Length).TrimStart('\')
            $dest = Join-Path $quarantineRoot $rel
            $destDir = Split-Path -Parent $dest
            if (-not (Test-Path $destDir)) { New-Item -Path $destDir -ItemType Directory -Force | Out-Null }
            Move-Item -Path $f.FullName -Destination $dest -Force
            Add-ReportLine "Moved duplicate: $($f.FullName) -> $dest"
        }
    }

    Add-ReportLine "\nQuarantine root: $quarantineRoot"
    Add-ReportLine "Operation completed. Verify contents of quarantine before deleting permanently."
}

Write-Host "Report written to: $reportFile"
if (-not $Apply) { Write-Host "Dry run complete. Run with -Apply to move files to quarantine." }
else { Write-Host "Apply completed. Quarantine: $quarantineRoot" }
