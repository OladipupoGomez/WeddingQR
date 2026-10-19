create table if not exists {{SUPABASE_SCHEMA}}.app_sessions (
  session_id text primary key,
  payload text not null,
  expires_at timestamptz not null
);

create index if not exists app_sessions_expires_at_idx
  on {{SUPABASE_SCHEMA}}.app_sessions (expires_at);

alter table {{SUPABASE_SCHEMA}}.app_sessions enable row level security;
revoke all on {{SUPABASE_SCHEMA}}.app_sessions from anon, authenticated;
grant usage on schema {{SUPABASE_SCHEMA}} to service_role;
grant all privileges on table {{SUPABASE_SCHEMA}}.app_sessions to service_role;
