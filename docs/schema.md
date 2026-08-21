# Datenbankstruktur — Kern-Entities

Übersicht der Tabellen und wichtigsten Felder (erste Iteration):

- users (Laravel default, `spatie/permission` roles & permissions)
  - id, name, email, password, phone, role through pivot

- owners
  - id, user_id (optional), company_name, first_name, last_name, email, phone, address fields, created_at, updated_at, deleted_at

- properties
  - id, owner_id, name, street, postal_code, city, country, description, contact_person, created_at, updated_at, deleted_at

- units
  - id, property_id, number, floor, area, rooms, status (enum), current_tenant_id, created_at, updated_at, deleted_at

- tenants
  - id, user_id (optional), first_name, last_name, email, phone, move_in, move_out, created_at, updated_at, deleted_at

- documents
  - id, documentable_type, documentable_id, filename, path, mime_type, size, version, category, uploaded_by, created_at, updated_at, deleted_at

- tickets
  - id, title, description, category, priority, status, property_id, unit_id, tenant_id, created_by, assigned_to, closed_at, due_at, created_at, updated_at, deleted_at

- attachments
  - id, attachable_type, attachable_id, filename, path, mime_type, size, uploaded_by, created_at, updated_at, deleted_at

- messages
  - id, thread_id, from_user_id, to_user_id (nullable), body, read_at, created_at, updated_at, deleted_at

- tasks
  - id, title, description, assigned_to, due_at, status, related_type, related_id, created_by, created_at, updated_at, deleted_at

- invoices
  - id, tenant_id, owner_id, unit_id, amount, due_date, status, pdf_path, created_at, updated_at, deleted_at

Hinweise:
- Soft Deletes werden in allen relevanten Tabellen genutzt.
- Dokumente und Anhänge sind polymorph (documentable / attachable).
- Indizes: foreign keys, fulltext auf suchrelevanten Feldern (title, description, address, document filename).
- Audit/Activity: Integration mit `spatie/laravel-activitylog` empfohlen.
