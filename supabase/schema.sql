create schema if not exists {{SUPABASE_SCHEMA}};

create table {{SUPABASE_SCHEMA}}.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  email text not null unique,
  display_name text not null default '',
  label text not null check (label in ('Groomsmen', 'VIP', 'ATB', 'Providus', 'Bridesmaid', 'Grooms family', 'Brides family')),
  role text not null default 'user' check (role in ('user', 'admin')),
  created_at timestamptz not null default now()
);

create table {{SUPABASE_SCHEMA}}.guests (
  id text primary key,
  name text not null,
  email text not null,
  phone text not null default '',
  label text not null check (label in ('Groomsmen', 'VIP', 'ATB', 'Providus', 'Bridesmaid', 'Grooms family', 'Brides family')),
  attending boolean not null default true,
  invitation_sent boolean not null default false,
  auth_user_id uuid references auth.users(id) on delete set null
);

create table {{SUPABASE_SCHEMA}}.checkins (
  id text primary key references {{SUPABASE_SCHEMA}}.guests(id) on delete cascade,
  check_in_count integer not null default 0 check (check_in_count >= 0),
  updates jsonb not null default '[]'::jsonb
);

-- The PHP server uses the service_role key for database REST requests.
-- Exposing a schema in the API does not grant PostgreSQL schema/table access.
grant usage on schema {{SUPABASE_SCHEMA}} to service_role;
grant all privileges on all tables in schema {{SUPABASE_SCHEMA}} to service_role;

alter table {{SUPABASE_SCHEMA}}.profiles enable row level security;
alter table {{SUPABASE_SCHEMA}}.guests enable row level security;
alter table {{SUPABASE_SCHEMA}}.checkins enable row level security;

create policy "Users can read their own profile"
  on {{SUPABASE_SCHEMA}}.profiles for select to authenticated
  using (auth.uid() = id);

create or replace function {{SUPABASE_SCHEMA}}.create_profile_for_auth_user()
returns trigger
language plpgsql
security definer
set search_path = ''
as $$
begin
  insert into {{SUPABASE_SCHEMA}}.profiles as existing_profile (id, email, display_name, label, role)
  values (
    new.id,
    new.email,
    coalesce(new.raw_user_meta_data ->> 'display_name', ''),
    case
      when new.raw_user_meta_data ->> 'label' in ('Groomsmen', 'VIP', 'ATB', 'Providus', 'Bridesmaid', 'Grooms family', 'Brides family')
        then new.raw_user_meta_data ->> 'label'
      else 'VIP'
    end,
    case
      when new.raw_user_meta_data ->> 'account_type' = 'guest' then 'user'
      else 'admin'
    end
  )
  on conflict (id) do update set
    email = excluded.email,
    role = case
      when new.raw_user_meta_data ->> 'account_type' = 'guest' then existing_profile.role
      else 'admin'
    end;
  return new;
end;
$$;

create trigger on_auth_user_created
  after insert on auth.users
  for each row execute procedure {{SUPABASE_SCHEMA}}.create_profile_for_auth_user();
