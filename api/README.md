# API access

This app now supports authenticated external API access, separate from the
browser session login.

## 1. Run the migrations

Import, in order:

1. `migration_add_api_keys.sql` — adds `api_keys` and `api_request_log`.
2. `migration_add_api_key_tracking_to_work_tables.sql` — adds a
   `last_api_key_id` / `last_api_touched_at` column to `website_work`,
   `app_work`, and `dashboard_work`, so each row can show which API key
   last created or updated it.

(`mysql -u root project_work_report_generator < migration_add_api_keys.sql`,
then the second file the same way — or use phpMyAdmin's Import tab.)
Both are additive and safe to re-run.

## 2. Generate a key

Log in to the app as usual and go to **API Keys** in the sidebar
(`api_keys.php`). Generate a key, choose "Read only" or "Read & write",
and copy the key immediately — it's shown once and stored only as a hash,
so it can't be recovered later (generate a new one and revoke the old one
if you lose it).

## 3. Call the API

Send the key as either header on every request:

```
Authorization: Bearer pwrg_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```
or
```
X-API-Key: pwrg_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

All endpoints live under `/api/` and return JSON in this shape:

```json
{ "success": true, "data": { ... } }
{ "success": false, "message": "..." }
```

| Endpoint                    | Methods                  | Notes                                  |
|------------------------------|---------------------------|-----------------------------------------|
| `/api/clients.php`           | GET, POST, PUT, DELETE    | `?id=`, `?status=`, `?page=&per_page=` |
| `/api/projects.php`          | GET, POST, PUT, DELETE    | `?id=`, `?status=`, `?client_id=`      |
| `/api/website_work.php`      | GET, POST, PUT, DELETE    | `?id=`, `?project_id=`, `?status=`     |
| `/api/app_work.php`          | GET, POST, PUT, DELETE    | `?id=`, `?project_id=`, `?status=`     |
| `/api/dashboard_work.php`    | GET, POST, PUT, DELETE    | `?id=`, `?project_id=`, `?status=`     |
| `/api/technologies.php`      | GET, POST, PUT, DELETE    | `?id=`, `?project_id=`                 |
| `/api/dashboard_stats.php`   | GET                       | Read-only summary numbers              |

- `GET` (list) is paginated: `?page=1&per_page=20` (max 100 per page).
- `POST`/`PUT` bodies are JSON, e.g. `{"client_name": "Acme"}`.
- `POST`/`PUT`/`DELETE` require a key with the **write** scope; a
  **read-only** key gets a `403` on those.
- Keys are rate-limited to 60 requests/minute; going over returns `429`.

### Example

```bash
curl https://your-domain/project_work_report_generator/api/projects.php?status=In+Progress \
  -H "Authorization: Bearer pwrg_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

## Which key touched what

The **Website Work**, **App Work**, and **Dashboard Work** list pages now
show an "API Key" column. Any row created or updated through
`/api/website_work.php`, `/api/app_work.php`, or `/api/dashboard_work.php`
is stamped with the calling key's label; the badge shows which key it was,
and hovering shows when. Editing that same row back in the browser clears
the badge (it shows "— Created in app —" again), since the browser edit is
now the most recent touch.

## Notes

- This is fully additive: the existing pages, session login, and AJAX
  endpoints (`ajax_add_client.php`, `chart_data.php`, `dashboard_stats.php`)
  are untouched and keep working exactly as before.
- Revoking a key in the **API Keys** page takes effect immediately, but the
  key's label stays on rows it already touched (for history) — the badge
  only says which key it *was*, not whether that key still works.
