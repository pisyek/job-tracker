# Job Tracker Auth + Per-User Data Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Status:** Plan revised 2026-11-28 for **per-user data**. No product code has been changed yet.

**Goal:** Port the authentication feature from `job-tracker-auth` (Laravel Livewire starter kit) into `job-tracker`, make every company and job application belong to a user, then restyle the existing pages in the same Tailwind 4 + Flux visual language.

**Architecture:** Install Fortify for the auth backend, Livewire 4 for Flux's runtime, and Flux free for the UI. Fortify owns all auth routes and controllers; this project supplies config, actions, views, and middleware. **Registration is enabled**, every authenticated user sees only their own companies and applications, and ownership is enforced in three places: scoped route model binding, scoped queries, and scoped validation. A foreign record returns **404**, never a 403 that leaks existence.

**Tech Stack:** PHP 8.5, Laravel 13.42, Fortify 1.37, Livewire 4, Flux 2.x (free), Tailwind CSS 4, Vite 8, Pest 5, SQLite.

**Design source:** This file. The user requested the plan at `build-auth.md`, so the design decisions and the implementation plan live together here instead of a separate `docs/superpowers/specs/` document.

**Baseline:** branch `main`, clean working tree at commit `5932b91` (`add composer step`), plus this untracked plan file. Confirm with `git status --short` before starting.

---

## Approved Decisions

> **Revision note:** Decisions 1 and 2 replace the earlier single-user design. The app is now multi-user with per-user ownership. Anything in this file that still implies "one owner" is a bug in the plan, not a requirement.

1. **Multi-user app with per-user data.** Public registration is enabled. Each user owns their own companies and job applications and can never read or mutate another user's rows. No shared/global tracker data.
2. **Ownership columns on both tables.** `companies.user_id` and `job_applications.user_id`, both set from `auth()->id()` on the server, never from request input. An application's `company_id` must belong to the same user; this is validated, not assumed.
3. **Auth stack is Fortify + Livewire + Flux free.** No teams, invitations, or team switcher.
4. **Email verification is implemented but not enforced.** `User` implements `MustVerifyEmail`, the verification routes and page exist, and the app shell shows a "verify your email" callout. Tracker routes use `auth` only, not `verified`, because `MAIL_MAILER=log` would otherwise lock every new user out until they read `storage/logs/laravel.log`. Adding `verified` to the route group is the one-line switch to enforce it.
5. **Visual language comes from `job-tracker-auth`.** Dark theme (`<html class="dark">`), Instrument Sans, Flux neutral/zinc palette, simple centered auth layout.
6. **Core auth ships first.** Login, register, logout, forgot/reset password, email verification, confirm password. Two-factor authentication, passkeys, and Settings → Security are Phase 3 and can be deferred without blocking Phases 1–2.
7. **Keep Vite 8.** Do not adopt `vite-plus` from the starter kit. Keep the existing `vite.config.js` shape and add `resources/js/passkeys.js` only in Phase 3.

---

## Data Model and Ownership Rules

### Schema

```
users
  id, name, email, password, email_verified_at, remember_token, timestamps

companies
  id, user_id -> users.id (cascade delete), name, website, location, timestamps
  index (user_id)
  unique (user_id, name)   <-- per-user uniqueness, not global

job_applications
  id, user_id -> users.id (cascade delete), company_id -> companies.id (cascade delete),
  role_title, status, salary_min, salary_max, applied_on, notes, timestamps
  index (user_id)
```

`user_id` is stored on `job_applications` as well as `companies` so index queries and route bindings stay a single indexed `where user_id = ?` instead of a `whereHas` subquery. The cost is one redundant column; the guarantee is that the two can never diverge because both are written from the same authenticated user and the company is validated against that user.

### Enforcement matrix

| Risk | Control | Where |
|---|---|---|
| Guest opens `/applications` | `auth` middleware → redirect to `/login` | `routes/web.php` |
| User opens another user's application/company URL | Scoped route binding → `findOrFail` → **404** | `AppServiceProvider::boot()` |
| User lists data | `$request->user()->jobApplications()` / `->companies()` | Controllers |
| User submits another user's `company_id` | `Rule::exists(...)->where('user_id', ...)` → validation error | Form Requests |
| User reuses another user's company name | `Rule::unique(...)->where('user_id', ...)` → validation error | `StoreCompany` |
| Application written with a mismatched `user_id` | `user_id` never accepted from input; set by relationship | Controllers |
| Factory/test data with mismatched owner | Factory derives `user_id` from `company->user_id` | `JobApplicationFactory` |

### Deliberate trade-offs

- **No policies.** Scoped route binding plus relationship-scoped queries already produce a 404 for foreign records. Adding `CompanyPolicy`/`JobApplicationPolicy` on top would be a second source of truth for the same rule. If a future feature needs role-based access (admin, sharing), add policies then and delete the bindings.
- **`user_id` stays nullable at the database level.** The backfill migration adds the column, assigns existing rows to the oldest user, then tightens to non-null when no orphans remain. Nullable keeps the migration safe on SQLite and on databases that have rows but no users yet. The application never writes null, and a test asserts no orphan rows exist after the normal flows.
- **Verification is not a gate.** See decision 4. The page and routes ship; the middleware does not.

---

## Dependency Audit

### Must install

| Package | Type | Why | Phase |
|---|---|---|---|
| `laravel/fortify` | Composer | Auth routes, login/logout, registration, password reset, email verification, 2FA, passkeys | 1 |
| `livewire/livewire` | Composer | Required by Flux; provides `@persist`, Alpine, and Livewire SFCs for Phase 3 | 1 |
| `livewire/flux` | Composer | The actual UI components and `flux.css` / `@fluxScripts` | 1 |
| `@laravel/passkeys` | npm | Browser WebAuthn helper for passkey login/registration | 3 (only if passkeys are kept) |

`laravel/fortify` already requires `laravel/passkeys` (PHP) and `pragmarx/google2fa`, so those are transitive and must not be added manually.

### Already present — do not reinstall

| Package | Current | Notes |
|---|---|---|
| `laravel/framework` | `^13.17` | Laravel 13.42 installed |
| `tailwindcss` | `^4.0.0` | Tailwind 4 is already configured |
| `@tailwindcss/vite` | `^4.0.0` | Keep |
| `laravel-vite-plugin` | `^3.1` | Keep |
| `vite` | `^8.0.0` | Keep |
| `pestphp/pest` | `^5.3` | Keep |
| `laravel/pint` | `^1.27` | Keep |

### Deliberately not installing

| Package | Why not |
|---|---|
| `livewire/blaze` | Optional Blade-component optimizer used by the starter kit; Flux works without it |
| `laravel/chisel` | Starter-kit pruning tool; not a runtime dependency |
| `vite-plus` | Starter-kit build tool; this project keeps Vite 8 |
| `pestphp/pest-plugin-drift` | Starter-kit-only test helper; not needed |

### Commands

```powershell
# Phase 1
composer require laravel/fortify livewire/livewire livewire/flux
php artisan fortify:install --no-interaction

# Phase 3 only
npm install @laravel/passkeys
```

`fortify:install` publishes `config/fortify.php`, the Fortify service provider, the Fortify actions, the 2FA migration, and the passkeys migration, then adds `App\Providers\FortifyServiceProvider` to `bootstrap/providers.php`.

> **Do not re-run `php artisan fortify:install` after editing the published files.** It overwrites `config/fortify.php` and `app/Providers/FortifyServiceProvider.php`.

---

## Global Constraints

- Follow existing project conventions: `@extends('layouts.app')`, named routes, Form Requests where already used, Pest tests.
- Use Flux **free** components only: button, input, select, textarea, checkbox, heading, subheading, text, link, badge, icon, avatar, dropdown, menu, sidebar, header, main, modal, toast, navlist, separator, callout, otp. Do not use Flux Pro components (date picker, table, tabs, etc.).
- Keep `resources/css/app.css` as the single Tailwind entry point. Do not reintroduce `simple.css` or a CDN stylesheet.
- Every auth page must render through `resources/views/layouts/auth.blade.php`; every tracker page through `resources/views/layouts/app.blade.php`.
- **Ownership is not optional.** Every query that returns companies or applications must start from `auth()->user()`, and every write must go through a relationship (`$user->companies()->create(...)`) or set `user_id` from `auth()->id()`. Never accept `user_id` from `$request->validated()`.
- Do not add a global scope for ownership. Global scopes hide bugs in seeders, queues, and tests, and make "why is this row missing" impossible to debug.
- Run `vendor/bin/pint --dirty --format agent` after PHP changes. On Windows, if the shim is missing, use `vendor\bin\pint.bat`.
- Run `npm run build` after CSS/Blade changes and `php artisan optimize:clear` after config/route changes.
- Do not create documentation files other than this one unless the user asks.

---

## Review Focus

These are the inputs and failure modes most likely to bite, and each gets a test in the task that owns the code:

1. **Guest requests any tracker route** → redirect to `/login`, never 500 or leak data.
2. **User A requests user B's application or company** → **404** (not 403, not a rendered page).
3. **User A's index page** → contains only A's rows, even when B has rows with the same status or name.
4. **User A submits user B's `company_id`** → validation error on `company_id`, no row written.
5. **User A registers a company name that B already uses** → allowed (per-user uniqueness). A second company with the same name for the same user → validation error.
6. **Invalid login credentials** → error on the `email` field, still a guest, no session user.
7. **Expired or invalid password-reset token** → validation error, password unchanged.
8. **Invalid application/company form payload** → validation errors, no database write.
9. **Registration** → account created, logged in, redirected to `/applications`, own empty state shown.
10. **Logout** → session invalidated, redirected to `/`, tracker routes inaccessible afterward.
11. **Delete request without CSRF token** → 419, record remains.
12. **Flux assets missing after a fresh clone** → pages must not render unstyled; `npm run build` fixes it, and the layout must include `@fluxScripts`.
13. **Orphan rows** → after the normal create flows, no company or application has `user_id = null`.

---

## File Map

### Create — Phase 1

| File | Responsibility |
|---|---|
| `config/fortify.php` | Fortify guard, home path, features (registration enabled), limiters, passkey settings |
| `app/Providers/FortifyServiceProvider.php` | Fortify actions, view bindings, rate limiters |
| `app/Actions/Fortify/CreateNewUser.php` | Registration action (no teams) |
| `app/Actions/Fortify/ResetUserPassword.php` | Password reset action |
| `app/Concerns/PasswordValidationRules.php` | Shared password rules |
| `app/Concerns/ProfileValidationRules.php` | Shared name/email rules |
| `database/migrations/*_add_user_id_to_tracker_tables.php` | Ownership columns, backfill, per-user index |
| `app/Http/Requests/StoreCompany.php` | Company validation, per-user unique name |
| `app/Http/Requests/UpdateJobApplication.php` | Update rules (extends the store request) |
| `resources/views/partials/head.blade.php` | Shared `<head>`: title, icons, fonts, Vite, Flux appearance |
| `resources/views/layouts/auth.blade.php` | Full-page auth shell, centered card, Flux scripts |
| `resources/views/layouts/app.blade.php` | Full-page tracker shell with sidebar/header |
| `resources/views/components/app-logo-icon.blade.php` | Logo mark |
| `resources/views/components/app-logo.blade.php` | Logo + app name |
| `resources/views/components/auth-header.blade.php` | Auth page title/description |
| `resources/views/components/auth-session-status.blade.php` | Green status message |
| `resources/views/components/user-menu.blade.php` | Avatar dropdown with logout |
| `resources/views/components/status-badge.blade.php` | Application status badge |
| `resources/views/components/empty-state.blade.php` | Reusable empty state |
| `resources/views/pages/auth/login.blade.php` | Login |
| `resources/views/pages/auth/register.blade.php` | Register (live route) |
| `resources/views/pages/auth/forgot-password.blade.php` | Request reset link |
| `resources/views/pages/auth/reset-password.blade.php` | Set new password |
| `resources/views/pages/auth/confirm-password.blade.php` | Password confirmation |
| `resources/views/pages/auth/verify-email.blade.php` | Verification notice |
| `tests/Feature/Auth/AuthenticationTest.php` | Login/logout/guest tests |
| `tests/Feature/Auth/RegistrationTest.php` | Registration flow and duplicate email |
| `tests/Feature/Auth/PasswordResetTest.php` | Reset flow tests |
| `tests/Feature/Auth/EmailVerificationTest.php` | Verification tests |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | Confirm screen test |
| `tests/Feature/OwnershipTest.php` | Cross-user isolation and scoped validation |
| `tests/Feature/CompanyTest.php` | Company pages and validation |

### Modify — Phase 1

| File | Change |
|---|---|
| `composer.json` / `composer.lock` | Add Fortify, Livewire, Flux |
| `bootstrap/providers.php` | Register `FortifyServiceProvider` (installer does this) |
| `app/Models/User.php` | Implement `MustVerifyEmail`; add `companies()` and `jobApplications()`; later 2FA/passkey traits |
| `app/Models/Company.php` | Add `user_id`, `user()` relation |
| `app/Models/JobApplication.php` | Add `user_id`, `user()` relation |
| `app/Providers/AppServiceProvider.php` | Password defaults, immutable dates, destructive-command guard, scoped route bindings |
| `app/Http/Controllers/JobApplicationController.php` | Scope every query and write to the authenticated user; use `UpdateJobApplication` |
| `app/Http/Controllers/CompanyController.php` | Scope every query and write to the authenticated user; use `StoreCompany` |
| `app/Http/Requests/StoreJobApplication.php` | Scope `company_id` to the authenticated user's companies |
| `database/factories/CompanyFactory.php` | Default `user_id => User::factory()` |
| `database/factories/JobApplicationFactory.php` | Derive `user_id` from the related company |
| `database/seeders/DatabaseSeeder.php` | Idempotent demo user + demo data |
| `database/seeders/CompanySeeder.php` | Attach companies and applications to the demo user |
| `routes/web.php` | Name `/` as `home`; wrap tracker routes in `auth` |
| `.env` / `.env.example` | `APP_NAME="Job Tracker"`, `DEMO_*` seed values |
| `resources/css/app.css` | Replace with starter-kit Flux/Tailwind CSS |
| `tests/TestCase.php` | Add `skipUnlessFortifyHas` helper for Phase 3 |
| `tests/Feature/JobApplicationTest.php` | Authenticate requests; ownership-aware factories |

### Create — Phase 2

| File | Responsibility |
|---|---|
| `resources/views/applications/index.blade.php` | Restyled list + summary cards |
| `resources/views/applications/show.blade.php` | Restyled detail card |
| `resources/views/applications/_form.blade.php` | Flux form fields |
| `resources/views/applications/create.blade.php` | Restyled create page |
| `resources/views/applications/edit.blade.php` | Restyled edit page |
| `resources/views/companies/index.blade.php` | Restyled company list |
| `resources/views/companies/show.blade.php` | Restyled company detail |
| `resources/views/companies/create.blade.php` | Restyled company form |

### Create — Phase 3 (optional)

| File | Responsibility |
|---|---|
| `database/migrations/*_add_two_factor_columns_to_users_table.php` | 2FA columns (published by Fortify) |
| `database/migrations/*_create_passkeys_table.php` | Passkeys table (published by Fortify) |
| `resources/views/pages/auth/two-factor-challenge.blade.php` | 2FA challenge |
| `resources/views/pages/settings/⚡security.blade.php` | Password, 2FA, passkey management |
| `resources/views/pages/settings/⚡two-factor-setup-modal.blade.php` | 2FA setup modal |
| `resources/views/pages/settings/two-factor/⚡recovery-codes.blade.php` | Recovery codes |
| `resources/views/pages/settings/layout.blade.php` | Settings shell |
| `resources/views/partials/settings-heading.blade.php` | Settings page heading |
| `resources/views/components/passkey-verify.blade.php` | Passkey login button |
| `resources/views/components/passkey-registration.blade.php` | Passkey registration UI |
| `resources/js/passkeys.js` | `@laravel/passkeys` bootstrap |
| `routes/settings.php` | Settings routes |
| `tests/Feature/Auth/TwoFactorChallengeTest.php` | 2FA tests |
| `tests/Feature/Settings/SecurityTest.php` | Security page tests |

---

# Phase 1 — Auth Foundation

## Task 1: Install Fortify, Livewire, and Flux

**Files:**
- Modify: `composer.json`, `composer.lock`
- Create (via installer): `config/fortify.php`, `app/Providers/FortifyServiceProvider.php`, `app/Actions/Fortify/*`, Fortify migrations
- Modify (via installer): `bootstrap/providers.php`

**Interfaces:**
- Produces: `App\Providers\FortifyServiceProvider` registered in the app; `config('fortify.home')` available; Fortify route names `login`, `login.store`, `register`, `register.store`, `logout`, `password.request`, `password.email`, `password.reset`, `password.update`, `verification.notice`, `verification.verify`, `verification.send`, `password.confirm`, `password.confirm.store`.

- [ ] **Step 1: Install the Composer packages**

Run:
```powershell
composer require laravel/fortify livewire/livewire livewire/flux
```

Expected: `composer.json` gains `laravel/fortify`, `livewire/livewire`, `livewire/flux`; `composer.lock` updates; no install errors.

- [ ] **Step 2: Run the Fortify installer**

Run:
```powershell
php artisan fortify:install --no-interaction
```

Expected:
- `config/fortify.php` exists.
- `app/Providers/FortifyServiceProvider.php` exists.
- `app/Actions/Fortify/CreateNewUser.php`, `ResetUserPassword.php`, `PasswordValidationRules.php`, `UpdateUserPassword.php`, `UpdateUserProfileInformation.php` exist.
- `database/migrations/*_add_two_factor_columns_to_users_table.php` and `*_create_passkeys_table.php` exist.
- `bootstrap/providers.php` contains `App\Providers\FortifyServiceProvider::class`.

- [ ] **Step 3: Verify the provider and routes**

Run:
```powershell
php artisan about --only=environment
php artisan route:list --except-vendor
```

Expected: `login`, `register`, `logout`, `forgot-password`, `reset-password`, `email/verify`, and `user/confirm-password` routes are listed.

- [ ] **Step 4: Commit**

```powershell
git add composer.json composer.lock bootstrap/providers.php config/fortify.php app/Providers/FortifyServiceProvider.php app/Actions/Fortify database/migrations
git commit -m "chore: install fortify, livewire, and flux"
```

---

## Task 2: Configure Fortify for Registration and Per-User Accounts

**Files:**
- Modify: `config/fortify.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `app/Actions/Fortify/CreateNewUser.php`
- Modify: `app/Actions/Fortify/ResetUserPassword.php`
- Create: `app/Concerns/PasswordValidationRules.php`
- Create: `app/Concerns/ProfileValidationRules.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Interfaces:**
- Consumes: `Laravel\Fortify\Fortify` static configuration API; `App\Models\User`.
- Produces: `Fortify::loginView()` etc. bound to `pages.auth.*` views; `config('fortify.home') === '/applications'`; registration enabled; `Password::defaults()` configured for production.

- [ ] **Step 1: Point Fortify at the tracker and enable registration**

In `config/fortify.php` set:
```php
'home' => '/applications',
```

Replace the `features` array with:
```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
],
```

Leave `updateProfileInformation`, `updatePasswords`, `twoFactorAuthentication`, and `passkeys` out for now. They are added in Phase 3.

Keep the `limiters`, `views`, and `passkeys` blocks from the published file.

- [ ] **Step 2: Move the shared validation traits**

Create `app/Concerns/PasswordValidationRules.php` with the `passwordRules()` and `currentPasswordRules()` methods from `job-tracker-auth/app/Concerns/PasswordValidationRules.php`.

Create `app/Concerns/ProfileValidationRules.php` with `profileRules()`, `nameRules()`, and `emailRules()` from `job-tracker-auth/app/Concerns/ProfileValidationRules.php`.

Update `CreateNewUser` and `ResetUserPassword` to `use App\Concerns\PasswordValidationRules;`. Delete the generated `app/Actions/Fortify/PasswordValidationRules.php` after the move (it is a generated duplicate).

- [ ] **Step 3: Simplify `CreateNewUser`**

Keep the `CreatesNewUsers` contract. Validate with:
```php
Validator::make($input, [
    ...$this->profileRules(),
    'password' => $this->passwordRules(),
])->validate();
```

Create the user with:
```php
return User::create([
    'name' => $input['name'],
    'email' => $input['email'],
    'password' => $input['password'],
]);
```

Do not create a team. The `password` cast on `User` hashes it. Fortify logs the user in and redirects to `config('fortify.home')`.

- [ ] **Step 4: Simplify `ResetUserPassword`**

Keep the `ResetsUserPasswords` contract. Validate `password` with `$this->passwordRules()` and save with:
```php
$user->forceFill(['password' => $input['password']])->save();
```

- [ ] **Step 5: Bind the Fortify views**

In `FortifyServiceProvider::boot()` call:
```php
$this->configureActions();
$this->configureViews();
$this->configureRateLimiting();
```

Add:
```php
private function configureViews(): void
{
    Fortify::loginView(fn () => view('pages.auth.login'));
    Fortify::registerView(fn () => view('pages.auth.register'));
    Fortify::verifyEmailView(fn () => view('pages.auth.verify-email'));
    Fortify::confirmPasswordView(fn () => view('pages.auth.confirm-password'));
    Fortify::resetPasswordView(fn () => view('pages.auth.reset-password'));
    Fortify::requestPasswordResetLinkView(fn () => view('pages.auth.forgot-password'));
}
```

Keep `Fortify::createUsersUsing(CreateNewUser::class)`, `Fortify::resetUserPasswordsUsing(ResetUserPassword::class)`, and `Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class)`.

- [ ] **Step 6: Configure password defaults**

In `AppServiceProvider::boot()` add the same defaults as the starter kit:
```php
Date::use(CarbonImmutable::class);

DB::prohibitDestructiveCommands(app()->isProduction());

Password::defaults(fn (): ?Password => app()->isProduction()
    ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
    : null,
);
```

- [ ] **Step 7: Clear caches and verify**

Run:
```powershell
php artisan optimize:clear
php artisan route:list --except-vendor
```

Expected: `register` and `register.store` are **present**; `login`, `logout`, password reset, verification, and confirm-password routes are present.

- [ ] **Step 8: Commit**

```powershell
git add config/fortify.php app/Providers/FortifyServiceProvider.php app/Actions/Fortify app/Concerns app/Providers/AppServiceProvider.php
git commit -m "feat: configure fortify with registration and per-user accounts"
```

---

## Task 3: Auth Layout, Shared Components, and Flux CSS

**Files:**
- Create: `resources/views/partials/head.blade.php`
- Create: `resources/views/layouts/auth.blade.php`
- Create: `resources/views/components/app-logo-icon.blade.php`
- Create: `resources/views/components/app-logo.blade.php`
- Create: `resources/views/components/auth-header.blade.php`
- Create: `resources/views/components/auth-session-status.blade.php`
- Modify: `resources/css/app.css`

**Interfaces:**
- Produces: `layouts.auth` with `@yield('title')` and `@yield('content')`; components `x-app-logo-icon`, `x-app-logo`, `x-auth-header`, `x-auth-session-status`; Flux CSS loaded in the Vite bundle.

- [ ] **Step 1: Copy the shared `<head>` partial**

Base it on `job-tracker-auth/resources/views/partials/head.blade.php`:
```blade
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
```

- [ ] **Step 2: Write the auth layout**

Use the starter kit's `layouts/auth/simple.blade.php` as the visual source, but expose sections:
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @php($pageTitle = trim($__env->yieldContent('title')))
    @include('partials.head', ['title' => $pageTitle])
</head>
<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-2">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium">
                <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                    <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>
            <div class="flex flex-col gap-6">
                @yield('content')
            </div>
        </div>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
```

- [ ] **Step 3: Copy the logo and auth components**

Copy `app-logo-icon.blade.php`, `app-logo.blade.php`, `auth-header.blade.php`, and `auth-session-status.blade.php` from `job-tracker-auth/resources/views/components/`.

Optional branding: replace the Laravel mark path in `app-logo-icon.blade.php` with a Heroicons briefcase path. Keep the same `viewBox`, `fill="currentColor"`, and class handling.

- [ ] **Step 4: Replace `resources/css/app.css`**

Copy `job-tracker-auth/resources/css/app.css`, then:
- Keep `@import 'tailwindcss';` and `@import '../../vendor/livewire/flux/dist/flux.css';`.
- Keep `@source '../views';`, the framework pagination source, and `@source '../../vendor/livewire/flux/stubs/**/*.blade.php';`.
- Remove the `flux-pro` source line (Flux Pro is not installed).
- Add `@source '../../storage/framework/views/*.php';`.
- Keep `@custom-variant dark`, the `@theme` accent variables, the dark accent overrides, and the Flux field/label/control rules.

- [ ] **Step 5: Build and verify**

Run:
```powershell
npm run build
php artisan view:clear
```

Expected: Vite build succeeds; no "Unable to locate file in Vite manifest" error.

- [ ] **Step 6: Commit**

```powershell
git add resources/views/partials/head.blade.php resources/views/layouts/auth.blade.php resources/views/components/app-logo-icon.blade.php resources/views/components/app-logo.blade.php resources/views/components/auth-header.blade.php resources/views/components/auth-session-status.blade.php resources/css/app.css
git commit -m "feat: add auth layout, components, and flux styles"
```

---

## Task 4: Create All Auth Pages

**Files:**
- Create: `resources/views/pages/auth/login.blade.php`
- Create: `resources/views/pages/auth/register.blade.php`
- Create: `resources/views/pages/auth/forgot-password.blade.php`
- Create: `resources/views/pages/auth/reset-password.blade.php`
- Create: `resources/views/pages/auth/confirm-password.blade.php`
- Create: `resources/views/pages/auth/verify-email.blade.php`

**Interfaces:**
- Consumes: `layouts.auth`, `x-auth-header`, `x-auth-session-status`, Flux components.
- Produces: views named `pages.auth.login`, `pages.auth.register`, `pages.auth.forgot-password`, `pages.auth.reset-password`, `pages.auth.confirm-password`, `pages.auth.verify-email`.

- [ ] **Step 1: Copy and adapt the login page**

Start from `job-tracker-auth/resources/views/pages/auth/login.blade.php`. Change the wrapper to:
```blade
@extends('layouts.auth')

@section('title', __('Log in'))

@section('content')
    ...
@endsection
```

Remove the `$teamInvitation` block and the `<x-passkey-verify />` component (Phase 3). Keep the email, password, remember-me, and submit fields. Registration is enabled, so keep the "Sign up" link and guard it anyway:
```blade
@if (Route::has('register'))
    <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
@endif
```

- [ ] **Step 2: Copy and adapt the register page**

Same `@extends` conversion. Remove the team invitation block. Keep name, email, password, and confirmation fields. The route is live in this design; the page must render and submit successfully.

- [ ] **Step 3: Copy and adapt the password pages**

Convert `forgot-password`, `reset-password`, and `confirm-password` to `@extends('layouts.auth')`. Remove the passkey component from `confirm-password` (Phase 3). Keep the hidden token field and the `passwordrules` attributes.

- [ ] **Step 4: Copy and adapt the verification page**

Convert `verify-email` to `@extends('layouts.auth')`. Keep the resend form and the logout form.

- [ ] **Step 5: Verify rendering**

Run:
```powershell
npm run build
php artisan view:clear
php artisan route:list --except-vendor
```

Then request `GET /login` and `GET /register` through the app (Herd URL or `php artisan serve`) and confirm both return 200 with the dark centered card, Flux inputs, and no `simple.css`.

- [ ] **Step 6: Commit**

```powershell
git add resources/views/pages/auth
git commit -m "feat: add login, register, password, and verification pages"
```

---

## Task 5: Add User Ownership to the Schema and Models

**Files:**
- Create: `database/migrations/*_add_user_id_to_tracker_tables.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Company.php`
- Modify: `app/Models/JobApplication.php`
- Modify: `database/factories/CompanyFactory.php`
- Modify: `database/factories/JobApplicationFactory.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `database/seeders/CompanySeeder.php`
- Modify: `.env`, `.env.example`

**Interfaces:**
- Produces: `companies.user_id` and `job_applications.user_id`; `User::companies()`, `User::jobApplications()`, `Company::user()`, `JobApplication::user()`; factories that always agree on ownership; idempotent demo seeding.
- Consumes: existing `users`, `companies`, `job_applications` tables.

- [ ] **Step 1: Create the ownership migration**

```powershell
php artisan make:migration add_user_id_to_tracker_tables --no-interaction
```

Write:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        $ownerId = DB::table('users')->orderBy('id')->value('id');

        if ($ownerId !== null) {
            DB::table('companies')->whereNull('user_id')->update(['user_id' => $ownerId]);
            DB::table('job_applications')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }

        $this->tightenToNonNullable();
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Existing rows are backfilled to the oldest user. If rows remain without an
     * owner (no users exist yet), the column stays nullable so the migration does
     * not fail; the app never writes null and the ownership test asserts this.
     */
    private function tightenToNonNullable(): void
    {
        $hasOrphans = DB::table('companies')->whereNull('user_id')->exists()
            || DB::table('job_applications')->whereNull('user_id')->exists();

        if ($hasOrphans) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
```

> If SQLite rejects the `change()` on your Laravel build, delete the `tightenToNonNullable()` call and keep the column nullable. Everything else in this plan works unchanged; only the database-level guarantee is lost.

- [ ] **Step 2: Add the relationships**

`app/Models/User.php`:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function companies(): HasMany
{
    return $this->hasMany(Company::class);
}

public function jobApplications(): HasMany
{
    return $this->hasMany(JobApplication::class);
}
```

`app/Models/Company.php`: add `'user_id'` to `$fillable` and:
```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

`app/Models/JobApplication.php`: add `'user_id'` to `$fillable` and:
```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

> `user_id` is fillable so factories and `create()` work. Controllers must still never pass raw request input; they use `$request->validated()`, which excludes `user_id`.

- [ ] **Step 3: Make the factories ownership-safe**

`CompanyFactory::definition()` gains:
```php
'user_id' => User::factory(),
```

`JobApplicationFactory` keeps `'company_id' => Company::factory()` and adds a configure hook so the application always inherits the company's owner:
```php
public function configure(): static
{
    return $this->afterMaking(function (JobApplication $application): void {
        if ($application->user_id === null && $application->company !== null) {
            $application->user_id = $application->company->user_id;
        }
    });
}
```

Add a test-friendly state:
```php
public function forUser(User $user): static
{
    return $this->state(fn (): array => ['user_id' => $user->id])
        ->for(Company::factory()->for($user));
}
```

This makes `JobApplication::factory()->for($company)->create()` correct automatically, and `JobApplication::factory()->forUser($user)->create()` a one-liner in tests.

- [ ] **Step 4: Make the seeders idempotent and owner-aware**

`DatabaseSeeder::run()`:
```php
$demo = User::firstOrCreate(
    ['email' => env('DEMO_EMAIL', 'demo@example.com')],
    [
        'name' => env('DEMO_NAME', 'Demo User'),
        'password' => env('DEMO_PASSWORD', 'password'),
        'email_verified_at' => now(),
    ],
);

if ($demo->companies()->doesntExist()) {
    $this->call(CompanySeeder::class);
}
```

`CompanySeeder::run()`:
```php
$user = User::where('email', env('DEMO_EMAIL', 'demo@example.com'))->firstOrFail();

Company::factory()
    ->count(10)
    ->for($user)
    ->has(
        JobApplication::factory()
            ->count(3)
            ->sequence(
                ['status' => ApplicationStatus::Wishlist],
                ['status' => ApplicationStatus::Applied, 'applied_on' => now()->subDays(5)],
                ['status' => ApplicationStatus::Interviewing, 'applied_on' => now()->subDays(12)],
            ),
    )
    ->create();
```

The `configure()` hook copies `user_id` from each application's company, so the demo rows stay consistent.

- [ ] **Step 5: Add environment defaults**

In `.env` and `.env.example`:
```dotenv
APP_NAME="Job Tracker"
DEMO_NAME="Demo User"
DEMO_EMAIL=demo@example.com
DEMO_PASSWORD=password
```

Note in `.env.example` that `DEMO_PASSWORD` must be changed before production and that these values only seed local demo data.

- [ ] **Step 6: Migrate and seed**

Run:
```powershell
php artisan migrate
php artisan db:seed --class=DatabaseSeeder
```

Expected: the migration backfills existing companies and applications to the oldest user; `demo@example.com` exists; every company and application has a non-null `user_id`.

- [ ] **Step 7: Verify the data**

```powershell
php artisan tinker --execute 'dump(App\Models\Company::whereNull("user_id")->count(), App\Models\JobApplication::whereNull("user_id")->count());'
```

Expected: `0` and `0`.

- [ ] **Step 8: Commit**

```powershell
git add database/migrations database/factories database/seeders app/Models .env.example
git commit -m "feat: add per-user ownership to companies and applications"
```

Do not commit `.env`.

---

## Task 6: Protect and Scope the Tracker Routes

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Http/Controllers/JobApplicationController.php`
- Modify: `app/Http/Controllers/CompanyController.php`
- Modify: `app/Http/Requests/StoreJobApplication.php`
- Create: `app/Http/Requests/UpdateJobApplication.php`
- Create: `app/Http/Requests/StoreCompany.php`
- Modify: `app/Models/User.php`

**Interfaces:**
- Produces: route `home` (`/`), tracker routes behind `auth`, scoped route bindings that 404 on foreign records, per-user validation, `User` implements `MustVerifyEmail`.

- [ ] **Step 1: Name the home route and protect the tracker**

`routes/web.php`:
```php
<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('applications.index');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::resource('applications', JobApplicationController::class);
    Route::resource('companies', CompanyController::class)->only(['index', 'create', 'store', 'show']);
});
```

**Why not `verified`:** the mailer is `log`, so gating would block every new account until it reads `storage/logs/laravel.log`. To enforce verification later, change the group to `['auth', 'verified']` and update the ownership/auth tests that expect an unverified user to reach the tracker.

- [ ] **Step 2: Scope route model binding to the authenticated user**

In `AppServiceProvider::boot()`:
```php
use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Route;

Route::bind('company', function (string $value): Company {
    $user = auth()->user();

    abort_if($user === null, 404);

    return $user->companies()->findOrFail($value);
});

Route::bind('application', function (string $value): JobApplication {
    $user = auth()->user();

    abort_if($user === null, 404);

    return $user->jobApplications()->findOrFail($value);
});
```

This is the single control that turns "another user's record" into a 404 for `show`, `edit`, `update`, and `destroy` without touching each controller action.

- [ ] **Step 3: Scope the application controller**

`index()`:
```php
$applications = $request->user()
    ->jobApplications()
    ->with('company')
    ->latest()
    ->get();

return view('applications.index', ['applications' => $applications]);
```

`create()` and `edit()` use the user's companies:
```php
'companies' => $request->user()->companies()->orderBy('name')->get(),
```

`store()`:
```php
$application = $request->user()->jobApplications()->create($request->validated());
```

`update(UpdateJobApplication $request, JobApplication $application)`:
```php
$application->update($request->validated());
```

`destroy()` and `show()` stay as they are; the route binding already guarantees ownership.

- [ ] **Step 4: Scope the company controller**

`index()`:
```php
$companies = $request->user()
    ->companies()
    ->withCount('jobApplications')
    ->orderBy('name')
    ->get();
```

`store(StoreCompany $request)`:
```php
$company = $request->user()->companies()->create($request->validated());
```

`show()` stays as it is; the route binding guarantees ownership.

- [ ] **Step 5: Scope the application validation**

`StoreJobApplication::rules()` replaces the `company_id` rule with:
```php
'company_id' => [
    'required',
    Rule::exists('companies', 'id')->where('user_id', $this->user()->id),
],
```

Everything else in the request stays the same (messages, attributes).

Create `UpdateJobApplication`:
```powershell
php artisan make:request UpdateJobApplication --no-interaction
```
```php
<?php

namespace App\Http\Requests;

class UpdateJobApplication extends StoreJobApplication
{
}
```

- [ ] **Step 6: Create the company request**

```powershell
php artisan make:request StoreCompany --no-interaction
```
```php
<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompany extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('companies', 'name')->where('user_id', $this->user()->id),
            ],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 7: Make `User` verifiable**

```php
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    // ...
}
```

Do not add 2FA/passkey traits yet; those are Phase 3.

- [ ] **Step 8: Clear caches and verify the wiring**

```powershell
php artisan optimize:clear
php artisan route:list --except-vendor -v
```

Expected: `applications.*` and `companies.*` show the `auth` middleware; `route('home')` resolves to `/`; `register` is present.

- [ ] **Step 9: Commit**

```powershell
git add routes/web.php app/Providers/AppServiceProvider.php app/Http/Controllers app/Http/Requests app/Models/User.php
git commit -m "feat: protect tracker routes and scope data to the authenticated user"
```

---

## Task 7: Auth and Ownership Tests

**Files:**
- Modify: `tests/TestCase.php`
- Create: `tests/Feature/Auth/AuthenticationTest.php`
- Create: `tests/Feature/Auth/RegistrationTest.php`
- Create: `tests/Feature/Auth/PasswordResetTest.php`
- Create: `tests/Feature/Auth/EmailVerificationTest.php`
- Create: `tests/Feature/Auth/PasswordConfirmationTest.php`
- Create: `tests/Feature/OwnershipTest.php`
- Create: `tests/Feature/CompanyTest.php`

**Interfaces:**
- Consumes: `App\Models\User`, `Company`, `JobApplication`, Fortify route names, `RefreshDatabase`.
- Produces: regression coverage for login, registration, logout, guest redirects, reset, verification, confirmation, and cross-user isolation.

- [ ] **Step 1: Add the Fortify feature helper**

In `tests/TestCase.php`:
```php
protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
{
    if (! \Laravel\Fortify\Features::enabled($feature)) {
        $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
    }
}
```

- [ ] **Step 2: Write the authentication tests**

Use Pest with `uses(RefreshDatabase::class);`:
```php
it('renders the login screen', function () {
    $this->get(route('login'))->assertOk();
});

it('authenticates a user and redirects to applications', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors()
      ->assertRedirect(route('applications.index', absolute: false));

    $this->assertAuthenticated();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

it('logs out and redirects home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home', absolute: false));

    $this->assertGuest();
});

it('redirects guests from applications to login', function () {
    $this->get(route('applications.index'))->assertRedirect(route('login'));
});

it('redirects guests from companies to login', function () {
    $this->get(route('companies.index'))->assertRedirect(route('login'));
});
```

- [ ] **Step 3: Write the registration tests**

```php
it('renders the registration screen', function () {
    $this->get(route('register'))->assertOk();
});

it('registers a new user and redirects to applications', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors()
      ->assertRedirect(route('applications.index', absolute: false));

    $this->assertAuthenticated();
    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Another User',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrorsIn('email');

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});
```

- [ ] **Step 4: Write the password reset tests**

Cover:
- `GET route('password.request')` returns 200.
- Posting an email sends `ResetPassword` (`Notification::fake()`).
- Posting a valid token updates the password and redirects to login.
- Posting an invalid token returns a validation error and leaves the password unchanged.

- [ ] **Step 5: Write the email verification tests**

Cover:
- `GET route('verification.notice')` returns 200 for an unverified user.
- An unverified user **can** reach `route('applications.index')` (documented behavior; the tracker is not gated on `verified`).
- A valid signed verification URL fires `Verified` and redirects to `/applications?verified=1`.
- An invalid hash does not verify the email.

- [ ] **Step 6: Write the password confirmation test**

```php
it('renders the confirm password screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('password.confirm'))->assertOk();
});
```

- [ ] **Step 7: Write the ownership isolation tests**

`tests/Feature/OwnershipTest.php`:
```php
<?php

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hides other users applications from the index', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    $alicesApplication = JobApplication::factory()->forUser($alice)->create(['role_title' => 'Alice Role']);
    $bobsApplication = JobApplication::factory()->forUser($bob)->create(['role_title' => 'Bob Role']);

    $this->actingAs($alice)
        ->get(route('applications.index'))
        ->assertOk()
        ->assertSee('Alice Role')
        ->assertDontSee('Bob Role');
});

it('hides other users companies from the index', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Company::factory()->for($alice)->create(['name' => 'Alice Corp']);
    Company::factory()->for($bob)->create(['name' => 'Bob Corp']);

    $this->actingAs($alice)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Alice Corp')
        ->assertDontSee('Bob Corp');
});

it('returns 404 for another users application', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $bobsApplication = JobApplication::factory()->forUser($bob)->create();

    $this->actingAs($alice)->get(route('applications.show', $bobsApplication))->assertNotFound();
    $this->actingAs($alice)->get(route('applications.edit', $bobsApplication))->assertNotFound();
    $this->actingAs($alice)->put(route('applications.update', $bobsApplication), [])->assertNotFound();
    $this->actingAs($alice)->delete(route('applications.destroy', $bobsApplication))->assertNotFound();

    expect($bobsApplication->fresh())->not->toBeNull();
});

it('returns 404 for another users company', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $bobsCompany = Company::factory()->for($bob)->create();

    $this->actingAs($alice)->get(route('companies.show', $bobsCompany))->assertNotFound();
});

it('rejects another users company id when storing an application', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $bobsCompany = Company::factory()->for($bob)->create();

    $this->actingAs($alice)
        ->post(route('applications.store'), [
            'company_id' => $bobsCompany->id,
            'role_title' => 'Sneaky Role',
            'status' => 'wishlist',
        ])
        ->assertSessionHasErrors('company_id');

    expect(JobApplication::where('role_title', 'Sneaky Role')->exists())->toBeFalse();
});

it('allows the same company name for different users', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Company::factory()->for($alice)->create(['name' => 'Acme']);

    $this->actingAs($bob)
        ->post(route('companies.store'), ['name' => 'Acme'])
        ->assertSessionHasNoErrors();

    expect(Company::where('name', 'Acme')->count())->toBe(2);
});

it('rejects a duplicate company name for the same user', function () {
    $alice = User::factory()->create();

    Company::factory()->for($alice)->create(['name' => 'Acme']);

    $this->actingAs($alice)
        ->post(route('companies.store'), ['name' => 'Acme'])
        ->assertSessionHasErrors('name');
});

it('never writes an orphan row through the app', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $this->actingAs($user)->post(route('applications.store'), [
        'company_id' => $company->id,
        'role_title' => 'Backend Developer',
        'status' => 'applied',
    ])->assertSessionHasNoErrors();

    expect(Company::whereNull('user_id')->count())->toBe(0)
        ->and(JobApplication::whereNull('user_id')->count())->toBe(0);
});
```

- [ ] **Step 8: Write the company tests**

Cover:
- authenticated index renders and shows the user's company,
- authenticated show renders,
- store creates a company owned by the user,
- guest is redirected to login.

- [ ] **Step 9: Run the tests**

```powershell
php artisan test --compact tests/Feature/Auth tests/Feature/OwnershipTest.php tests/Feature/CompanyTest.php
```

Expected: all pass. If a test fails, fix the implementation before continuing.

- [ ] **Step 10: Commit**

```powershell
git add tests/TestCase.php tests/Feature/Auth tests/Feature/OwnershipTest.php tests/Feature/CompanyTest.php
git commit -m "test: cover authentication and per-user ownership"
```

---

## Task 8: Phase 1 Verification

**Files:** none new.

- [ ] **Step 1: Run the full suite**

```powershell
php artisan test --compact
```

- [ ] **Step 2: Format PHP**

```powershell
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Build assets**

```powershell
npm run build
```

- [ ] **Step 4: Manual auth and isolation QA**

1. `php artisan optimize:clear`
2. Start the app (`composer run dev` or Herd).
3. Visit `/` as a guest → redirected to `/login`.
4. Register `user-a@example.com` → lands on `/applications` with an empty state.
5. Add a company and an application.
6. Log out, register `user-b@example.com` → `/applications` is empty; `/companies` is empty.
7. As user B, open user A's application URL → 404.
8. Log back in as user A → data is still there.
9. Visit `/register` while logged in → still reachable (Fortify redirects authenticated users home).
10. Request a reset link → inspect `storage/logs/laravel.log` (mailer is `log`).

- [ ] **Step 5: Commit any fixes**

```powershell
git add -A
git commit -m "chore: verify auth foundation and ownership"
```

---

# Phase 2 — Restyle Tracker Pages

> Phase 2 changes presentation only. Every controller query is already user-scoped from Task 6; do not "simplify" one back to `Model::query()`.

## Task 9: Build the App Shell

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Create: `resources/views/components/user-menu.blade.php`
- Create: `resources/views/components/status-badge.blade.php`
- Create: `resources/views/components/empty-state.blade.php`

**Interfaces:**
- Produces: `layouts.app` with a Flux sidebar/header, `x-user-menu`, `x-status-badge`, `x-empty-state`, and an email-verification callout.
- Consumes: `auth()->user()`, `route('logout')`, `route('applications.index')`, `route('companies.index')`.

- [ ] **Step 1: Replace the app layout**

Use the starter kit's sidebar layout as the visual source, but keep `@yield('content')`:
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @php($pageTitle = trim($__env->yieldContent('title')))
    @include('partials.head', ['title' => $pageTitle])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('applications.index') }}" />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Tracker')" class="grid">
                <flux:sidebar.item icon="briefcase" :href="route('applications.index')" :current="request()->routeIs('applications.*')">
                    {{ __('Applications') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-office" :href="route('companies.index')" :current="request()->routeIs('companies.*')">
                    {{ __('Companies') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />
        <x-user-menu :name="auth()->user()->name" class="hidden lg:block" />
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <x-user-menu :name="auth()->user()->name" />
    </flux:header>

    <flux:main>
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <flux:callout icon="envelope" class="mb-6">
                <flux:callout.heading>{{ __('Verify your email address') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Your account works, but verifying your email keeps account recovery reliable.') }}
                </flux:callout.text>
                <x-slot name="actions">
                    <flux:button :href="route('verification.notice')" size="sm" variant="filled">
                        {{ __('Verify now') }}
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        @yield('content')
    </flux:main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
```

If a Heroicon name is unavailable in Flux free, replace it with an inline SVG or another available icon; do not install Flux Pro.

- [ ] **Step 2: Create the user menu**

Copy the structure of `desktop-user-menu.blade.php`, remove the team and settings links, and keep:
- avatar + name/email,
- separator,
- `POST route('logout')` form with a `flux:menu.item` submit button.

Use `flux:avatar :name="$name"`; no `initials()` method is required.

- [ ] **Step 3: Create the status badge**

```blade
@props(['status'])

@php
    $color = match ($status) {
        \App\Enums\ApplicationStatus::Wishlist => 'zinc',
        \App\Enums\ApplicationStatus::Applied => 'blue',
        \App\Enums\ApplicationStatus::Interviewing => 'amber',
        \App\Enums\ApplicationStatus::Offer => 'green',
        \App\Enums\ApplicationStatus::Rejected => 'red',
    };
@endphp

<flux:badge :color="$color" size="sm">{{ $status->label() }}</flux:badge>
```

- [ ] **Step 4: Create the empty state**

A simple centered block with an icon, heading, description, and optional action slot. Use it for empty application/company lists.

- [ ] **Step 5: Build and verify the shell**

Run:
```powershell
npm run build
php artisan view:clear
```

Visit `/applications` while logged in. Expected: dark sidebar, Flux nav items, no `simple.css`, user menu opens, logout works, verification callout appears for an unverified account.

- [ ] **Step 6: Commit**

```powershell
git add resources/views/layouts/app.blade.php resources/views/components/user-menu.blade.php resources/views/components/status-badge.blade.php resources/views/components/empty-state.blade.php
git commit -m "feat: add flux app shell and shared tracker components"
```

---

## Task 10: Restyle the Applications Index and Detail

**Files:**
- Modify: `resources/views/applications/index.blade.php`
- Modify: `resources/views/applications/show.blade.php`
- Modify: `app/Http/Controllers/JobApplicationController.php` (only if adding summary counts)

**Interfaces:**
- Consumes: `$applications` (already the current user's), `$application`, `x-status-badge`, `x-empty-state`.
- Produces: restyled pages that still use the same route names and controller data.

- [ ] **Step 1: Restyle the index**

Include:
- `flux:heading` + `flux:subheading`,
- a primary "Add application" `flux:button` linked to `route('applications.create')`,
- three summary cards: total, interviewing, offers (computed from `$applications`),
- a bordered table card with role, company, status badge, and applied date,
- `x-empty-state` when the collection is empty.

Keep the table semantic (`<table>`, `<thead>`, `<tbody>`) for accessibility and tests.

- [ ] **Step 2: Restyle the detail page**

Include:
- a "Back to applications" link,
- a bordered card with role title, company link, status badge,
- a definition list for salary, applied date, and notes,
- primary Edit button and danger Delete button,
- a delete form with `@csrf`, `@method('DELETE')`, and an `onsubmit="return confirm('Delete this application?')"` guard.

- [ ] **Step 3: Keep the summary user-scoped**

If you move the counts into the controller, start from the user's relation:
```php
$applications = $request->user()->jobApplications()->with('company')->latest()->get();

$summary = [
    'total' => $applications->count(),
    'interviewing' => $applications->where('status', ApplicationStatus::Interviewing)->count(),
    'offers' => $applications->where('status', ApplicationStatus::Offer)->count(),
];
```

Never reintroduce `JobApplication::count()` or any unscoped aggregate; it would leak another user's totals into the summary cards.

- [ ] **Step 4: Update the application tests**

In `tests/Feature/JobApplicationTest.php`:
- create a user and `actingAs` them,
- use `JobApplication::factory()->forUser($user)` so the application is visible to them,
- add a guest redirect test,
- add a store test with valid data,
- add a validation test with a missing `company_id` or `role_title`.

- [ ] **Step 5: Run the tests**

```powershell
php artisan test --compact tests/Feature/JobApplicationTest.php
```

- [ ] **Step 6: Commit**

```powershell
git add resources/views/applications/index.blade.php resources/views/applications/show.blade.php app/Http/Controllers/JobApplicationController.php tests/Feature/JobApplicationTest.php
git commit -m "feat: restyle application index and detail pages"
```

---

## Task 11: Restyle the Application Forms

**Files:**
- Modify: `resources/views/applications/_form.blade.php`
- Modify: `resources/views/applications/create.blade.php`
- Modify: `resources/views/applications/edit.blade.php`

**Interfaces:**
- Consumes: `$application`, `$companies` (already the current user's), `$statuses`, `old()`.
- Produces: the same field names and validation contract, rendered with Flux components.

- [ ] **Step 1: Convert the shared form fields**

Replace the raw HTML fields with:
- `flux:select name="company_id" label="Company"` with the placeholder option and `@selected(...)`,
- `flux:input name="role_title"`,
- `flux:select name="status"` with the enum options,
- a two-column grid for `salary_min` and `salary_max` (`type="number"`, `min="0"`),
- `flux:input name="applied_on" type="date"`,
- `flux:textarea name="notes" rows="4"`.

Flux form components display validation errors from the `$errors` bag automatically when `name` matches. Do not add duplicate `@error` blocks unless a field does not show errors.

- [ ] **Step 2: Handle the no-companies case**

When `$companies->isEmpty()`, the create page cannot submit a valid form. Render a callout instead of the form:
```blade
@if ($companies->isEmpty())
    <flux:callout icon="building-office">
        <flux:callout.heading>{{ __('Add a company first') }}</flux:callout.heading>
        <flux:callout.text>{{ __('Every application belongs to one of your companies.') }}</flux:callout.text>
        <x-slot name="actions">
            <flux:button :href="route('companies.create')" size="sm">{{ __('Add company') }}</flux:button>
        </x-slot>
    </flux:callout>
@else
    @include('applications._form')
@endif
```

Keep the same guard on the edit page only if a user can somehow have zero companies while owning an application (they cannot today, so create-only is fine).

- [ ] **Step 3: Wrap create/edit in cards**

Each page should have:
- a `flux:heading` + `flux:subheading`,
- a bordered card containing the form or the no-companies callout,
- a primary submit button,
- a secondary Cancel/Back link.

Keep the existing form actions (`applications.store`, `applications.update`) and method spoofing.

- [ ] **Step 4: Verify create/edit/update**

Run the app and:
1. Create an application with valid data.
2. Submit with a missing company and confirm the inline error.
3. Submit with another user's `company_id` (via a request tool or a temporary test) and confirm a validation error, not a write.
4. Edit the application and save.
5. Delete it and confirm the redirect + flash message.

- [ ] **Step 5: Commit**

```powershell
git add resources/views/applications/_form.blade.php resources/views/applications/create.blade.php resources/views/applications/edit.blade.php
git commit -m "feat: restyle application forms with flux fields"
```

---

## Task 12: Restyle the Company Pages

**Files:**
- Modify: `resources/views/companies/index.blade.php`
- Modify: `resources/views/companies/show.blade.php`
- Modify: `resources/views/companies/create.blade.php`

**Interfaces:**
- Consumes: `$companies` (current user's, with counts), `$company`, `x-status-badge`, `x-empty-state`.
- Produces: restyled company pages with the same routes and per-user validation.

- [ ] **Step 1: Restyle the company index**

Include:
- heading + "Add a company" button,
- bordered table/card with name, location, application count,
- `x-empty-state` when empty.

- [ ] **Step 2: Restyle the company detail**

Include:
- company name, location, website link (external, `rel="noopener"`),
- the company's applications with role title, status badge, and link,
- "Add an application" button,
- empty state when the company has no applications.

- [ ] **Step 3: Restyle the company form**

Use `flux:input` for name, website, and location, inside a bordered card, with a primary submit and Cancel link. Keep the `companies.store` action; validation now lives in `StoreCompany`.

- [ ] **Step 4: Verify per-user uniqueness in the UI**

1. As user A, create "Acme".
2. Create "Acme" again → inline error on `name`.
3. Log in as user B, create "Acme" → succeeds.

- [ ] **Step 5: Run the tests**

```powershell
php artisan test --compact tests/Feature/CompanyTest.php tests/Feature/OwnershipTest.php
```

- [ ] **Step 6: Commit**

```powershell
git add resources/views/companies
git commit -m "feat: restyle company pages"
```

---

## Task 13: Phase 2 Verification

**Files:** none new.

- [ ] **Step 1: Run the full suite**

```powershell
php artisan test --compact
```

- [ ] **Step 2: Format PHP**

```powershell
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Build assets**

```powershell
npm run build
```

- [ ] **Step 4: Manual QA**

1. Log in as user A.
2. Visit Applications: summary cards, table, badges, empty state.
3. Create, edit, delete an application.
4. Visit Companies: list, create, detail, application links.
5. Confirm the summary cards count only A's applications while B has data.
6. Resize to mobile width: sidebar collapses, header shows the menu toggle, user menu works.
7. Confirm no page still loads `cdn.simplecss.org`.

- [ ] **Step 5: Commit any fixes**

```powershell
git add -A
git commit -m "chore: verify tracker restyle"
```

---

# Phase 3 — Optional: 2FA, Passkeys, and Security Settings

> Phase 3 is optional and can be deferred. Do not start it until Phases 1–2 are green and the user asks for it. The core auth flow works without it. Everything here is per-user already: 2FA and passkeys live on the `users` row, so no extra ownership work is needed.

## Task 14: Enable Two-Factor Authentication

**Files:**
- Modify: `config/fortify.php`
- Modify: `app/Models/User.php`
- Modify: `app/Providers/FortifyServiceProvider.php`
- Modify: `database/factories/UserFactory.php`
- Create: `resources/views/pages/auth/two-factor-challenge.blade.php`
- Create: `tests/Feature/Auth/TwoFactorChallengeTest.php`

**Interfaces:**
- Consumes: Fortify's 2FA routes (`two-factor.login`, `two-factor.login.store`, `two-factor.enable`, `two-factor.confirm`, `two-factor.disable`, `two-factor.qr-code`, `two-factor.secret-key`, `two-factor.recovery-codes`).
- Produces: 2FA challenge page, `withTwoFactor()` factory state, `TwoFactorAuthenticatable` on `User`.

- [ ] **Step 1: Add the 2FA feature**

In `config/fortify.php`:
```php
Features::twoFactorAuthentication([
    'confirm' => true,
    'confirmPassword' => true,
]),
```

- [ ] **Step 2: Update the User model**

```php
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    #[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
    // ...
}
```

Add `'two_factor_confirmed_at' => 'datetime'` to `casts()`.

- [ ] **Step 3: Bind the challenge view**

In `FortifyServiceProvider::configureViews()`:
```php
Fortify::twoFactorChallengeView(fn () => view('pages.auth.two-factor-challenge'));
```

- [ ] **Step 4: Add the challenge page**

Copy `job-tracker-auth/resources/views/pages/auth/two-factor-challenge.blade.php` and convert the wrapper to `@extends('layouts.auth')` + `@section('content')`. Keep the Alpine toggle between TOTP and recovery code, `flux:otp`, and the submit button.

- [ ] **Step 5: Add the factory state**

```php
public function withTwoFactor(): static
{
    return $this->state(fn (array $attributes) => [
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at' => now(),
    ]);
}
```

- [ ] **Step 6: Add the tests**

Cover:
- unauthenticated `GET route('two-factor.login')` redirects to login,
- a user with 2FA is redirected to the challenge after login,
- the challenge page renders for a pending 2FA login.

- [ ] **Step 7: Migrate and test**

```powershell
php artisan migrate
php artisan test --compact tests/Feature/Auth/TwoFactorChallengeTest.php
```

- [ ] **Step 8: Commit**

```powershell
git add config/fortify.php app/Models/User.php app/Providers/FortifyServiceProvider.php database/factories/UserFactory.php resources/views/pages/auth/two-factor-challenge.blade.php tests/Feature/Auth/TwoFactorChallengeTest.php
git commit -m "feat: add two-factor authentication challenge"
```

---

## Task 15: Add Settings → Security

**Files:**
- Create: `routes/settings.php`
- Modify: `routes/web.php` (require the settings file)
- Create: `resources/views/pages/settings/⚡security.blade.php`
- Create: `resources/views/pages/settings/⚡two-factor-setup-modal.blade.php`
- Create: `resources/views/pages/settings/two-factor/⚡recovery-codes.blade.php`
- Create: `resources/views/pages/settings/layout.blade.php`
- Create: `resources/views/partials/settings-heading.blade.php`
- Modify: `resources/views/components/user-menu.blade.php` (add Settings link)
- Modify: `app/Providers/FortifyServiceProvider.php` (persistent password-confirm middleware)
- Create: `tests/Feature/Settings/SecurityTest.php`

**Interfaces:**
- Produces: route `security.edit` (`/settings/security`), Livewire component `pages::settings.security`, password change, 2FA enable/disable, recovery codes.

- [ ] **Step 1: Add the settings routes**

Create `routes/settings.php`:
```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/security');
});

Route::middleware(['auth', 'password.confirm'])->group(function () {
    Route::livewire('settings/security', 'pages::settings.security')->name('security.edit');
});
```

Require it at the bottom of `routes/web.php`:
```php
require __DIR__.'/settings.php';
```

- [ ] **Step 2: Copy the settings views**

Copy from `job-tracker-auth/resources/views/`:
- `pages/settings/⚡security.blade.php`
- `pages/settings/⚡two-factor-setup-modal.blade.php`
- `pages/settings/two-factor/⚡recovery-codes.blade.php`
- `pages/settings/layout.blade.php`
- `partials/settings-heading.blade.php`

Remove the Teams nav item from `pages/settings/layout.blade.php`. Keep Profile/Security only if those routes exist; otherwise keep Security only.

- [ ] **Step 3: Fix the trait namespace**

The copied `⚡security.blade.php` uses `App\Concerns\PasswordValidationRules`, which exists from Task 2. Confirm the import matches.

- [ ] **Step 4: Add persistent password confirmation**

In `FortifyServiceProvider::boot()`:
```php
Livewire::addPersistentMiddleware([
    RequirePassword::class,
]);
```

Use `Illuminate\Auth\Middleware\RequirePassword` and `Livewire\Livewire`.

- [ ] **Step 5: Add the Settings link**

In `x-user-menu`, add:
```blade
<flux:menu.item :href="route('security.edit')" icon="cog" wire:navigate>
    {{ __('Settings') }}
</flux:menu.item>
```

- [ ] **Step 6: Add tests**

Cover:
- guest is redirected from `/settings/security` to login,
- an authenticated user without recent password confirmation is redirected to `password.confirm`,
- a confirmed user can render the security page,
- the password update action changes the password (use `Livewire::test()` on the SFC).

- [ ] **Step 7: Run the tests**

```powershell
php artisan test --compact tests/Feature/Settings/SecurityTest.php
```

- [ ] **Step 8: Commit**

```powershell
git add routes/settings.php routes/web.php resources/views/pages/settings resources/views/partials/settings-heading.blade.php resources/views/components/user-menu.blade.php app/Providers/FortifyServiceProvider.php tests/Feature/Settings/SecurityTest.php
git commit -m "feat: add security settings for password and two-factor auth"
```

---

## Task 16: Add Passkeys

**Files:**
- Modify: `package.json` / `package-lock.json`
- Modify: `vite.config.js`
- Create: `resources/js/passkeys.js`
- Create: `resources/views/components/passkey-verify.blade.php`
- Create: `resources/views/components/passkey-registration.blade.php`
- Modify: `resources/views/pages/auth/login.blade.php`
- Modify: `resources/views/pages/auth/confirm-password.blade.php`
- Modify: `config/fortify.php`
- Modify: `app/Models/User.php`
- Modify: `app/Providers/FortifyServiceProvider.php`

**Interfaces:**
- Consumes: `@laravel/passkeys`, Fortify passkey routes (`passkey.login-options`, `passkey.login`, `passkey.confirm-options`, `passkey.confirm`, `passkey.registration-options`, `passkey.store`, `passkey.destroy`).
- Produces: passkey login button, passkey registration UI, `PasskeyUser` + `PasskeyAuthenticatable` on `User`.

- [ ] **Step 1: Install the browser package**

```powershell
npm install @laravel/passkeys
```

- [ ] **Step 2: Add the JS entry**

Create `resources/js/passkeys.js`:
```js
import { Passkeys } from '@laravel/passkeys';

window.Passkeys = Passkeys;
window.dispatchEvent(new CustomEvent('passkeys:ready'));
```

Add `'resources/js/passkeys.js'` to the `laravel({ input: [...] })` array in `vite.config.js`.

- [ ] **Step 3: Enable passkeys in Fortify**

In `config/fortify.php`:
```php
Features::passkeys([
    'confirmPassword' => true,
]),
```

Keep the `passkeys` config block. Ensure `APP_URL` matches the host used in the browser (Herd `.test` URL or `http://localhost:8000`).

- [ ] **Step 4: Update the User model**

```php
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;
}
```

- [ ] **Step 5: Add the passkey components**

Copy `passkey-verify.blade.php` and `passkey-registration.blade.php` from the starter kit. In `passkey-verify`, change the fallback redirect from `/dashboard` to `/applications`:
```js
Livewire.navigate(response.redirect || '/applications');
```

- [ ] **Step 6: Add the login/confirm buttons**

In `login.blade.php`, insert `<x-passkey-verify />` above the email form. In `confirm-password.blade.php`, insert:
```blade
<x-passkey-verify
    options-route="passkey.confirm-options"
    submit-route="passkey.confirm"
    :label="__('Confirm with passkey')"
    :loading-label="__('Confirming...')"
    :separator="__('Or confirm with password')"
/>
```

- [ ] **Step 7: Add the passkey section to security settings**

The copied `⚡security.blade.php` already renders the passkey list and `<x-passkey-registration />` when `Features::canManagePasskeys()` is true. Confirm the `DeletePasskey` import resolves to `Laravel\Passkeys\Actions\DeletePasskey`.

- [ ] **Step 8: Build and test**

```powershell
npm run build
php artisan migrate
php artisan test --compact
```

Manual check:
1. Log in, open Settings → Security, confirm password.
2. Add a passkey; name it.
3. Log out, use "Sign in with a passkey".
4. Confirm the passkey appears in the list and can be removed.

- [ ] **Step 9: Commit**

```powershell
git add package.json package-lock.json vite.config.js resources/js/passkeys.js resources/views/components/passkey-verify.blade.php resources/views/components/passkey-registration.blade.php resources/views/pages/auth/login.blade.php resources/views/pages/auth/confirm-password.blade.php config/fortify.php app/Models/User.php app/Providers/FortifyServiceProvider.php
git commit -m "feat: add passkey login and registration"
```

---

## Task 17: Phase 3 Verification

- [ ] **Step 1: Full suite**

```powershell
php artisan test --compact
```

- [ ] **Step 2: Format**

```powershell
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 3: Build**

```powershell
npm run build
```

- [ ] **Step 4: Manual 2FA/passkey QA**

1. Enable 2FA, scan the QR, confirm with a TOTP code.
2. Log out and log in with a TOTP code.
3. Log in with a recovery code.
4. Disable 2FA.
5. Add a passkey, log out, log in with the passkey.
6. Remove the passkey.

- [ ] **Step 5: Commit any fixes**

```powershell
git add -A
git commit -m "chore: verify two-factor and passkey auth"
```

---

# Verification Checklist

Run before declaring the work complete:

- [ ] `git status --short` shows only intended changes.
- [ ] `php artisan test --compact` passes.
- [ ] `vendor/bin/pint --dirty --format agent` passes.
- [ ] `npm run build` succeeds.
- [ ] `php artisan route:list --except-vendor` shows the auth routes including `register`.
- [ ] A guest hitting `/applications` or `/companies` is redirected to `/login`.
- [ ] Registration creates an account, logs the user in, and lands on `/applications`.
- [ ] A logged-out session cannot reach the tracker.
- [ ] User B sees none of user A's applications or companies in any list.
- [ ] User B gets a **404** on user A's application/company URLs (show, edit, update, delete).
- [ ] Submitting another user's `company_id` fails validation and writes nothing.
- [ ] The same company name is allowed for two different users and rejected twice for the same user.
- [ ] No company or application row has `user_id = null` after normal use.
- [ ] The applications and companies pages use Flux components and no `simple.css`.
- [ ] Mobile layout works at 375 px width.
- [ ] Password reset email appears in `storage/logs/laravel.log` with the `log` mailer.
- [ ] Phase 3 only: 2FA and passkey flows work end-to-end.

---

# Resume Notes

**Current state:** This plan was written after inspecting both projects, then revised on 2026-11-28 from a single-user design to per-user ownership. No code, dependencies, migrations, or assets have been changed.

**Baseline:** `main` at `5932b91`, plus this untracked plan file. Start each session with:
```powershell
git status --short
git log -1 --oneline
```

**Next step:** Phase 1, Task 1 (`composer require laravel/fortify livewire/livewire livewire/flux`).

**Key decisions to preserve:**
- **Multi-user with per-user data.** `user_id` on `companies` and `job_applications`; every query and write scoped to `auth()->user()`.
- **Registration is enabled.** `Features::registration()` is in `config/fortify.php`; `/register` must return 200.
- **Foreign records 404**, enforced by scoped route bindings in `AppServiceProvider`, not by policies.
- **`user_id` is never accepted from request input.** Controllers use relationships or `auth()->id()`.
- **Company name uniqueness is per user**, enforced in `StoreCompany`.
- **Email verification is available but not required** (`auth`, not `auth` + `verified`) because the mailer is `log`.
- Flux free only; no Flux Pro, Blaze, Chisel, or Vite Plus.
- Dark theme and centered auth layout copied from the starter kit.
- 2FA/passkeys are optional Phase 3.

**Known pitfalls:**
- Re-running `php artisan fortify:install` overwrites `config/fortify.php` and `app/Providers/FortifyServiceProvider.php`. Re-apply the edits if it happens.
- If login redirects to `/home`, `config/fortify.php` still has the default `'home' => '/home'`.
- If a logged-in user gets a 404 on their own record, the route binding is querying the wrong relationship or `user_id` is null on that row. Check `php artisan tinker --execute 'dump(App\Models\Company::whereNull("user_id")->count());'`.
- If another user's data appears in a list, a controller query was changed back to `Model::query()`; it must start from `$request->user()->...`.
- If `Rule::exists(...)->where('user_id', ...)` always fails, `$this->user()` is null because the request ran outside the `auth` middleware.
- If auth pages render unstyled, `resources/css/app.css` is missing the Flux import or `npm run build` has not run.
- If `@fluxScripts` throws, Livewire is missing or the directive is outside `<body>`.
- If the ownership migration fails on `change()`, remove the `tightenToNonNullable()` call; the column stays nullable and the app still works.
- On Windows, use `vendor\bin\pint.bat` if `vendor/bin/pint` is not found.
- If Vite reports a missing manifest, run `npm run build` or `npm run dev`.
- If routes/config appear stale, run `php artisan optimize:clear`.

**How to enforce email verification later:**
1. Change the tracker group in `routes/web.php` to `Route::middleware(['auth', 'verified'])`.
2. Update the verification test that currently asserts an unverified user can reach `/applications`.
3. Configure a real mailer (`MAIL_MAILER=smtp` or a transactional provider) so users can actually receive the link.
4. Remove the verification callout from `layouts.app` once gating is on.

**How to add sharing or roles later (out of scope):**
1. Keep `user_id` as the owner column and add a pivot (for example `company_user`) rather than overloading `user_id`.
2. Add policies then; they become the single source of truth and the scoped route bindings can be removed.
3. Add `Gate::before` only if an admin role is introduced.
