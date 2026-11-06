-- Run once for an existing project after replacing {{SUPABASE_SCHEMA}}
-- with the configured schema name. Resolve existing duplicate email rows first.

create unique index if not exists guests_email_normalized_unique
  on {{SUPABASE_SCHEMA}}.guests (lower(btrim(email)))
  where btrim(email) <> '';
