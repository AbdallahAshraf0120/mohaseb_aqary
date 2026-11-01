# Mohaseb Aqary — Real Estate Accounting & Property Management

**Mohaseb Aqary** (محاسب عقاري) is a full real-estate development management system built with **Laravel 12**.
It covers the whole lifecycle of a real-estate project: land acquisition, units and inventory, sales and
installment contracts, shareholders and ownership, cashboxes and accounting, CRM, and a public marketing website.
The admin panel is Arabic (RTL), and a REST API exposes the core modules for mobile and third-party clients.

---

## Key Features

### Projects & Properties
- Multi-project setup: data is scoped to the current project (`BelongsToProject`, `CurrentProject`)
- Properties and units with areas, facings, and status tracking
- Interactive **site sketch** for visualizing units on the project plan
- Daily scheduled email report of available units

### Land Trading
- Lands, land parcels, and parcel parts
- Installment plans for land purchases (`LandInstallmentPlanBuilder`)
- Parcel payments and payment distribution across shareholders
- Dedicated land-trading cashbox

### Sales, Contracts & Debts
- Sales linked to clients, units and contracts
- Contract generation as **Word documents** (PHPWord)
- Debts and debt payments with remaining-balance tracking
- Settlements and an approvals workflow

### Shareholders & Ownership
- Shareholders attached to projects and land parcels
- Ownership percentages with historical **ownership snapshots**
- Per-shareholder **ledger** with automatic attribution of sales and revenues
- Shareholder funding and attributed cash flows

### Accounting & Treasury
- Revenues, expenses, and expense types
- Multiple cashboxes, a global cashbox, and **fund transfers** between them
- Cashbox ledger with a rebuild command (`php artisan cashbox:rebuild`)
- Money handling through a dedicated `Money` value helper

### Reports
- Dashboard with project KPIs
- **Excel** exports for project reports (PhpSpreadsheet)
- **PDF** generation (mPDF)

### CRM & Tasks
- CRM leads with activity history
- Tasks with progress updates

### Security & Audit
- Role-based access control: roles, permissions, and per-user exclusive permissions
- Route-level permission middleware, plus an audit command (`php artisan permissions:audit-routes`)
- Full **activity log** (spatie/laravel-activitylog) and HTTP request auditing

### Public Website
- Website settings and projects managed from the admin panel
- Marketing site with a **3D model** hero section (Three.js)

### REST API
- Token-based authentication: register, login, logout, me
- Resources: properties, clients, contracts, sales, debts, shareholders
- Accounting endpoints: revenues, expenses, treasury, contract payments
- Dashboard summary and settings

---

## Architecture

- **Service layer** for business logic (`app/Services`): ownership, shareholder ledger, cashbox balances,
  land parcel payments, fund transfers, contract documents, report exports
- **Repository pattern** with interfaces (`app/Repositories/Contracts` → `app/Repositories/Eloquent`)
- **Form Requests** for validation and **Policies** for authorization
- Custom middleware for project context, role checks, route permissions and request auditing
- 35+ Eloquent models and 60+ database migrations

## Tech Stack

| Layer | Technologies |
|---|---|
| Backend | PHP 8.2, Laravel 12, Eloquent ORM, REST API |
| Database | MySQL (SQLite for local development) |
| Documents | PHPWord, PhpSpreadsheet, mPDF |
| Frontend | Blade, AdminLTE 4, Bootstrap 5, Tailwind CSS 4, Tom Select, SweetAlert2, Three.js, Vite |
| Logging | spatie/laravel-activitylog |
| Testing | PHPUnit (feature and unit tests for authorization, routing, permissions, ownership calculations) |

## Getting Started

```bash
git clone https://github.com/AbdallahAshraf0120/mohaseb_aqary.git
cd mohaseb_aqary

composer install
npm install

cp .env.example .env
php artisan key:generate

# configure the database in .env (SQLite works out of the box), then:
php artisan migrate --seed

npm run build
php artisan serve
```

Run the tests:

```bash
php artisan test
```

## Author

**Abdallah Ashraf** — Laravel Backend Developer
[LinkedIn](https://www.linkedin.com/in/abdallah-ashraf-75a578283/) · [GitHub](https://github.com/AbdallahAshraf0120)
