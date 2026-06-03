# Postman API Testing Guide — Modul 4

> [!TIP]
> Make sure your Laravel dev server is running before testing:
> ```bash
> php artisan serve
> ```
> Base URL: **`http://127.0.0.1:8000`**
> All routes live under `/api/v1/`.
> Set `Accept: application/json` on every request.

---

## Part 1: Publisher Workflow

No authentication required — the controller falls back to `user_id = 1` for testing.

### 1. Publisher Dashboard

Gets the pre-print count and monthly approval stats.

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/publisher/dashboard`
- **Headers:** `Accept: application/json`

---

### 2. List Pre-Print Manuscripts

Returns paginated manuscripts with status `preprint`.
Optional query param: `?kelengkapan=pending|complete|partial`

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/publisher/manuscripts/pre-print`
- **Headers:** `Accept: application/json`

---

### 3. Get Manuscript Detail

Returns full manuscript info + previous check results.

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/publisher/manuscripts/{id}`
  *(Replace `{id}` with a valid manuscript ID, e.g. `1`)*
- **Headers:** `Accept: application/json`

---

### 4. Save Publisher Check

Saves (or updates) the physical checklist for a manuscript.

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/v1/publisher/check/{manuscriptId}`
  *(Replace `{manuscriptId}` with a valid ID, e.g. `1`)*
- **Headers:**
  - `Accept: application/json`
  - `Content-Type: application/json`
- **Body (raw → JSON):**
```json
{
    "is_cover_valid": true,
    "is_page_count_valid": true,
    "is_admin_docs_complete": true,
    "check_notes": "Cover and page count verified. All admin documents received."
}
```

---

### 5. Publisher Decision

Records the final approval/revision decision, updates the manuscript status,
and **fires the `DecisionMade` event** which triggers the notification pipeline.

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/v1/publisher/decision`
- **Headers:**
  - `Accept: application/json`
  - `Content-Type: application/json`

> [!IMPORTANT]
> `status` must be exactly `"approved"` or `"revised"`.
> A `publisher_checks` record for this `manuscript_id` must exist first (run Step 4).

**Body — Approve:**
```json
{
    "manuscript_id": 1,
    "status": "approved",
    "final_notes": "All requirements met. Ready for printing."
}
```

**Body — Revise:**
```json
{
    "manuscript_id": 1,
    "status": "revised",
    "final_notes": "Cover image resolution too low. Please resubmit."
}
```

> [!NOTE]
> **What happens after this request:**
> The `DecisionMade` event is fired → `SendDecisionNotification` listener runs →
> an email is sent to the manuscript author via `EmailService` →
> a row is inserted into `notification_log` with `event_type = publisher_approved` or `publisher_revised`.

---

## Part 2: Notifications

### 6. Manual Notification Send

Sends an ad-hoc email to any address and logs it to `notification_log`.
Use this to test the full email → log pipeline without needing an event.

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/v1/notification/send`
- **Headers:**
  - `Accept: application/json`
  - `Content-Type: application/json`

> [!IMPORTANT]
> `type` must be one of the exact enum values below.

**Body — Test `account_created`:**
```json
{
    "to": "testuser@example.com",
    "subject": "Selamat Datang di Book Grant",
    "body": "Halo! Akun Anda telah berhasil dibuat.",
    "type": "account_created"
}
```

**Body — Test `contract_validated`:**
```json
{
    "to": "author@example.com",
    "subject": "Kontrak Anda Telah Divalidasi",
    "body": "Kontrak penerbitan untuk naskah Anda telah divalidasi.",
    "type": "contract_validated"
}
```

**Body — Test `deadline_reminder`:**
```json
{
    "to": "author@example.com",
    "subject": "Reminder: Deadline Naskah Anda Mendekat",
    "body": "Harap segera selesaikan revisi sebelum tenggat waktu.",
    "type": "deadline_reminder"
}
```

**All valid `type` values:**
| Value | Triggered by |
|---|---|
| `account_created` | Modul 1 → `AccountCreated` event |
| `contract_validated` | Modul 1 → `ContractValidated` event |
| `draft_uploaded` | Modul 2 |
| `review_assigned` | Modul 3 |
| `review_completed` | Modul 3 |
| `revision_requested` | Modul 3 |
| `preprint_entered` | Modul 2/3 |
| `publisher_approved` | `DecisionMade` event (approved) |
| `publisher_revised` | `DecisionMade` event (revised) |
| `deadline_reminder` | `ReminderController@trigger` |

**Expected success response (`200`):**
```json
{
    "status": "success",
    "message": "Email berhasil dikirim.",
    "_links": {
        "notification_logs": { "href": "/api/v1/monitoring/logs", "method": "GET" }
    }
}
```

> [!NOTE]
> If SMTP is not configured, the email will fail but the log entry will still be written with `"status": "failed"` and the error message recorded in `error_message`.

---

### 7. Trigger Deadline Reminders

Sweeps all active deadlines due within 3 days and sends reminder emails.
Designed to be called by a daily scheduled job, but can be triggered manually here.

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/v1/reminder/trigger`
- **Headers:** `Accept: application/json`
- **Body:** *(none required)*

**Expected response:**
```json
{
    "status": "success",
    "data": {
        "total_deadlines_found": 3,
        "reminders_sent": 3,
        "reminders_failed": 0,
        "reminders_skipped": 0
    },
    "_links": {
        "monitoring_deadlines": { "href": "/api/v1/monitoring/deadlines", "method": "GET" },
        "notification_logs": { "href": "/api/v1/monitoring/logs", "method": "GET" }
    }
}
```

> [!NOTE]
> Each reminder sent also creates a `notification_log` entry with `event_type = deadline_reminder`.
> Check the log endpoint after triggering to verify.

---

## Part 3: Monitoring & Dashboards

### 8. Admin Dashboard Summary

Returns aggregate pipeline counts across all modules.

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/admin/dashboard/summary`
- **Headers:** `Accept: application/json`

---

### 9. Monitoring — Upcoming Deadlines

Returns all active deadlines due within the next 3 days with eager-loaded assignee and manuscript info.

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/monitoring/deadlines`
- **Headers:** `Accept: application/json`

---

### 10. Monitoring — Notification Logs

Returns paginated notification log history. Supports filters.

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/monitoring/logs`
- **Headers:** `Accept: application/json`

**Optional Query Params:**
| Param | Example | Description |
|---|---|---|
| `type` | `?type=account_created` | Filter by event type |
| `status` | `?status=failed` | Filter by send status (`sent`, `failed`, `pending`) |
| `limit` | `?limit=10` | Results per page (default: 20) |
| `page` | `?page=2` | Page number |

**Example — see all failed notifications:**
```
GET http://127.0.0.1:8000/api/v1/monitoring/logs?status=failed
```

**Example — see only deadline reminders:**
```
GET http://127.0.0.1:8000/api/v1/monitoring/logs?type=deadline_reminder
```

---

### 11. User Deadline Widget

Returns active task count and nearest deadline for the current user (falls back to `user_id = 1`).

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/v1/user/deadline-widget`
- **Headers:** `Accept: application/json`

---

## Testing the Full Notification Flow (End-to-End)

Follow this sequence to verify the entire pipeline works:

```
1. POST /api/v1/publisher/check/1        → Save a check (ensure a publisher_checks row exists)
2. POST /api/v1/publisher/decision       → Trigger DecisionMade event (fires SendDecisionNotification)
3. GET  /api/v1/monitoring/logs?type=publisher_approved  → Confirm log entry was written
```

```
4. POST /api/v1/notification/send        → Manually send an account_created notification
5. GET  /api/v1/monitoring/logs?type=account_created     → Confirm log entry was written
```

```
6. POST /api/v1/reminder/trigger         → Sweep active deadlines and send reminders
7. GET  /api/v1/monitoring/logs?type=deadline_reminder   → Confirm reminder logs were written
```
