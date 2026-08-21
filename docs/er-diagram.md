# ER Diagram — Kundenportal Immobilienverwaltung

```mermaid
erDiagram
    USER ||--o{ TENANT : has
    USER ||--o{ OWNER : has
    USER ||--o{ MESSAGE : author
    USER ||--o{ TICKET : author

    OWNER ||--o{ PROPERTY : owns
    PROPERTY ||--o{ UNIT : contains
    UNIT ||--o{ TENANT : rents

    PROPERTY ||--o{ DOCUMENT : has
    UNIT ||--o{ DOCUMENT : has
    TENANT ||--o{ DOCUMENT : has

    TICKET ||--o{ ATTACHMENT : has
    DOCUMENT ||--o{ ATTACHMENT : has

    UNIT ||--o{ TICKET : related
    TENANT ||--o{ TICKET : creates

    MESSAGE ||--o{ ATTACHMENT : has
    USER ||--o{ TASK : assigned
    TASK ||--o{ ATTACHMENT : has

    INVOICE ||--o{ ATTACHMENT : has
    OWNER ||--o{ INVOICE : receives
    TENANT ||--o{ INVOICE : pays
```

This captures the core domain relationships for the first iteration: Users (system users), Owners, Properties (Objekte), Units/Wohnungen, Tenants (Mieter), Documents, Tickets, Messages, Tasks and Attachments.
