# Fridge-Fuse: Universal Standalone Sandbox API I have used for my SQL testing in projects rather than messing about.

A single-file PHP backend that exposes an automated REST API over a local SQLite database. It maps inbound HTTP request URIs dynamically to database operations, allowing clients to query data, inject JSON payloads, and reset the database state on demand.

---

## Technical Specifications

* **Language/Runtime:** PHP 7.4+ (Requires `php-cli` and `php-sqlite3` extensions).
* **Database Engine:** SQLite 3 (Self-contained, file-based).
* **Architecture:** Front Controller Pattern (All routing runs through `index.php`).
* **Data Serialization:** Input and output payloads are strictly structured JSON (`Content-Type: application/json`).
* **CORS Behavior:** Configured to accept all cross-origin requests (`Access-Control-Allow-Origin: *`).

---

## Endpoint Map

The routing logic parses the request path string to match tables and records dynamically.

| HTTP Method | URI Route | Target Action | Payload Constraint |
| :--- | :--- | :--- | :--- |
| **`POST`** | `/api/setup` | Deletes existing `database.sqlite` file, recreates schemas (`users`, `inventory`), and inserts baseline test rows. | None (Empty Body) |
| **`GET`** | `/api/{table}` | Fetches all rows from the specified table. | None |
| **`GET`** | `/api/{table}/{id}` | Fetches a single row matching the primary key ID. | None |
| **`POST`** | `/api/{table}` | Inserts a new row into the specified table. Maps JSON keys directly to database column names. | Valid JSON object |
| **`DELETE`** | `/api/{table}/{id}` | Drops the row matching the primary key ID from the specified table. | None |

---

## Deployment & Execution Options

The address and network port are not constrained by the codebase. Binding limits are handled at runtime via the interpreter terminal flags.

### Local Loopback (Internal Testing Only)
Binds strictly to interface `127.0.0.1`. Only requests originating from the host machine are processed:
```bash
php -S localhost:8000
