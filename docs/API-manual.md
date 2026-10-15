# ZVS API (Local Development)

This manual explains how to use and test the ZVS API locally.

## API overview

The API exposes:

- `POST /api/login` for token creation
- `GET /api/v1/{table}` for read-only table access (requires bearer token)

Authentication is handled with Laravel Sanctum bearer tokens.

The API is read-only: every `/api/v1/*` endpoint is a paginated `GET`. Which tables are exposed,
and which of their columns are returned, is defined in `config/api_tables.php`.

## 1. Start ZVS on localhost

From the project root:

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate:fresh --seed
```

By default, the app is available at:

- `http://localhost` (Sail default `APP_PORT=80`)

If your local port differs, use your own host/port in the API client.

## 2. Create API credentials

### Create a new API user

Run:

```bash
vendor/bin/sail artisan api:create-user
```

You will be prompted for:

- `API User Name`

The command prints:

- `API User ID`
- `API Key` (64 chars)
- `API Secret` (128 chars)

Store the `API Secret` immediately. It is shown once in plain text.

### Regenerate credentials for an existing API user

Run:

```bash
vendor/bin/sail artisan api:generate-credentials <api_user_id>
```

This overwrites the existing key/secret for that API user.

## 3. Login and call endpoints

### Login (get access token)

```bash
curl -X POST "http://localhost/api/login" \
  -H "Content-Type: application/json" \
  -d '{"api_key":"<API_KEY>","api_secret":"<API_SECRET>"}'
```

Expected response:

```json
{
  "access_token": "<token>"
}
```

### Call a table endpoint

```bash
curl "http://localhost/api/v1/users?per_page=5" \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json"
```

Response shape:

- `data`
- `pagination` (`current_page`, `per_page`, `total`, `last_page`, `from`, `to`)
- `meta.available_fields`

### Common query parameters

All endpoints accept:

- `page` - page number (default: 1)
- `per_page` - items per page (default: 15, max: 100; higher values are capped at 100)
- `created_at_after` - keeps rows with `created_at` strictly after this timestamp
- `created_at_before` - keeps rows with `created_at` strictly before this timestamp
- `updated_at_after` - keeps rows with `updated_at` strictly after this timestamp
- `updated_at_before` - keeps rows with `updated_at` strictly before this timestamp

Timestamps are compared as given; use ISO 8601 (for example `2026-01-31T00:00:00Z`).

### Filtering on fields

Every field an endpoint exposes doubles as an exact-match filter. For example
`GET /api/v1/petitions?number=2026-0001` or `GET /api/v1/petition_events?petition_id=<uuid>`.
The fields available per endpoint are returned in `meta.available_fields` on every response.

Two things to be aware of:

- A parameter that is present but empty (`?number=`) filters on an empty string and returns no
  rows. Leave a filter out entirely instead of sending it empty.
- The `filterable_fields` key in `config/api_tables.php` is not read by the query builder; filtering
  is derived from the `fields` list. It only documents intent.

## 4. Available endpoints

| Endpoint (`/api/v1/...`) | Database table |
| --- | --- |
| `users` | `users` |
| `petitions` | `petitions` |
| `petition_petition` | `petition_petition` |
| `petition_deliverables` | `petition_deliverables` |
| `petition_draft_terms` | `petition_draft_terms` |
| `petition_custom_properties` | `custom_petition_property_petition` |
| `decision_petition` | `decision_petition` |
| `categories` | `petition_categories` |
| `tags` | `tags` |
| `contacts` | `contacts` |
| `terms` | `petition_terms` |
| `decisions` | `decisions` |
| `departments` | `departments` |
| `petition_statuses` | `petition_statuses` |
| `petition_status_histories` | `petition_statuses_history_entries` |
| `petition_types` | `petition_types` |
| `petition_custom_costs` | `custom_costs` |
| `petition_custom_dates` | `petition_custom_dates` |
| `public_holidays` | `public_holidays` |
| `processing_steps` | `processing_steps` |
| `policy_departments` | `policy_departments` |
| `custom_petition_properties_definitions` | `custom_petition_properties` |
| `petition_external_urls` | `petition_external_urls` |
| `petition_policy_department` | `petition_policy_department` |
| `petition_tag` | `petition_tag` |
| `petition_querysnapshots` | `petition_querysnapshots` |
| `contact_petition` | `contact_petition` |
| `custom_petition_properties` | `custom_petition_properties` |
| `petition_events` | `petition_events` |
| `petition_timeline_items` | `timeline_items` |
| `petition_assignments` | `petition_assignments` |

`custom_petition_properties` and `custom_petition_properties_definitions` are aliases: both read the
`custom_petition_properties` table and expose the same fields.

The endpoints below need some extra explanation.

### GET /api/v1/petition_timeline_items

Retrieve timeline items (activities) for all cases or filtered by specific case.

**Endpoint-specific filter:** `timelineable_id` - restrict to one case (or other timelineable record)

**Response Fields:**
- `internal_id` - Timeline item ID
- `timelineable_type` - Type (e.g., App\Models\Petition)
- `timelineable_id` - Related entity ID
- `user_id` - User who triggered the event
- `type` - Event type
- `data` - Event data (JSON)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

**Example Request:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/api/v1/petition_timeline_items?page=1
```

### GET /api/v1/petition_assignments

Retrieve petition-to-user assignments (lawyer assignments with PRIMARY and SECONDARY roles).

**Endpoint-specific filters:** `petition_id`, `user_id`

**Response Fields:**
- `id` - Assignment record ID
- `petition_id` - Petition ID
- `user_id` - Assigned user/lawyer ID
- `assignment_role` - Role type (`1` = PRIMARY, `2` = SECONDARY)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

**Example Requests:**
```bash
# Get all assignments for a specific petition
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/petition_assignments?petition_id=<petition_id>"

# Get all assignments for a specific lawyer/user
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/petition_assignments?user_id=<user_id>"
```

### GET /api/v1/petition_events

Retrieve the events that drive status transitions, suspensions and deadline calculations on a case.

**Endpoint-specific filters:** `petition_id`, `type`

**Response Fields:**
- `id` - Event ID
- `petition_id` - Petition the event belongs to
- `type` - Event type
- `date` - Event date
- `duration` - Duration in days, for events that suspend a term
- `penalties` - Penalty amount attached to the event
- `suspension_type` - Suspension type, for suspension events
- `result_type` - Result type, for events that close a term
- `reasoning` - Free-text reasoning (used for unspecified adjournment)
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

**Example Request:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/petition_events?petition_id=<petition_id>"
```

### GET /api/v1/tags and GET /api/v1/petition_tag

`tags` is the tag list each department manages itself. `petition_tag` links tags to petitions.

**Response Fields (`tags`):**
- `id` - Tag ID
- `department_id` - Department the tag belongs to
- `name` - Tag name
- `color` - Colour (`blue`, `green`, `yellow`, `orange`, `red`, `grape`, `gray`)
- `description` - Explanation for case handlers (optional)
- `active` - Whether the tag can still be added to a petition
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

**Response Fields (`petition_tag`):**
- `petition_id` - Petition ID
- `tag_id` - Tag ID

Inactive tags stay in `tags` and stay linked in `petition_tag`. Filter on `tag_id` rather than on the
name, because a department can rename a tag.

**Example Request:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "http://localhost/api/v1/petition_tag?tag_id=<tag_id>"
```

## 5. Use the Bruno collection

A Bruno collection is maintained in `docs/bruno-collection/`, with one request per endpoint plus a
`login` request. It covers every endpoint listed in section 4. When an endpoint or a field is added
to `config/api_tables.php`, update the collection in the same change.

1. Open `docs/bruno-collection` as a collection in Bruno.
2. Set the collection variables:
   - `host` = `http://localhost` (or your local port)
   - `api_key` = generated API key
   - `api_secret` = generated API secret
3. Select (or create) an environment. The `login` request stores the token with `bru.setEnvVar`,
   so an environment has to be active for it to land.
4. Run the `login` request first. Its after-response script stores `access_token`, which every other
   request uses as its bearer token.
5. Run any `GET /api/v1/*` request. Query parameters are included but disabled; enable the ones you
   need and give them a value.

## 6. Run API tests

Run API endpoint tests:

```bash
vendor/bin/sail artisan test tests/Feature/Http/Controllers/Api/AuthenticationControllerTest.php
vendor/bin/sail artisan test tests/Feature/Api/GenericApiControllerTest.php
vendor/bin/sail artisan test tests/Feature/Api/TimelineItemsEndpointTest.php
```

Run credential command tests:

```bash
vendor/bin/sail artisan test tests/Feature/Commands/Api/CreateApiUserCommandTest.php
vendor/bin/sail artisan test tests/Feature/Commands/Api/GenerateApiCredentialsTest.php
```
