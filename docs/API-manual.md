# ZVS API (Local Development)

This manual explains how to use and test the ZVS API locally.

## API overview

The API exposes:

- `POST /api/login` for token creation
- `GET /api/v1/{table}` for read-only table access (requires bearer token)

Authentication is handled with Laravel Sanctum bearer tokens.

## 1. Start ZVS on localhost

From the project root:

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate:fresh --seed
```

By default, the app is available at:

- `http://localhost` (Sail default `APP_PORT=80`)

If your local port differs, use your own host/port in Postman.

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

- `per_page` (max 100)
- `created_at_after`
- `created_at_before`
- `updated_at_after`
- `updated_at_before`

## 3a. Timeline Items Endpoint

### GET /api/v1/petition_timeline_items

Retrieve timeline items (activities) for all cases or filtered by specific case.

**Authentication:** Required (Sanctum token)

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)
- `created_at_after` - ISO 8601 timestamp
- `created_at_before` - ISO 8601 timestamp
- `updated_at_after` - ISO 8601 timestamp
- `updated_at_before` - ISO 8601 timestamp

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

## 3b. Petition Assignments Endpoint

### GET /api/v1/petition_assignments

Retrieve petition-to-user assignments (lawyer assignments with PRIMARY and SECONDARY roles).

**Authentication:** Required (Sanctum token)

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)
- `petition_id` - Filter by specific petition ID
- `user_id` - Filter by specific user/lawyer ID
- `created_at_after` - ISO 8601 timestamp
- `created_at_before` - ISO 8601 timestamp
- `updated_at_after` - ISO 8601 timestamp
- `updated_at_before` - ISO 8601 timestamp

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

## 5. Use the Postman collection

Collection file:

- `postman/zvs-api-v1.postman_collection.json`

### Steps

1. Import the collection in Postman.
2. Open collection variables and set:
   - `host` = `http://localhost` (or your local port)
   - `api_key` = generated API key
   - `api_secret` = generated API secret
3. Run `login` request first.
4. The `login` test script stores `access_token` automatically.
5. Run any `GET /api/v1/*` request.

## 6. Run API tests

Run API endpoint tests:

```bash
vendor/bin/sail artisan test tests/Feature/Http/Controllers/Api/AuthenticationControllerTest.php
vendor/bin/sail artisan test tests/Feature/Api/GenericApiControllerTest.php
```

Run credential command tests:

```bash
vendor/bin/sail artisan test tests/Feature/Commands/CreateApiUserCommandTest.php
vendor/bin/sail artisan test tests/Feature/Commands/GenerateApiCredentialsTest.php
```
