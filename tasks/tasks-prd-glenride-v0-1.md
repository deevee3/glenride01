## Relevant Files

- `database/migrations/` - Migrations for tenants, nodes, edges, events, scenarios, and supporting tables.
- `database/migrations/2025_11_14_112931_create_tenants_table.php` - Creates the tenants table with unique names per organization.
- `database/migrations/2025_11_14_113108_add_tenant_id_to_users_table.php` - Adds the tenant association and scoped unique constraint to users.
- `app/Models/` - Eloquent models for the domain entities introduced by the PRD.
- `app/Support/TenantContext.php` - Holds the current tenant context during each request for multi-tenant scoping.
- `app/Http/Middleware/EnsureTenantIsSet.php` - Applies tenant scoping for authenticated routes.
- `app/Services/` - Service layer for ingestion, event parsing, scenario engine, and LLM routing.
- `app/Http/Controllers/` - Controllers handling uploads, events, and scenarios.
- `resources/js/Pages/` - Inertia pages for uploads, events listing, and scenario management.
- `resources/js/Pages/auth/register.tsx` - Registration form capturing tenant name and user details.
- `config/seed.php` - Centralizes environment-driven defaults for seeded tenants and admin users.
- `tests/Feature/` - Feature tests covering ingestion, event parsing endpoints, and scenario flows.
- `tests/Feature/Auth/RegistrationTest.php` - Ensures registration flow provisions tenants and users correctly.
- `database/factories/UserFactory.php` - Seeds users with tenant relationships for tests and seeders.

### Notes

- Unit tests should typically be placed alongside the code files they are testing when practical.
- Use `php artisan test` to run the test suite.

## Tasks

- [ ] 1.0 Establish multi-tenant domain foundation and authentication scoping
  - [x] 1.1 Create migrations for `tenants` table and add `tenant_id` to users with foreign key constraints.
  - [x] 1.2 Update Fortify/User models, policies, and middleware to enforce tenant scoping across queries.
  - [x] 1.3 Seed default tenant and admin user; add factory states for tenants and tenant-bound users.
- [ ] 2.0 Implement structured data ingestion with validation and persistence
  - [ ] 2.1 Create migrations and Eloquent models for nodes and edges with tenant relationships.
  - [ ] 2.2 Build CSV upload endpoints/services with schema validation and aggregated error reporting.
  - [ ] 2.3 Persist successful uploads, ensure idempotent updates, and cover ingestion with feature tests.
- [ ] 3.0 Build text ingestion and LLM-based event parsing pipeline
  - [ ] 3.1 Implement text extraction utilities for pasted text and PDF uploads (e.g., `smalot/pdfparser`).
  - [ ] 3.2 Create LLM client abstraction with conditional model routing and cost tracking hooks.
  - [ ] 3.3 Parse events into structured records, persist raw/parsed payloads, and expose verification tests.
- [ ] 4.0 Develop scenario engine, metrics, and recommendation logic
  - [ ] 4.1 Model scenarios and scenario results (migrations + models) with tenant scoping.
  - [ ] 4.2 Implement scenario engine service covering port closure, lead time inflation, and labor loss flows.
  - [ ] 4.3 Generate impact metrics and reroute recommendations; validate via unit/feature tests.
- [ ] 5.0 Deliver Inertia UI flows and user experience for uploads, events, and scenarios
  - [ ] 5.1 Build pages/forms for CSV upload with error summaries and sample template downloads.
  - [ ] 5.2 Implement event listing and detail views with “run scenario” actions.
  - [ ] 5.3 Create scenario configuration/result pages with summaries, tables, and loading states.
- [ ] 6.0 Implement observability, LLM usage tracking, and optional chat integrations
  - [ ] 6.1 Persist LLM usage metrics (model, tokens, cost) and surface admin reporting endpoints.
  - [ ] 6.2 Enhance logging/monitoring (e.g., Telescope local, structured logs); document operational runbooks.
  - [ ] 6.3 If capacity allows, expose REST hooks for Slack/Teams bot commands leveraging scenario APIs.
