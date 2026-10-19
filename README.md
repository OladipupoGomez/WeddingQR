# ADE & OLA

Wedding invitation website with a public, password-free RSVP and a private guest-management dashboard. Guests do not create accounts to RSVP.

## Requirements

- PHP 7.4 or newer with cURL enabled
- Composer
- A Supabase project

## Supabase Setup

1. Create a Supabase project.
2. Choose a lowercase schema name (for example, `wedding_app`). In each Supabase SQL script you use, replace every `{{SUPABASE_SCHEMA}}` placeholder with that name before running the script in the SQL Editor. Run `schema.sql` to create the empty profile, guest, and check-in tables; existing JSON files are not migrated. Add the same schema name to **Project Settings → API → Exposed schemas**.
3. In **Authentication > URL Configuration**, set the Site URL to `http://localhost:8001` and add these Redirect URLs:
   - `http://localhost:8001/login.php?confirmed=1`
   - `http://localhost:8001/invite`
4. In **Authentication > Providers**, enable email/password sign-in and email confirmation.
5. From **Project Settings > API** (or **Data API** in the current dashboard), copy the Project URL, the publishable/anon key, and the secret/service-role key. Put these in `config.local.php` as `SUPABASE_PROJECT_URL`, `SUPABASE_PUBLIC_KEY`, and `SUPABASE_PRIVATE_SERVICE_KEY`; set `SUPABASE_SCHEMA` to the same schema name. Never paste the private service-role key into chat, frontend code, or a committed file.
6. Copy `config.example.php` to `config.local.php`. Set `ADEOLA_DEMO_MODE` to `false`, enter those three Supabase values, set `RSVP_EMAIL_FUNCTION_SECRET` to a long random value, and use `ADEOLA_APP_URL` for the current site URL.
7. Under **Authentication > URL Configuration**, set the Site URL to the live site URL and add it to the Redirect URLs. For local testing use `http://localhost:8001`, `http://localhost:8001/login.php?confirmed=1`, and `http://localhost:8001/invite`.
8. For Supabase Auth invitation emails, configure **Authentication > SMTP Settings**. For RSVP and uninvitation emails, configure a verified sender with Resend and set the Edge Function secrets below.

## Admin Access

The public wedding site is at `/`; the password-free RSVP is at `/register`; admin tools are at `/admin` and require an admin account. In demo mode, open `/login.php` and use **Set up the demo admin account** before exposing the site. The first demo account becomes admin.

With Supabase, run [`supabase/admin_creation_trigger.sql`](supabase/admin_creation_trigger.sql) once in the SQL Editor after replacing its schema placeholder as above. After that, Auth users created through **Authentication > Users** automatically get the `admin` role. Guest accounts invited by the app carry an `account_type = guest` marker and remain regular users. Turn off public email sign-ups in Supabase Auth settings so untrusted sign-ups cannot receive admin access. The standalone [`supabase/promote_admin.sql`](supabase/promote_admin.sql) remains available to manually promote an existing profile.

Sign in at `/login.php` after promoting the user. The admin dashboard displays RSVPs. Admins can add guests; each guest must have an email and guest category, and receives a Supabase invitation email to set a password. The public RSVP does not create an account or ask for a password. In live mode, public RSVP confirmation emails include a guest QR code and Google Maps directions. Deleting an admin guest sends a polite uninvitation email before removing the record; if email delivery fails, removal is stopped so it can be retried. Labels categorize guests and do not grant permissions.

## Run Locally

From the project directory:

```sh
composer install
php -S localhost:8001 router.php
```

Open `http://localhost:8001` for the invitation, `http://localhost:8001/register` to RSVP, and `http://localhost:8001/admin` for administration. Port 8000 was already in use during setup, so the app is configured for 8001. For Supabase email links to work, add the exact local redirect URLs above. PHP's built-in server is for local development; production deployments need HTTPS and a configured PHP runtime.

## Deploy to Vercel

This project uses the community `vercel-php` runtime. Before importing the repository into Vercel, replace the schema placeholder in [`supabase/vercel_sessions.sql`](supabase/vercel_sessions.sql) with your chosen schema and run it once in the Supabase SQL Editor. The app stores PHP sessions in that table so admin sign-ins and RSVP form tokens work across serverless requests. Vercel deployments must use Supabase mode; local JSON data is excluded from deployments.

In the Vercel project settings, add these environment variables for Production and Preview:

- `ADEOLA_DEMO_MODE=false`
- `SUPABASE_PROJECT_URL`
- `SUPABASE_PUBLIC_KEY`
- `SUPABASE_PRIVATE_SERVICE_KEY`
- `SUPABASE_SCHEMA` (the same schema name used in the SQL scripts)
- `RSVP_EMAIL_FUNCTION_SECRET`
- `ADEOLA_APP_URL` (the deployed site URL, such as `https://your-project.vercel.app`)
- `ADEOLA_GIFT_REGISTRY_URL` (your GiftDice wishlist share link)

Import the GitHub repository in Vercel and deploy a Preview first. Add the deployed URL to Supabase Authentication's Site URL and Redirect URLs before using guest invitations. Keep the service-role key in Vercel's server-side environment settings only.

## Local Demo Mode

Demo mode is enabled automatically when Supabase credentials are not configured. You can also force it with `define('ADEOLA_DEMO_MODE', true);` in `config.local.php`. Create the first admin account from the login page using its setup link. Demo accounts and password hashes are stored locally in ignored `data/demo_accounts.php`; RSVP and check-in changes use the local JSON files. RSVPs require no password, and no confirmation or invitation emails are sent in demo mode. To switch back to Supabase, set `ADEOLA_DEMO_MODE` to `false` after filling in the real project credentials.

## RSVP Confirmation Email

RSVP confirmations and guest-uninvitation notices are sent through [`supabase/functions/rsvp-confirmation/index.ts`](supabase/functions/rsvp-confirmation/index.ts). RSVP emails include the guest's QR code and Google Maps directions. When an admin deletes a guest, the app sends a polite uninvitation email first; if delivery fails, deletion is stopped so the admin can retry. In demo mode no email is sent and deletion remains local. Deploy or redeploy the function with `supabase functions deploy rsvp-confirmation` and leave JWT verification enabled. Set its Edge Function secrets `RESEND_API_KEY`, `RSVP_FROM_EMAIL` (a sender address verified by Resend), and `RSVP_EMAIL_FUNCTION_SECRET`; use the same function secret in `config.local.php`. Guest account invitations use Supabase Auth SMTP and templates.

## Security Notes

- Passwords and email confirmation are managed by Supabase Auth.
- The Supabase service-role key is used only by the PHP server and bypasses Row Level Security. Keep it private.
- Profile, guest, and check-in tables have Row Level Security enabled; the public API key cannot directly read or modify guest/check-in records.
- Admin-only account listing, guest registration, and CSV import are checked server-side against the current Supabase profile role.
- Existing `data/guests.json` and `data/checkin.json` data remains untouched. New records go to Supabase.
