-- One-time setup for existing projects. Run in the Supabase SQL Editor.
-- Auth users created from the Supabase Dashboard become admins. Accounts
-- invited as wedding guests by this app are marked account_type = 'guest'
-- and remain regular users.

create or replace function {{SUPABASE_SCHEMA}}.create_profile_for_auth_user()
returns trigger
language plpgsql
security definer
set search_path = ''
as $$
begin
  insert into {{SUPABASE_SCHEMA}}.profiles as existing_profile
    (id, email, display_name, label, role)
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
