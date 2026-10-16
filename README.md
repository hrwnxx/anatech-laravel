# Anatech — Gadget Catalog & Review System (Laravel + React)

Anatech is a gadget shop catalog. Customers browse phones, tablets, laptops,
smartwatches and accessories. They can only review a gadget after staff have
recorded an in-store purchase for them, and every review is moderated by an
admin before it goes public.

This is the Laravel + React version of the original native-PHP project.

- **Backend** (`backend/`): Laravel 13 (PHP 8.3+), a JSON API under `/api`
- **Frontend** (`frontend/`): React + Vite, a separate app that calls the API
- **Database:** MySQL / MariaDB (XAMPP)
- **Login:** email + password, or Google (Laravel Socialite)

---

## 1. Setup on a new computer

The project is two separate apps in one repository: `backend/` (Laravel) and
`frontend/` (React). Start **MySQL** in the XAMPP Control Panel, then set up each one.

**Backend**

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

Create the database (or use phpMyAdmin → New → `anatech_laravel`):

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE anatech_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Create the tables, load the data, and make product photos reachable (still in `backend/`):

```bash
php artisan migrate --seed
php artisan storage:link
```

**Frontend**

```bash
cd frontend
npm install
```

**Run both** (two terminals):

```bash
cd backend  && php artisan serve    # API at http://127.0.0.1:8000
cd frontend && npm run dev          # app at http://localhost:5173
```

Open **http://localhost:5173**. Always use `localhost:5173`, not `127.0.0.1:5173`:
the login session and Google login are set up for that address.

The React dev server forwards `/api` and `/auth` to Laravel (see
`frontend/vite.config.js`), so the browser only ever talks to one address and
no CORS setup is needed. If you run Laravel on another port, change `laravelServer`
in that file and `APP_URL` in `backend/.env`.

To reset everything back to the original data at any time:

```bash
cd backend && php artisan migrate:fresh --seed
```

## 2. Demo accounts

Every seeded account uses the same demo password: **`password`**
(set in `backend/database/seeders/UserSeeder.php`).

| Role     | Email                   | Log in at                                   |
|----------|-------------------------|---------------------------------------------|
| Admin    | `admin@gadgetshop.com`  | `http://localhost:5173/staff-login`         |
| Customer | `juan@example.com`      | `http://localhost:5173/login` → "Log in with password" |
| Customer | `customer@example.com`  | same                                        |
| Customer | `customer1@example.com` | same                                        |

Staff and customers log in on **separate pages**, and each page only accepts its
own role. The staff page (`/staff-login`) is not linked from anywhere on the
public site, so staff should bookmark it.

**Can't log in as admin?** Check these, in order:

1. Use **`http://localhost:5173/staff-login`**. The customer page (`/login`) refuses staff accounts.
2. After pulling, run `php artisan migrate` in `backend/`. If a new column is missing, the
   login fails with "Something went wrong".
3. If your database was filled before the demo password was set (it then still has the
   old project's passwords), reset everything with `php artisan migrate:fresh --seed`, or
   reset just the admin password in `backend/`:

   ```powershell
   php artisan tinker --execute '$admin = App\Models\User::where("email", "admin@gadgetshop.com")->first(); $admin->forceFill(["password" => "password", "role" => "admin", "status" => "active"])->save();'
   ```

4. After 10 wrong tries in a minute you'll see "Too Many Attempts". Wait one minute.

The seed data (5 categories, 40 gadgets with photos and stock, 27 purchases,
18 reviews, 4 users) is the original project's database
(`gadget_review_system.sql`) in `backend/database/seeders/data/*.json` and
`backend/database/seeders/images/`. You do not import the `.sql` file: its
tables are the old layout, and `php artisan migrate --seed` builds the new
layout and loads the same rows.

## 3. Login with Google (optional)

1. Go to <https://console.cloud.google.com/> and create a project.
2. **APIs & Services → OAuth consent screen**: choose *External*, fill in the app
   name and your email, and add your team's Gmail addresses as *Test users*.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID**:
   - Application type: *Web application*
   - Authorized redirect URI: `http://localhost:8000/auth/google/callback`
4. Copy the client ID and secret into `backend/.env`:

   ```env
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   ```

5. In `backend/`, run `php artisan config:clear`, then stop and restart `php artisan serve`.
6. Open the app at `http://localhost:5173` (not `127.0.0.1`) and click "Continue with Google".
   While the Google project is in *Testing* mode, only the Gmail addresses you added as
   *Test users* can sign in.

### How Google login works in this store

Google is a way for **customers to log in, never to sign up**. This is a small
store where customers only get an account after buying in person:

1. A customer buys a gadget at the counter.
2. Staff record the sale in **Admin → Purchases → New Customer**, entering the
   customer's **Gmail address**. The temporary password is optional and only for
   customers without Gmail.
3. The customer opens `/login` and clicks **Continue with Google**. Laravel
   (`app/Http/Controllers/Auth/GoogleController.php`) matches their Google account
   to the account staff created, links it, and logs them in.
4. Customers without Gmail click **"No Gmail? Log in with password"** and use the
   temporary password.

What the Google login refuses (each has an automated test in `tests/Feature/GoogleLoginTest.php`):

| Situation | Result |
|---|---|
| Gmail that doesn't belong to any customer | Refused with "accounts are created when you buy in store". **No account is created.** |
| A staff member's Gmail | Refused the same way. Staff use `/staff-login` with a password. |
| An inactive customer | Refused |
| A Google account whose email Google hasn't verified | Refused |
| An email already linked to a *different* Google account | Refused |

The first Google login links the Google account (`users.google_id`), so later
logins keep working even if the customer's Gmail address changes. If staff change
a customer's email in **Admin → Users** (for example, to fix a typo), the old
Google link is removed and the new email is used next time. The Customers page
shows each customer's login method and last login.

Behind the scenes, the "Continue with Google" button links to
`/auth/google/redirect` (forwarded to Laravel by the Vite proxy). After Google
signs the person in, Laravel sends the browser back to `FRONTEND_URL` (set in
`backend/.env`, default `http://localhost:5173`).

## 4. Automated tests

The tests use a separate database so they never touch your real data:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE anatech_laravel_test"
cd backend && php artisan test
```

The tests cover login, the catalog, the purchase-before-review rule, stock
limits, admin-only access, moderation, user management, the dashboard
numbers and that the backend serves JSON only.

## 5. How the project is organized

```
anatech-laravel/
├── backend/     Laravel API (described below)
└── frontend/    React app
    ├── index.html, vite.config.js (dev proxy to Laravel), public/images
    └── src/     main.jsx, App.jsx (all routes), api.js (axios), auth/, layouts/,
                 components/, pages/{guest,customer,admin,auth}/, css/
```

### Backend (`backend/`)

```
app/
├── Models/                      Eloquent models = the database tables + relationships
│   ├── User.php                 role (admin/customer), status, hasPurchased(), maskedName()
│   ├── Category.php
│   ├── Gadget.php               scopes: active(), archived(), search(), withStats(); addStock()/removeStock()
│   ├── Inventory.php            one stock row per gadget
│   ├── Purchase.php             in-store sale; unit_price saved at time of sale
│   └── Review.php               status: pending → approved / rejected
├── Http/
│   ├── Controllers/Api/         public + customer endpoints
│   │   ├── AuthController.php       login / logout / current user
│   │   ├── CatalogController.php    catalog list, gadget page, filters
│   │   ├── MyAccountController.php  My Purchases / My Reviews
│   │   ├── ReviewController.php     write / edit / delete own review
│   │   └── Admin/                   admin-only endpoints
│   │       ├── DashboardController.php
│   │       ├── GadgetController.php     CRUD, archive, stock in/out, photo upload
│   │       ├── PurchaseController.php   record sale (+ create new customer)
│   │       ├── ReviewController.php     approve / reject / bulk
│   │       ├── CustomerController.php
│   │       └── UserController.php
│   ├── Controllers/Auth/GoogleController.php   Login with Google (Socialite)
│   ├── Middleware/EnsureUserIsAdmin.php        the "admin" route middleware
│   └── Resources/               API Resources: shape models into the JSON React receives
└── Policies/ReviewPolicy.php    customers may only edit/delete their own review

database/
├── migrations/                  table definitions (users, categories, gadgets, inventories, purchases, reviews)
├── seeders/                     load the original project's data
└── factories/                   fake data for the automated tests

routes/
├── api.php                      every /api endpoint, grouped by who may call it
└── web.php                      only the Google login redirect + callback
```

### Mapping from the old PHP version

| Old (native PHP)                          | New (Laravel)                                    |
|-------------------------------------------|--------------------------------------------------|
| `classes/*.php` with hand-written SQL     | `app/Models` (Eloquent) + relationships + scopes |
| `ajax/*.php` with an `action` switch      | Controllers + `routes/api.php`                   |
| `includes/admin/auth.php`                 | `auth:sanctum` + `admin` middleware              |
| Manual `if` checks for input              | `$request->validate([...])` rules                |
| `password_hash()` / `password_verify()`   | the model's `hashed` cast + `Auth::attempt()`    |
| `.sql` files run by hand in phpMyAdmin    | migrations + seeders (`php artisan migrate --seed`) |
| `uploads/gadgets/`                        | `storage/app/public/gadgets` (`Storage` facade)  |
| Manual stock "rollback"                   | `DB::transaction()` + row lock (`lockForUpdate`) |

### Improvements over the original

- **Admin API is locked down.** In the old version, anyone could call the admin
  AJAX files directly (even create an admin account). Now every admin endpoint
  needs an admin login (`tests/Feature/Admin/AdminAccessTest.php` proves it).
- **One review per customer per gadget** is also enforced by a unique index in the database.
- **Purchases store the sale price**, so editing a gadget's price later never changes past revenue.
- **Recording a sale is all-or-nothing.** If stock runs out, a newly created customer account is rolled back too.
- **Stock can't be oversold** by two requests at once (row lock inside a transaction).
- **Admins can't lock themselves out** by demoting, deactivating or deleting their own account.
- **Login is rate-limited** (10 attempts per minute) and inactive accounts can't log in.
- **Login with Google**.

## 6. API reference (for the React side)

All endpoints are under `/api` and return JSON. React calls them through the Vite
dev proxy (same address as the page), so the login session cookie and CSRF token
work automatically with axios. Validation errors come back as
**422** with `{ "message": "...", "errors": { "field": ["..."] } }`. Not logged in is
**401**, and not allowed is **403**.

### Public

| Method | URL | Notes |
|---|---|---|
| POST | `/api/login` | Customer password login: `{ email, password }` → the user. Refuses staff accounts. |
| POST | `/api/staff/login` | Staff password login: `{ email, password }` → the user. Refuses customer accounts. |
| GET | `/api/gadgets` | Catalog. Query: `keyword`, `category_id`, `brand`, `sort` = `newest` \| `price_asc` \| `price_desc` \| `rating` \| `popular` \| `name` |
| GET | `/api/gadgets/{id}` | `{ gadget, reviews, rating_counts, viewer: { logged_in, has_purchased, my_review } }` |
| GET | `/api/categories` | `[{ id, name }]` |
| GET | `/api/brands` | `["Apple", "Samsung", ...]` |

A gadget looks like: `{ id, category_id, category, brand, gadget_name, model, price,
description, image_url, archived, stock, units_sold, avg_rating, review_count }`.

### Logged in (customer or admin)

| Method | URL | Notes |
|---|---|---|
| GET | `/api/user` | Who is logged in (401 if nobody) |
| POST | `/api/logout` | |
| GET | `/api/my/purchases` | Active purchases with `gadget` |
| GET | `/api/my/reviews` | All own reviews (any status) with `gadget` |
| POST | `/api/reviews` | `{ gadget_id, rating (1–5), comment }`. 403 if not purchased, 422 if already reviewed |
| PUT | `/api/reviews/{id}` | `{ rating, comment }`. Own review only, and it goes back to `pending` |
| DELETE | `/api/reviews/{id}` | Own review only |

### Admin only (`/api/admin/...`)

| Method | URL | Notes |
|---|---|---|
| GET | `/dashboard` | `{ stats, top_selling, monthly_sales, low_stock }` |
| GET | `/gadgets` | Query: `archived=1`, `keyword`, `category_id` |
| GET | `/gadgets/{id}` | |
| POST | `/gadgets` | **multipart/form-data**: `category_id, brand, gadget_name, model, price, description, image` (file) |
| POST | `/gadgets/{id}` | Edit (POST, not PUT, because of the file upload). Same fields; `image` is optional |
| PATCH | `/gadgets/{id}/archive`, `/gadgets/{id}/unarchive` | |
| POST | `/gadgets/{id}/stock-in`, `/gadgets/{id}/stock-out` | `{ quantity }` → `{ stock }`. Stock-out returns 422 if there isn't enough |
| GET | `/purchases` | Query: `archived=1` |
| POST | `/purchases` | Existing customer: `{ user_id, gadget_id, quantity, purchase_date }`. New customer: `{ new_customer: true, name, email, password?, gadget_id, quantity, purchase_date }`. `email` is the customer's Gmail; `password` is optional (only for customers without Gmail) |
| PATCH | `/purchases/{id}/archive`, `/purchases/{id}/unarchive` | |
| GET | `/reviews` | Query: `status=pending` (default) or `status=archived` |
| PATCH | `/reviews/{id}/approve`, `/reviews/{id}/reject` | |
| POST | `/reviews/bulk` | `{ action: "approve" \| "reject", ids: [1, 2, 3] }` |
| GET | `/customers` | Query: `keyword`. Includes `purchases_count`, `reviews_count` |
| GET | `/customers/{id}/purchases` | One customer's purchase history |
| GET | `/users` | Query: `keyword`, `role`, `status` |
| POST | `/users` | `{ name, email, password?, role, status? }`. `password` is required for staff, optional for customers |
| PUT | `/users/{id}` | `{ name, email, role, status }`. Changing the email unlinks the old Google account |
| PUT | `/users/{id}/password` | `{ password }` |
| DELETE | `/users/{id}` | Also deletes their purchases and reviews, so prefer setting `status` to `inactive` |

### Pages React should provide

Google login (customers only) redirects to `/` on the React app, and to
`/login?error=...` when it's refused or something goes wrong (`google-not-configured`,
`google-cancelled`, `google-failed`, `google-unverified`, `no-account`, `inactive`).
Staff log in at `/staff-login`.
"# Anatech-Laravel-Customer" 
