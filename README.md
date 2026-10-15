# Anatech — Gadget Catalog & Review System (Laravel + React)

Anatech is a gadget shop catalog. Customers browse phones, tablets, laptops,
smartwatches and accessories. They can only review a gadget after staff have
recorded an in-store purchase for them, and every review is moderated by an
admin before it goes public.

This is the Laravel + React version of the original native-PHP project.

- **Backend:** Laravel 13 (PHP 8.3+), a JSON API under `/api`
- **Frontend:** React (served by the same Laravel app through Vite)
- **Database:** MySQL / MariaDB (XAMPP)
- **Login:** email + password, or Google (Laravel Socialite)

---

## 1. Setup on a new computer

Start **MySQL** in the XAMPP Control Panel, then run these in the project folder:

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Create the database (or use phpMyAdmin → New → `anatech_laravel`):

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE anatech_laravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Create the tables, load the data, and make product photos reachable:

```bash
php artisan migrate --seed
php artisan storage:link
```

Run the app (two terminals):

```bash
php artisan serve
npm run dev
```

Open **http://localhost:8000**. Always use `localhost:8000`, not `127.0.0.1:8000`:
the login session and Google login are set up for that address.

To reset everything back to the original data at any time:

```bash
php artisan migrate:fresh --seed
```

## 2. Demo accounts

All seeded accounts use the password **`password`** (set in
`database/seeders/UserSeeder.php`).

| Role     | Email                   |
|----------|-------------------------|
| Admin    | `admin@gadgetshop.com`  |
| Customer | `juan@example.com`      |
| Customer | `customer@example.com`  |
| Customer | `customer1@example.com` |

The seed data (5 categories, 40 gadgets with photos and stock, 27 purchases,
18 reviews) was carried over from the original project's database. It lives in
`database/seeders/data/*.json` and `database/seeders/images/`.

## 3. Login with Google (optional)

1. Go to <https://console.cloud.google.com/> and create a project.
2. **APIs & Services → OAuth consent screen**: choose *External*, fill in the app
   name and your email, and add your team's Gmail addresses as *Test users*.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID**:
   - Application type: *Web application*
   - Authorized redirect URI: `http://localhost:8000/auth/google/callback`
4. Copy the client ID and secret into `.env`:

   ```env
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   ```

5. Run `php artisan config:clear`.

The Login page's "Continue with Google" button should link to
`/auth/google/redirect`. If someone logs in with Google using the same email as
an existing account, it logs into that account. Otherwise a new customer account
is created. They can browse right away, but can only review gadgets after an
admin records a purchase for them.

## 4. Automated tests

The tests use a separate database so they never touch your real data:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE anatech_laravel_test"
php artisan test
```

68 tests cover login, the catalog, the purchase-before-review rule, stock
limits, admin-only access, moderation, user management and the dashboard
numbers.

## 5. How the backend is organized

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
└── web.php                      Google login routes + the page that loads React
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

All endpoints are under `/api` and return JSON. React must call them from the
page served by `php artisan serve` (same site), so the login session cookie and
CSRF token work automatically with axios. Validation errors come back as
**422** with `{ "message": "...", "errors": { "field": ["..."] } }`. Not logged in is
**401**, and not allowed is **403**.

### Public

| Method | URL | Notes |
|---|---|---|
| POST | `/api/login` | `{ email, password }` → the user (`role` tells admin vs customer) |
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
| POST | `/purchases` | Existing customer: `{ user_id, gadget_id, quantity, purchase_date }`. New customer: `{ new_customer: true, name, email, password, gadget_id, quantity, purchase_date }` |
| PATCH | `/purchases/{id}/archive`, `/purchases/{id}/unarchive` | |
| GET | `/reviews` | Query: `status=pending` (default) or `status=archived` |
| PATCH | `/reviews/{id}/approve`, `/reviews/{id}/reject` | |
| POST | `/reviews/bulk` | `{ action: "approve" \| "reject", ids: [1, 2, 3] }` |
| GET | `/customers` | Query: `keyword`. Includes `purchases_count`, `reviews_count` |
| GET | `/customers/{id}/purchases` | One customer's purchase history |
| GET | `/users` | Query: `keyword`, `role`, `status` |
| POST | `/users` | `{ name, email, password, role, status? }` |
| PUT | `/users/{id}` | `{ name, email, role, status }` |
| PUT | `/users/{id}/password` | `{ password }` |
| DELETE | `/users/{id}` | Also deletes their purchases and reviews, so prefer setting `status` to `inactive` |

### Pages React should provide

Google login redirects to `/` (customers) or `/admin` (admins), and to
`/login?error=...` when something goes wrong (`google-not-configured`,
`google-failed`, `inactive`).
