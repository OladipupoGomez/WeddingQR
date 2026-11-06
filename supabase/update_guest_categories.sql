-- Run once in the Supabase SQL Editor for an existing project after replacing
-- {{SUPABASE_SCHEMA}} with the configured schema name.
-- VIP remains valid only for existing records; the application no longer offers it.

begin;

alter table {{SUPABASE_SCHEMA}}.profiles
  drop constraint if exists profiles_label_check;
update {{SUPABASE_SCHEMA}}.profiles set label = 'ATB/MIVA' where upper(label) = 'ATB';
alter table {{SUPABASE_SCHEMA}}.profiles
  add constraint profiles_label_check
  check (label in ('Groomsmen', 'Grooms friend', 'ATB/MIVA', 'Providus', 'Bridesmaid', 'Brides friend', 'Grooms family', 'Brides family', 'VIP'));

alter table {{SUPABASE_SCHEMA}}.guests
  drop constraint if exists guests_label_check;
update {{SUPABASE_SCHEMA}}.guests set label = 'ATB/MIVA' where upper(label) = 'ATB';
alter table {{SUPABASE_SCHEMA}}.guests
  add constraint guests_label_check
  check (label in ('Groomsmen', 'Grooms friend', 'ATB/MIVA', 'Providus', 'Bridesmaid', 'Brides friend', 'Grooms family', 'Brides family', 'VIP'));

commit;
