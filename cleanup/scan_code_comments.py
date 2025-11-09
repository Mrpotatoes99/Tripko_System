#!/usr/bin/env python3
"""
scan_code_comments.py

Scans repository files (.php, .js, .html) for large commented-out blocks and sequences
of single-line comments that likely represent removed or dead code.

This script is non-destructive: it only produces a report (JSON and TXT) under the cleanup/ folder.

Usage:
    python scan_code_comments.py [path] [min_lines]

Example:
    python scan_code_comments.py "C:\\xampp\\htdocs\\tripko-system" 8

Outputs:
- cleanup/commented_blocks_report_<timestamp>.json
- cleanup/commented_blocks_report_<timestamp>.txt

"""
import os
import re
import sys
import json
from datetime import datetime

import argparse

parser = argparse.ArgumentParser(description='Scan and optionally remove large commented-out blocks')
parser.add_argument('path', nargs='?', help='Path to scan (defaults to repo root)', default=None)
parser.add_argument('--min-lines', type=int, default=8, help='Minimum consecutive comment lines to consider')
parser.add_argument('--apply', action='store_true', help='Apply changes (create backups and remove detected blocks)')
args = parser.parse_args()

ROOT = os.path.abspath(args.path) if args.path else os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
MIN_LINES = args.min_lines
APPLY = args.apply
OUT_DIR = os.path.dirname(__file__)
TS = datetime.now().strftime('%Y%m%d_%H%M%S')
OUT_JSON = os.path.join(OUT_DIR, f'commented_blocks_report_{TS}.json')
OUT_TXT = os.path.join(OUT_DIR, f'commented_blocks_report_{TS}.txt')
BACKUP_ROOT = os.path.join(OUT_DIR, f'originals_{TS}')

EXTENSIONS = ['.php', '.js', '.html', '.htm']
EXCLUDE_FOLDERS = ['.git', 'node_modules', 'vendor', 'cleanup']

BLOCK_PATTERNS = [
    # C-style block comment: /* ... */
    (re.compile(r'/\*'), re.compile(r'\*/')),
    # HTML comment: <!-- ... -->
    (re.compile(r'<!--'), re.compile(r'-->')),
]

SINGLE_LINE_PATTERNS = [
    # JS/PHP single-line //
    re.compile(r'^\s*//'),
    # PHP shell-style #
    re.compile(r'^\s*#'),
]

results = []

def should_scan_file(path):
    _, ext = os.path.splitext(path)
    if ext.lower() in EXTENSIONS:
        return True
    return False

def walk_files(root):
    for dirpath, dirnames, filenames in os.walk(root):
        # skip excluded
        parts = dirpath.split(os.sep)
        if any(p in EXCLUDE_FOLDERS for p in parts):
            continue
        for fn in filenames:
            full = os.path.join(dirpath, fn)
            if should_scan_file(full):
                yield full


def find_block_comments(lines):
    matches = []
    linecount = len(lines)
    for start_pat, end_pat in BLOCK_PATTERNS:
        i = 0
        while i < linecount:
            if start_pat.search(lines[i]):
                # found start
                j = i
                while j < linecount and not end_pat.search(lines[j]):
                    j += 1
                if j < linecount:
                    # include end line
                    length = j - i + 1
                    if length >= MIN_LINES:
                        snippet = ''.join(lines[i:max(i+5,j+1)])
                        matches.append({'type':'block','start':i+1,'end':j+1,'length':length,'preview':snippet})
                    i = j + 1
                else:
                    # unterminated - treat until EOF
                    length = linecount - i
                    if length >= MIN_LINES:
                        snippet = ''.join(lines[i:i+5])
                        matches.append({'type':'block_unterminated','start':i+1,'end':linecount,'length':length,'preview':snippet})
                    break
            else:
                i += 1
    return matches


def find_multi_single_line_comments(lines):
    matches = []
    i = 0
    linecount = len(lines)
    while i < linecount:
        if any(pat.search(lines[i]) for pat in SINGLE_LINE_PATTERNS):
            j = i
            while j < linecount and any(pat.search(lines[j]) for pat in SINGLE_LINE_PATTERNS):
                j += 1
            length = j - i
            if length >= MIN_LINES:
                snippet = ''.join(lines[i:min(j,i+5)])
                matches.append({'type':'single_seq','start':i+1,'end':j,'length':length,'preview':snippet})
            i = j
        else:
            i += 1
    return matches


def analyze_file(path):
    try:
        with open(path, 'r', encoding='utf-8', errors='ignore') as fh:
            lines = fh.readlines()
    except Exception as e:
        return None
    blocks = find_block_comments(lines)
    singles = find_multi_single_line_comments(lines)
    if blocks or singles:
        return {'path': path, 'blocks': blocks, 'singles': singles, 'total_lines': len(lines)}
    return None


def apply_changes(results):
    """Create backups and remove the detected comment ranges from files."""
    if not os.path.exists(BACKUP_ROOT):
        os.makedirs(BACKUP_ROOT)
    for r in results:
        rel = os.path.relpath(r['path'], start=ROOT)
        backup_path = os.path.join(BACKUP_ROOT, rel)
        backup_dir = os.path.dirname(backup_path)
        if not os.path.exists(backup_dir):
            os.makedirs(backup_dir)
        # copy original
        try:
            with open(r['path'], 'r', encoding='utf-8', errors='ignore') as fh:
                lines = fh.readlines()
        except Exception as e:
            print(f"Failed to read {r['path']}: {e}")
            continue
        # write backup
        with open(backup_path, 'w', encoding='utf-8', errors='ignore') as bh:
            bh.writelines(lines)

        # build set of lines to remove (1-based indices)
        to_remove = set()
        for b in r.get('blocks', []):
            for i in range(b['start'], b['end'] + 1):
                to_remove.add(i)
        for s in r.get('singles', []):
            for i in range(s['start'], s['end'] + 1):
                to_remove.add(i)

        new_lines = [ln for idx, ln in enumerate(lines, start=1) if idx not in to_remove]
        # write modified file
        try:
            with open(r['path'], 'w', encoding='utf-8', errors='ignore') as fh:
                fh.writelines(new_lines)
            print(f"Applied cleanup to: {r['path']} (backup at {backup_path})")
        except Exception as e:
            print(f"Failed to write {r['path']}: {e}")


def main():
    total_files = 0
    scanned = 0
    for f in walk_files(ROOT):
        total_files += 1
        res = analyze_file(f)
        if res:
            results.append(res)
        scanned += 1

    out = {
        'root': ROOT,
        'generated': datetime.now().isoformat(),
        'min_lines_threshold': MIN_LINES,
        'results_count': len(results),
        'results': results,
    }
    with open(OUT_JSON, 'w', encoding='utf-8') as jh:
        json.dump(out, jh, indent=2)

    with open(OUT_TXT, 'w', encoding='utf-8') as th:
        th.write(f"Commented blocks scan for: {ROOT}\nGenerated: {datetime.now().isoformat()}\nThreshold: {MIN_LINES} lines\n\n")
        th.write(f"Found {len(results)} files with large commented blocks or multi-line single-line comments.\n\n")
        for r in results:
            th.write(f"File: {r['path']} (total lines: {r['total_lines']})\n")
            for b in r['blocks']:
                th.write(f"  BLOCK: lines {b['start']}-{b['end']} ({b['length']} lines)\n")
                th.write(f"    Preview:\n{b['preview']}\n\n")
            for s in r['singles']:
                th.write(f"  MULTI-LINE SINGLE: lines {s['start']}-{s['end']} ({s['length']} lines)\n")
                th.write(f"    Preview:\n{s['preview']}\n\n")
            th.write('\n')

    print('Reports written:', OUT_JSON, OUT_TXT)

if __name__ == '__main__':
    main()
