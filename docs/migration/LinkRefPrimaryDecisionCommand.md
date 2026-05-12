# LinkRefPrimaryDecisionCommand

## Overview

`LinkRefPrimaryDecisionCommand` is an Artisan console command that links the "Kenmerk Primair Besluit" value from an Excel sheet to existing petitions. When committing, it writes the value to the petition `message` field and updates the `reference` field on applicant links (pivot `contact_petition`) for the petition.

This command supports a dry-run mode (default) so you can preview changes without modifying the database.

## Purpose

Useful during migrations or data-imports where an organization stores a primary decision reference in an Excel sheet and you want to copy that reference into the system for the petition and its applicant links.

## Command signature

```bash
php artisan petitions:link-ref-primary-decision {file} [--commit]
```

Where `{file}` is a path to an Excel file (XLSX) containing at least the header columns `Zaaknummers` and `Kenmerk Primair Besluit` (case/spacing variations are handled).

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `--commit` | `false` | If present the command will write changes to the database. Otherwise the command runs in dry-run mode and only prints what would be changed. |

## Expected input (Excel)

- Required header columns (case/whitespace-insensitive):
  - `Zaaknummers` — petition number (mapped to `number`)
  - `Kenmerk Primair Besluit` — primary decision reference (mapped to `primary_decision_reference`)
- The command tolerates header casing/spacing differences (it normalises header names and maps them with an internal mapping).
- Rows that are entirely empty are ignored. Cells are trimmed before processing.

## Usage examples

### Dry run (preview)

```bash
php artisan petitions:link-ref-primary-decision storage/imports/primary-decisions.xlsx
```

This prints per-row actions (e.g. "Would update petition X with primary decision reference \"...\""), counts of applicant links that would be updated, and a final summary. No database changes are performed.

### Commit (apply changes)

```bash
php artisan petitions:link-ref-primary-decision storage/imports/primary-decisions.xlsx --commit
```

This updates each petition's `message` column with the provided reference and updates `contact_petition.reference` for all links whose role is `ContactRole::APPLICANT`.

## Behavior & edge cases

- If the file path does not exist the command prints an error and exits with failure.
- The Excel file is read using the Maatwebsite Excel wrapper. If no sheets or no rows are found the command prints an error and fails.
- If the header row does not contain both required columns the command prints an error and fails.
- For each non-empty row:
  - If the petition number cell is empty the row is recorded as a failed record (missing petition number) and skipped.
  - If a petition with the given number cannot be found it is recorded as a failed record and skipped.
  - Dry-run: the command prints the would-be updates and accumulates counts (petitions, applicant links) but does not modify the DB.
  - Commit: the command wraps changes in a DB transaction, updates the petition `message`, and updates applicant link `reference` fields. If an exception occurs the transaction is rolled back and an error is printed.

## Current implementation notes

- Column name transformation is handled by `transformColumnName()` which normalises header text and maps known names to the internal keys `number` and `primary_decision_reference`.
- Rows are combined to an associative array using the header mapping; missing cells are padded as `null`.
- Applicant links are detected by `ContactRole::APPLICANT` and updated accordingly.

## Output

Typical outputs include:

- "File not found: {path}" — missing file error
- "DRY RUN MODE - No changes will be made to the database" — when not committing
- "Would update petition {number} with primary decision reference "{ref}"" — per-row dry-run message
- "  Would update {N} applicant link(s)" — per-row applicant link count (dry-run)
- "Dry run completed. Would update {P} petition(s) and {L} applicant link(s)." — dry-run summary
- "Successfully updated {P} petition(s) and {L} applicant link(s)." — on successful commit
- A final list of rows that could not be processed is printed when applicable, prefixed with "{N} row(s) could not be processed:" and each row listed as "  Row {i}: {reason}".

## Return codes

- `0` (SUCCESS): Command completed successfully
- `1` (FAILURE): Command failed (file not found, missing required columns, or an exception occurred)

## Location

`app/Console/Commands/Petition/LinkRefPrimaryDecisionCommand.php`
