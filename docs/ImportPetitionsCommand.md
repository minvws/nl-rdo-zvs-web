# ImportPetitionsCommand

## Basic Usage

### Dry Run (Preview Only)
```bash
# Preview import without making changes
vendor/bin/sail artisan import:petitions storage/imports/completebezwaren-testdata.xlsx \
--file-jurist=storage/imports/juristen-testdata.xlsx \
--file-category=storage/imports/categorieen.xlsx
```

### Commit Changes
```bash
# Actually import the data
vendor/bin/sail artisan import:petitions storage/imports/completebezwaren-testdata.xlsx \
--file-jurist=storage/imports/juristen-testdata.xlsx \
--file-category=storage/imports/categorieen.xlsx \
--commit
```

## Import Modes

### 1. Bezwaren Mode (Default)
Used for importing "bezwaren".

```bash
vendor/bin/sail artisan import:petitions storage/imports/completebezwaren-testdata.xlsx \
--file-jurist=storage/imports/juristen-testdata.xlsx \
--file-category=storage/imports/categorieen.xlsx \
--commit
```

**Expected columns:**
- `datumbob` → Maps to DATE_OF_LAST_DECISION
- `datumintrekking` → Maps to DATE_WITHDRAWN
- `datumdoorzending` → Maps to DATE_OF_FORWARDING

### 2. Beroepen Mode
Used for importing "beroepen".

```bash
vendor/bin/sail artisan import:petitions storage/imports/beroepenWJZ-testdata.xlsx \
--file-jurist=storage/imports/juristen-testdata.xlsx \
--file-category=storage/imports/categorieen.xlsx \
--beroepen \
--commit
```

**Expected columns:**
- `datum_uitspraak` → Maps to DATE_RULING
- `zitting` → Maps to DATE_COURT_SESSION
- `uitspraak` → Mapped to `redenintrekking` using beroepen mapping

**Uitspraak Mapping:**
- `afgewezen` → Ongegrond
- `doorzending` → Doorzending
- `informeel` → Informeel
- `gegrond` → Gegrond
- `instantie verklaart zich onbevoegd` → Rechtbank onbevoegd
- `intrekking` → Intrekking
- `kennelijk niet-ontvankelijk` → Kennelijk niet-ontvankelijk
- `niet-ontvankelijk` → Niet-ontvankelijk
- `ongegrond` → Ongegrond
- `toegewezen` → Gegrond

## Quick Test

### Step 1: Dry Run
Always start with a dry run to preview changes:
```bash
vendor/bin/sail artisan import:petitions storage/imports/completebezwaren-testdata.xlsx \
--file-jurist=storage/imports/juristen-testdata.xlsx \
--file-category=storage/imports/categorieen.xlsx
```

### Step 2: Review Output
Check the output for:
- Number of records to be imported
- Any validation errors
- What properties/dates would be added

### Step 3: Commit
If everything looks good:
```bash
vendor/bin/sail artisan import:petitions storage/imports/completebezwaren-testdata.xlsx \
--file-jurist=storage/imports/juristen-testdata.xlsx \
--file-category=storage/imports/categorieen.xlsx \
--commit
```

### Step 4: Rollback (If Needed)
If something goes wrong, use the batch ID from the output:
```bash
vendor/bin/sail artisan import:petitions --rollback={batch-id}
```

## Testing with Sample Data

### Create Test Excel File
Create a simple Excel file with these columns:

**For Bezwaren:**

| number | datumbob | datumintrekking | redenintrekking |
|--------|----------|-----------------|-----------------|
| BZ001  | 01-01-2025 | 15-01-2025 | Informeel |

**For Beroepen:**

| number | datum_uitspraak | zitting | uitspraak |
|--------|-----------------|---------|-----------|
| BR001  | 01-02-2025 | 15-01-2025 | gegrond |

## Location

`app/Console/Commands/ImportPetitionsCommand.php`
