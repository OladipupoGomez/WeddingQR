-- Run once in the Supabase SQL Editor for an existing project after replacing
-- {{SUPABASE_SCHEMA}} with the configured schema name.
-- VIP remains valid only for existing records; the application no longer offers it.

alter table {{SUPABASE_SCHEMA}}.profiles
  drop constraint if exists profiles_label_check;
alter table {{SUPABASE_SCHEMA}}.profiles
  add constraint profiles_label_check
  check (label in ('Groomsmen', 'Grooms friend', 'ATB', 'Providus', 'Bridesmaid', 'Brides friend', 'Grooms family', 'Brides family', 'VIP'));

alter table {{SUPABASE_SCHEMA}}.guests
  drop constraint if exists guests_label_check;
alter table {{SUPABASE_SCHEMA}}.guests
  add constraint guests_label_check
  check (label in ('Groomsmen', 'Grooms friend', 'ATB', 'Providus', 'Bridesmaid', 'Brides friend', 'Grooms family', 'Brides family', 'VIP'));

