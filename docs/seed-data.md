# Seed data from production

The seeder takes departments, petition types and petition statuses from a production export in
`database/seeders/data`. That way local statuses have the same names, groups and ids as production.

## Refreshing the export

```bash
API=https://<production-host>/api/v1
TOKEN=<token>

for table in departments petition_types petition_statuses; do
  for page in $(seq 1 10); do
    curl -s -H "Authorization: Bearer $TOKEN" "$API/$table?per_page=100&page=$page"
  done | jq -s --indent 4 'map(.data) | add' > "database/seeders/data/${table//_/-}.json"
done
```
Then run `sail composer reset-db` and check the result locally.