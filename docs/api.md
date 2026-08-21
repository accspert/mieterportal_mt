# REST API — Kundenportal

This API provides endpoints for external clients, SPAs, and mobile apps using **Laravel Sanctum Bearer Tokens**.

## Authentication

### 1) Register a new user
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@example.com","password":"secret","password_confirmation":"secret"}'
```

### 2) Login
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"secret"}'
```
Response:
```json
{
  "token": "1|abcdef123456...",
  "user": { "id": 1, "name": "Alice", "email": "alice@example.com" }
}
```

### 3) Get current user
```bash
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" http://localhost/api/auth/me
```

---

## Domain Endpoints

### 4) Properties & Units
```bash
# List properties
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" "http://localhost/api/properties?per_page=10"

# List units
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" "http://localhost/api/units"
```

### 5) Tickets
```bash
# Create ticket
curl -X POST http://localhost/api/tickets \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Leck im Bad","description":"Wasser am Boden","priority":"high","property_id":1}'
```

### 6) Invoices
```bash
# List invoices
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" "http://localhost/api/invoices?status=sent"

# Create invoice
curl -X POST http://localhost/api/invoices \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"invoice_number":"INV-2026-001","amount":750.00,"due_date":"2026-09-01","status":"sent"}'
```

### 7) Tasks
```bash
# List tasks
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" "http://localhost/api/tasks?priority=high"

# Create task
curl -X POST http://localhost/api/tasks \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Dachrinnen reinigen","priority":"medium","status":"pending"}'
```

### 8) Documents
```bash
# List documents
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" "http://localhost/api/documents?category=contract"
```

### 9) Messages
```bash
# List user messages
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" "http://localhost/api/messages"

# Send message
curl -X POST http://localhost/api/messages \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"subject":"Frage zu Mietvertrag","body":"Bitte um Rückruf bzgl. Zählerstand."}'

# Mark message as read
curl -X PATCH http://localhost/api/messages/1/read \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Accept: application/json"
```
