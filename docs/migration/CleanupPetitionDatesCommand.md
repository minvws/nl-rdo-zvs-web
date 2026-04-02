# CleanupPetitionDatesCommand

## Overview

The `CleanupPetitionDatesCommand` is an Artisan console command that cleans up invalid dates in the petitions table by setting the `deadline_at` field to `null` when it matches a specified target date.

## Purpose

This command is designed to handle data migration scenarios where certain dates (such as placeholder dates like `2025-04-14`) need to be removed from the database. 
It provides a safe way to identify and clean up these invalid dates with support for dry-run mode to preview changes before committing them.

## Command Signature

```bash
php artisan petitions:cleanup-dates [--date=yyyy-mm-dd] [--commit]
```

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `--date` | `2025-04-14` | Target date to replace with null (format: yyyy-mm-dd) |
| `--commit` | `false` | Commit changes to database (without this flag, runs in dry-run mode) |

## Usage Examples

### Dry Run (Preview Changes)

Preview changes without modifying the database:

```bash
# Uses default date (2025-04-14)
php artisan petitions:cleanup-dates

# Preview changes for a specific date
php artisan petitions:cleanup-dates --date=2025-04-14
```

### Commit Changes

Apply changes to the database:

```bash
# Commit changes for default date
php artisan petitions:cleanup-dates --commit

# Commit changes for a specific date
php artisan petitions:cleanup-dates --date=2025-04-14 --commit
```

## Behavior

1. **Date Validation**: Validates that the provided date matches the format `yyyy-mm-dd`
2. **Record Count**: Counts how many records have `deadline_at` matching the target date
3. **Dry-Run Mode** (default):
   - Displays how many records would be updated
   - Does not modify the database
   - Shows a warning message indicating it's in dry-run mode
4. **Commit Mode** (with `--commit` flag):
   - Updates `deadline_at` to `null` for matching records
   - Uses database transactions for safe updates
   - Rolls back changes if an error occurs

## Current Implementation Notes

- **Active Updates**: Only `deadline_at` field is currently being updated
- **Transaction Safety**: All database modifications are wrapped in a transaction that rolls back on error

## Output

The command provides informative output including:
- Dry-run warning (if applicable)
- Target date being processed
- Count of records found with matching dates
- Success/failure messages
- Total number of records updated (in commit mode)
- Error messages if something goes wrong

## Return Codes

- `0` (SUCCESS): Command completed successfully
- `1` (FAILURE): Command failed (invalid date format or database error)

## Location

`app/Console/Commands/CleanupPetitionDatesCommand.php`
