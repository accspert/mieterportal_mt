# Mieterportal — Modernes Immobilien- & Mieterverwaltungssystem

Das **Mieterportal** ist eine moderne Web-Plattform zur digitalen Verwaltung von Immobilien, Mieter- & Eigentümer-Kontakten, Service-Tickets, Abrechnungsbelegen, Dokumenten und Aufgaben.

---

## 🏛️ Architektur & Systemübersicht

Die Anwendung basiert auf einer modularen Multi-Interface-Architektur:

1. **Administration & Hausverwaltung (Filament v4/v5)**
   - Umfassendes Admin-Panel unter `/admin` für Sachbearbeiter & Administratoren.
   - Liegenschafts- & Einheitenverwaltung mit Mieter-Zuordnung.
   - Ticket-Management mit Bearbeitungs-Workflow, Dringlichkeits-Stufen und Zuweisung.
   - Rechnungsverwaltung mit 1-Klick PDF-Generierung und CSV-Export.
   - Interaktive KPI-Analytics (Statistiken, Ticket-Verteilungs-Charts, Umsatz-Grafiken).
   - Revisionssicheres Aktivitäten-Audit (`spatie/laravel-activitylog`).
   - Global Search über alle Stammdaten.

2. **Self-Service Mieterportal (Tenants & Owners)**
   - Responsives Mieter- & Eigentümer-Portal unter `/portal` mit modernem Dark-Mode-Design.
   - Dashboard mit Kennzahlen, offenen Rechnungen, aktiven Vorgängen und Benachrichtigungen.
   - Schadensmeldungen einreichen mit Foto- & Dateianhängen.
   - Digitales Dokumentenarchiv mit Kategorie-Filter (Mietverträge, Abrechnungen, Hausordnung).
   - Rechnungsübersicht mit DIN 5008 konformem PDF-Download.
   - Direktnachrichten-Kanal zur Hausverwaltung.
   - Profil- & Sicherheitseinstellungen (Passwortänderung, Kontaktdaten-Pflege).

3. **RESTful API (Sanctum Authentication)**
   - API-Endpunkte unter `/api/*` mit Bearer-Token-Authentifizierung für Mobile Apps und SPAs.
   - CRUD-Ressourcen für Liegenschaften, Einheiten, Tickets, Rechnungen, Dokumente und Nachrichten.
   - PDF-Streaming-Endpunkt `/api/invoices/{invoice}/pdf`.

---

## 🚀 Technologie-Stack

- **Framework**: Laravel 12 (PHP 8.2+)
- **Admin Panel**: Filament v4/v5 (Livewire 3 / Tailwind)
- **Design System**: Vanilla CSS mit Design-Tokens, Glassmorphism & Plus Jakarta Sans
- **PDF Engine**: `barryvdh/laravel-dompdf` (Server-seitiges Rendering)
- **Authentifizierung & API**: Laravel Sanctum (Tokens & Stateful Sessions)
- **Rechte & Rollen**: `spatie/laravel-permission` (Administrator, Sachbearbeiter, Eigentümer, Mieter)
- **Audit & Logging**: `spatie/laravel-activitylog`
- **Testing**: Pest PHP mit 100% grüner Testabdeckung (52+ Tests, 200+ Assertions)
- **Code Style**: Laravel Pint (PSR-12 / Laravel Standards)

---

## 👥 Rollen- & Berechtigungsmatrix

| Rolle | Admin-Panel (`/admin`) | Mieterportal (`/portal`) | REST API (`/api`) | Beschreibung |
|---|---|---|---|---|
| **Administrator** | Vollzugriff (inkl. User & Audit) | Zugriff | Vollzugriff | Systemadministration |
| **Sachbearbeiter** | Objekte, Tickets, Finanzen | Zugriff | Management | Hausverwalter & Objektmanager |
| **Eigentümer** | - | Eigene Objekte & Belege | Eigene Daten | Immobilieneigentümer |
| **Mieter** | - | Eigene Mieteinheit, Tickets, Belege | Eigene Daten | Mieter & Bewohner |

---

## 🛠️ Installation & Lokale Entwicklung

### Voraussetzungen
- PHP 8.2 oder höher mit SQLite/MySQL-Erweiterung
- Composer
- Node.js & npm

### Schnellstart

```powershell
# 1. Abhängigkeiten installieren
composer install
npm install

# 2. Umgebungskonfiguration
cp .env.example .env
php artisan key:generate

# 3. Datenbank migrieren und Testdaten einspielen
php artisan migrate:fresh --seed

# 4. Storage-Symlink erstellen
php artisan storage:link

# 5. Entwicklungsserver starten
php artisan serve
```

### Standard-Testkonten (nach Seeding)

- **Administrator**: `admin@kundenportal.local` / `password`
- **Sachbearbeiter**: `staff@kundenportal.local` / `password`
- **Eigentümer**: `owner@kundenportal.local` / `password`
- **Mieter**: `tenant@kundenportal.local` / `password`

---

## 🧪 Tests & Qualitätssicherung

### Automatisierte Tests ausführen (Pest)
```powershell
php artisan test --compact
```

### Code-Formatierung prüfen (Laravel Pint)
```powershell
vendor/bin/pint --format agent
```

### System-Gesundheitsprüfung (Health Check)
```powershell
php artisan mieterportal:health
```

---

## 📚 API-Dokumentation

Ausführliche curl-Beispiele und JSON-Payloads für alle REST-Endpunkte finden Sie in der [API-Dokumentation](docs/api.md).

