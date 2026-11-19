# Mohaseb Aqary (محاسب عقاري) — Project Blueprint

| | |
|---|---|
| **Repository** | `AbdallahAshraf0120/mohaseb_aqary` (GitHub, branch `main`, commit `e7f3cf8` plus one uncommitted view change) |
| **Analysed path** | `C:\laragon\www\محاسب عقاري\mohaseb_aqary` (local working copy) |
| **Production** | https://elmanaregy.com (Hostinger shared hosting) |
| **Prepared** | 9 November 2026 |
| **Method** | Read-only static analysis of the code, `php artisan route:list`, the role/permission seeder, a live walkthrough of production, and desk research. |

> **Reading the diagrams.** Every diagram is embedded below as inline SVG. VS Code's Markdown preview, Obsidian and most browsers render it. GitHub's web viewer strips inline SVG, so the interactive originals (light/dark toggle) are linked from each caption and listed in the [Appendix](#appendix).

## Table of contents

1. [Executive summary](#executive-summary)
2. [Business overview](#business-overview)
3. [Market and competitive research](#market-research)
4. [Technical overview](#technical-overview)
5. [Architecture diagrams](#architecture-diagrams)
6. [Dashboard analysis](#dashboard-analysis)
7. [API reference — endpoint inventory](#api-reference)
8. [Mobile opportunity analysis](#mobile-opportunity)
9. [Mobile app proposals](#mobile-apps)
   - [App 1 — Owner & Partner companion](#app-owner)
   - [App 2 — Sales field app](#app-sales)
   - [App 3 — Buyer portal](#app-buyer)
10. [Comparison matrix and recommended roadmap](#roadmap)
11. [Decisions and assumptions](#decisions)
12. [Appendix](#appendix)

---

<a id="executive-summary"></a>
## 1. Executive summary

**What it is.** Mohaseb Aqary is an Arabic, right-to-left web ERP for small and mid-sized Egyptian real-estate developers. It covers the whole life of a residential building project:

- buying and splitting land;
- building properties, with floors, units and models;
- selling units with cash or instalment plans;
- collecting instalments;
- paying expenses and suppliers;
- running one cashbox per project;
- tracking each partner's capital and profit share.

It also ships a public marketing website and light CRM and task modules (`routes/web.php`, `app/Models/*`).

**How it is built.** It is a single Laravel 12 / PHP 8.3 monolith with server-rendered Blade views (AdminLTE 4 + Bootstrap 5) and MySQL/MariaDB (`composer.json`, `package.json`).

- **Projects.** Every business record carries a `project_id`, and a global scope limits queries to the current project (`app/Models/Concerns`, `app/Support/CurrentProject.php`).
- **Access.** It comes from roles and a route-to-permission map (`config/route-permissions.php`, `app/Http/Middleware/AuthorizeRoutePermission.php`). The four roles are admin, accountant, sales and viewer.
- **Approvals.** Money-moving entries made by non-admins wait for approval before they reach the cashbox (`app/Http/Controllers/ApprovalsController.php`).

**Where it stands.**

- The web product is functionally broad: 174 routes, 34 controllers, 35 models and 65 migrations.
- It was recently hardened. The changes include:
  - rate-limited login and security headers;
  - amount caps;
  - unit-level duplicate-sale prevention;
  - guarded deletes;
  - a rewritten settlement and partner-profit page.
- **It has no working machine-readable API.** `routes/api.php` declares a Sanctum REST API, but it is not registered in `bootstrap/app.php`, and the `App\Http\Controllers\Api\*` classes it references do not exist.
- So every mobile idea below depends first on a new `/api/v1` layer. That layer would reuse the same services the dashboard already uses.

**Recommendation.** Three apps make sense. Build them in this order:

1. **Owner & Partner companion** (approvals, cashbox, settlement, push alerts). Size: M. Highest value per effort, and almost all of its logic already exists.
2. **Sales field app** (live unit availability, on-site reservation, CRM). Size: M–L.
3. **Buyer portal** (my units, instalment schedule, receipts, reminders). Size: L. It needs new concepts: client login and a stored instalment schedule.

Building the shared API foundation first is roughly an M-sized effort, and it de-risks all three apps.

---

<a id="business-overview"></a>
## 2. Business overview

### 2.1 Idea
The product replaces the spreadsheets, paper receipt books and WhatsApp threads that small Egyptian developers use to run a building project. Its distinctive focus is the **developer's own money**, more than the property listing:

- **Project and partner money.** It keeps one cashbox per project, partner capital and withdrawals, and a settlement page that answers "how much profit did we make, and how much is each partner owed today?" (`app/Http/Controllers/SettlementController.php`, `app/Services/ShareholderLedgerService.php`).
- **Land trading as a business line.** Land can be held and traded with its own cashbox and partners (`app/Http/Controllers/LandTradingController.php`, `app/Support/LandTradingCashbox.php`).
- **Physical inventory.** Units are modelled as floor × unit-number, with a visual "site sketch" of sold and available units (`app/Http/Controllers/SiteSketchController.php`, `app/Models/Property.php`).

### 2.2 Target customers
*Assumption, inferred from the domain model, the Egyptian-Arabic copy, EGP currency formatting (`app/Support/Money.php`) and the production deployment.*

- **Primary.** Small and mid-sized residential developers and contractor-developers in Egypt, typically building 1–20 buildings at a time with a handful of equity partners.
- **Secondary.** Land traders who buy, split and resell parcels.
- **Internal users.**
  - Owner/partners: admin role.
  - Accountant: accountant role.
  - Sales staff: sales role.
  - Read-only stakeholders: viewer role (`database/seeders/PermissionSeeder.php`).

### 2.3 Value proposition
- **One true cashbox per project.** Every receipt and payment posts to `treasury_transactions` with a reference to its source document, and only approved movements count (`app/Services/CashboxLedgerService.php`, `app/Services/CashboxBalanceService.php`).
- **Partner clarity.** It shows realized and expected profit, each partner's share by percentage, and "due now" (capital balance + realized share) (`SettlementController.php`).
- **No double-selling.** Unit numbers are validated against floor capacity, and sold units are locked (`app/Http/Requests/StoreSaleRequest.php`).
- **Maker–checker control.** Sales staff and accountants create entries, and the owner approves them (`ApprovalsController.php`).
- **Arabic-first documents.** Word contracts, PDF reports and Excel exports are generated in Arabic (`app/Services/ContractWordDocumentService.php`, `ReportController.php`).

### 2.4 Revenue model
*Assumption.* There is no billing, subscription or tenant-management code in the repository: no payment gateway, no plans table, and one database per deployment. Today the product is therefore sold as a **custom-deployed, single-company installation** (a licence or setup fee plus hosting and support). A SaaS model would need multi-tenancy and billing (see [Risks](#risks)).

### 2.5 Primary user journeys
| # | Journey | Main screens / handlers |
|---|---|---|
| J1 | Set up a project → area → property (floors, units, models) | `ProjectController`, `AreaController`, `PropertyController` |
| J2 | Sell a unit (cash or instalments) → contract generated → (approval) → down payment posted to cashbox | `SaleController@store`, `ApprovalsController@approve` |
| J3 | Collect an instalment against a contract → remaining balance drops | `RevenueController@store`, `RemainingController@index` |
| J4 | Record expenses and supplier debts → pay suppliers from the cashbox | `ExpenseController`, `DebtController@payFromCashbox` |
| J5 | Record partner capital or withdrawals → see the partner ledger | `ShareholderLedgerController@store`, `ShareholderController@show` |
| J6 | Close the period: settlement, reports, exports | `SettlementController@index`, `ReportController@export*` |
| J7 | Land trading: buy a parcel, split it into parts, record purchase/sale payments, distribute to partners | `LandTradingController@*` |
| J8 | Pipeline: capture a lead, log calls, assign follow-up tasks | `CrmLeadController`, `TaskController` |

---

<a id="market-research"></a>
## 3. Market and competitive research

*Desk research carried out on 9 November 2026. Figures are taken from the headlines of the cited sources and are indicative only.*

- **Market context.**
  - Egypt's largest developers reported about EGP 670 bn of sales in H1 2026 ([Daily News Egypt](https://www.dailynewsegypt.com/?p=852602)).
  - Analysts describe the wider market as entering a "correction phase" ([Daily News Egypt, May 2026](https://www.dailynewsegypt.com/2026/05/31/egypts-top-developers-post-egp-271bn-in-sales-as-real-estate-market-enters-correction-phase/)), and say that revenues of top developers are growing while the market producing them shrinks ([Enterprise](https://enterpriseam.com/egypt/hardhat/revenues-for-egypts-top-developers-are-growing-but-the-market-producing-these-sales-is-shrinking/)).
  - Rising costs are reshaping investment strategies ([Ahram Online](https://english.ahram.org.eg/NewsContent/3/14/561830/Business/Markets--Companies/Rising-costs,-market-restructuring-reshape-Egypt-r.aspx)).
  - *Implication (assumption):* smaller developers face tighter cash and longer instalment plans, which raises the value of accurate cash and receivables control.
- **Competitor groups.**

  | Group | Examples | Strengths | Gap Mohaseb Aqary can exploit |
  |---|---|---|---|
  | General ERPs with real-estate modules | Odoo (used by Egyptian developers such as [Reflect Developments](https://www.odoo.com/en_US/customers/reflect-developments-23068948); third-party real-estate apps such as [Rightechs](https://rightechs-solutions.odoo.com/realestate-solution)), Zoho (see the [Beni Suef developer case study](https://www.zolute.consulting/case-studies/developing-egypt-real-estate-sector-with-zoho-smarter-construction-and-sales-in-beni-suef)) | Breadth, official mobile apps, integrations | Expensive, long implementations, not built around Egyptian partner-settlement practice |
  | Regional real-estate ERP vendors | Products listed by [ERP+](https://erppluscloud.com), [HashMicro](https://www.hashmicro.com/my/real-estate-erp-software) | Construction plus sales suites | Enterprise pricing and English-first UX |
  | Custom freelance builds | Requests on [Khamsat](https://khamsat.com/community/requests/792044-%D8%A8%D8%B2%D9%86%D8%A7%D9%85%D8%AC-%D9%83%D8%A7%D9%85%D9%84) and [Mostaql](https://mostaql.com/portfolio/3713681-laravel-developer-property-erp-real-estate-management-platform) | Cheap, tailored | No product roadmap, no support, rarely mobile |
  | Spreadsheets + WhatsApp | — | Free, familiar | No controls, errors, no partner transparency |

- **How competitors handle mobile.**
  - General ERPs offer generic mobile clients (approvals, dashboards, CRM) rather than developer-specific workflows. Vertical vendors increasingly ship buyer self-service portals.
  - *Assumption based on product pages; no hands-on test.*
- **Mobile UX expectations in this category** (*assumption, industry practice*):
  - Arabic RTL first.
  - Push notifications for approvals and due instalments.
  - Biometric unlock.
  - WhatsApp hand-off for client contact.
  - Offline read access on site.
  - PDF receipts that can be shared.
  - Simple OTP login for buyers.

---

<a id="technical-overview"></a>
## 4. Technical overview

### 4.1 Stack
| Layer | Technology | Source |
|---|---|---|
| Language / framework | PHP ^8.2 (8.3 in production), Laravel ^12 | `composer.json` |
| UI | Blade, AdminLTE 4 rc, Bootstrap 5.3, Font Awesome 7, SweetAlert2, Tom Select, three.js (site visuals); built with Vite 7 + Tailwind 4 | `package.json`, `vite.config.js` |
| Database | MySQL / MariaDB, one schema, `project_id` on business tables | `database/migrations/*` |
| Documents | mPDF (PDF), PhpSpreadsheet (Excel), PhpWord (contracts) | `composer.json`, `app/Services/ContractWordDocumentService.php`, `app/Services/ProjectReportsExcelExporter.php` |
| Audit | spatie/laravel-activitylog + `AuditHttpRequests` middleware | `composer.json`, `app/Http/Middleware/AuditHttpRequests.php` |
| Queue / cache | Database driver by default | `config/queue.php` |
| Mail | Laravel mailer (daily available-units report) | `app/Mail/DailyAvailableUnitsReportMail.php` |
| Tests | PHPUnit 11 (Unit + Feature); QA flow suites in `tests/QA` exist only in the analysis copy and are not in the repository | `phpunit.xml`, `tests/` |

### 4.2 Structure (summary)
| Area | Contents | Count / source |
|---|---|---|
| Controllers | `app/Http/Controllers/*` (+ `Auth/`, `Site/`) | 34 |
| Form requests | `app/Http/Requests/*` | Validation including `Money::MAX_AMOUNT` |
| Services | `app/Services/*` | 13: cashbox ledger and balance, shareholder ledger and attribution, land payments, ownership, exports |
| Support | `app/Support/*` | `CurrentProject`, `Money`, `ListingFilters`, `WebsiteContent`, `LandTradingCashbox` |
| Models | `app/Models/*` | 35 |
| Middleware | `AuthorizeRoutePermission`, `SyncProjectFromRoute`, `SecurityHeaders`, `AuditHttpRequests`, `EnsureUserHasRole`, `SetApiCurrentProject` (unused) | `app/Http/Middleware` |
| Console | `app:data-audit`, `reports:daily-available-units`, `contracts:recalculate…`, `cashbox:rebuild`, `permissions:audit-routes` | `app/Console/Commands`, `routes/console.php` |
| Migrations | Schema history | 65 |

### 4.3 Environments
- **Local.** Laragon on Windows; `php artisan serve` or Apache (*assumption, from the folder layout*).
- **Production.** Hostinger shared hosting at `~/domains/elmanaregy.com/public_html/mohaseb_aqary`.
  - A root `.htaccess` exposes only `public/`.
  - Deployment is `git pull` → `php artisan migrate --force` → `php artisan optimize:clear`.
  - Configuration comes from `.env`; its values are not reproduced here.
- **Scheduler.** `reports:daily-available-units` runs every minute and is time-gated per project setting (`routes/console.php`). It needs a cron entry for `schedule:run` on the host (*assumption: not verifiable from code*).

### 4.4 Cross-cutting rules worth knowing
- **Project scoping.**
  - Project routes use the `/app/{project}/…` prefix.
  - `Route::bind('project')` forces `CurrentProject` (`app/Providers/AppServiceProvider.php`), and `SyncProjectFromRoute` keeps the session aligned.
  - Global routes (shareholders, approvals, land trading) fall back to the session project.
- **Permissions.**
  - Each named route maps to a slug (`config/route-permissions.php`), and the slug is checked with `Gate` (`AuthorizeRoutePermission`).
  - Role defaults are seeded (`PermissionSeeder`), and per-user `extra_permissions` can widen them (`app/Models/User.php`).
- **Money integrity.** Approved-only balances (`CashboxBalanceService::approvedBalance`), spend guards (`assertCanSpend`), and delete guards that refuse any delete that would drive the cashbox negative (`SaleController@destroy`, `RevenueController@destroy`).

---

<a id="architecture-diagrams"></a>
## 5. Architecture diagrams

### 5.1 Repository / module map
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 640" width="100%" role="img" aria-label="Repository / module map"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="640" rx="12"/><text x="24" y="34" class="h" text-anchor="start">Repository / module map</text><rect x="20" y="480" width="1060" height="120" rx="14" class="grp"/><rect x="20" y="340" width="1060" height="120" rx="14" class="grp"/><rect x="20" y="190" width="1060" height="130" rx="14" class="grp"/><rect x="20" y="50" width="1060" height="120" rx="14" class="grp"/><rect x="40" y="80" width="170" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="230" y="80" width="190" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="440" y="80" width="200" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="660" y="80" width="190" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="870" y="80" width="190" height="70" rx="10" class="warn" stroke-width="1.5"/><rect x="40" y="222" width="220" height="80" rx="10" class="core" stroke-width="1.5"/><rect x="280" y="222" width="190" height="80" rx="10" class="core" stroke-width="1.5"/><rect x="490" y="222" width="250" height="80" rx="10" class="shared" stroke-width="1.5"/><rect x="760" y="222" width="150" height="80" rx="10" class="core" stroke-width="1.5"/><rect x="930" y="222" width="130" height="80" rx="10" class="shared" stroke-width="1.5"/><rect x="40" y="372" width="300" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="360" y="372" width="220" height="70" rx="10" class="shared" stroke-width="1.5"/><rect x="600" y="372" width="220" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="840" y="372" width="220" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="40" y="512" width="220" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="280" y="512" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="490" y="512" width="190" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="700" y="512" width="170" height="70" rx="10" class="ext" stroke-width="1.5"/><rect x="890" y="512" width="170" height="70" rx="10" class="ext" stroke-width="1.5"/><path d="M125,150 L125,222" class="ln" marker-end="url(#ah)"/><path d="M540,150 L540,190 L150,190 L150,222" class="ln" marker-end="url(#ah)"/><path d="M260,262 L280,262" class="ln" marker-end="url(#ah)"/><path d="M470,262 L490,262" class="ln" marker-end="url(#ah)"/><path d="M640,302 L640,340 L190,340 L190,372" class="ln" marker-end="url(#ah)"/><path d="M340,407 L360,407" class="ln" marker-end="url(#ah)"/><path d="M40,290 L28,290 L28,547 L40,547" class="ln" marker-end="url(#ah)"/><path d="M590,512 L590,302" class="lnd" marker-end="url(#ah)"/><path d="M680,547 L700,547" class="ln" marker-end="url(#ah)"/><rect x="40" y="612" width="18" height="12" rx="3" class="core" stroke-width="1.5"/><rect x="190" y="612" width="18" height="12" rx="3" class="shared" stroke-width="1.5"/><rect x="340" y="612" width="18" height="12" rx="3" class="db" stroke-width="1.5"/><rect x="490" y="612" width="18" height="12" rx="3" class="ext" stroke-width="1.5"/><rect x="640" y="612" width="18" height="12" rx="3" class="warn" stroke-width="1.5"/><text x="32" y="66" class="gl" text-anchor="start">ENTRY &amp; HTTP LAYER</text><text x="125.0" y="105.0" class="t" text-anchor="middle">routes/web.php</text><text x="125.0" y="120.0" class="s" text-anchor="middle">174 routes</text><text x="125.0" y="133.0" class="s" text-anchor="middle">(site + /app)</text><text x="325.0" y="105.0" class="t" text-anchor="middle">bootstrap/app.php</text><text x="325.0" y="120.0" class="s" text-anchor="middle">middleware stack,</text><text x="325.0" y="133.0" class="s" text-anchor="middle">SecurityHeaders</text><text x="540.0" y="105.0" class="t" text-anchor="middle">Http/Middleware</text><text x="540.0" y="120.0" class="s" text-anchor="middle">AuthorizeRoutePermission</text><text x="540.0" y="133.0" class="s" text-anchor="middle">SyncProjectFromRoute</text><text x="755.0" y="112.0" class="t" text-anchor="middle">config/route-permissions</text><text x="755.0" y="127.0" class="s" text-anchor="middle">route name -&gt; slug</text><text x="965.0" y="105.0" class="t" text-anchor="middle">routes/api.php</text><text x="965.0" y="120.0" class="s" text-anchor="middle">planned, NOT loaded</text><text x="965.0" y="133.0" class="s" text-anchor="middle">(no Api controllers)</text><text x="32" y="206" class="gl" text-anchor="start">APPLICATION LAYER</text><text x="150.0" y="252.0" class="t" text-anchor="middle">Http/Controllers (34)</text><text x="150.0" y="267.0" class="s" text-anchor="middle">Sale, Revenue, Expense,</text><text x="150.0" y="280.0" class="s" text-anchor="middle">Shareholder, LandTrading ...</text><text x="375.0" y="252.0" class="t" text-anchor="middle">Http/Requests</text><text x="375.0" y="267.0" class="s" text-anchor="middle">Store/Update form</text><text x="375.0" y="280.0" class="s" text-anchor="middle">validation (Money::MAX)</text><text x="615.0" y="252.0" class="t" text-anchor="middle">Services (13)</text><text x="615.0" y="267.0" class="s" text-anchor="middle">CashboxLedger, CashboxBalance,</text><text x="615.0" y="280.0" class="s" text-anchor="middle">ShareholderLedger, LandPayment ...</text><text x="835.0" y="259.0" class="t" text-anchor="middle">Repositories</text><text x="835.0" y="274.0" class="s" text-anchor="middle">Contracts + Eloquent</text><text x="995.0" y="252.0" class="t" text-anchor="middle">Support</text><text x="995.0" y="267.0" class="s" text-anchor="middle">CurrentProject,</text><text x="995.0" y="280.0" class="s" text-anchor="middle">Money, Filters</text><text x="32" y="356" class="gl" text-anchor="start">DOMAIN &amp; PERSISTENCE</text><text x="190.0" y="397.0" class="t" text-anchor="middle">Models (35)</text><text x="190.0" y="412.0" class="s" text-anchor="middle">Project, Property, Sale, Contract,</text><text x="190.0" y="425.0" class="s" text-anchor="middle">Revenue, Expense, TreasuryTransaction</text><text x="470.0" y="404.0" class="t" text-anchor="middle">Concerns/BelongsToProject</text><text x="470.0" y="419.0" class="s" text-anchor="middle">global project scope</text><text x="710.0" y="404.0" class="t" text-anchor="middle">database/migrations (65)</text><text x="710.0" y="419.0" class="s" text-anchor="middle">MySQL / MariaDB schema</text><text x="950.0" y="397.0" class="t" text-anchor="middle">database/seeders</text><text x="950.0" y="412.0" class="s" text-anchor="middle">PermissionSeeder</text><text x="950.0" y="425.0" class="s" text-anchor="middle">roles x slugs</text><text x="32" y="496" class="gl" text-anchor="start">PRESENTATION, JOBS &amp; EXPORTS</text><text x="150.0" y="537.0" class="t" text-anchor="middle">resources/views (Blade)</text><text x="150.0" y="552.0" class="s" text-anchor="middle">AdminLTE 4 + Bootstrap 5</text><text x="150.0" y="565.0" class="s" text-anchor="middle">RTL Arabic UI</text><text x="375.0" y="544.0" class="t" text-anchor="middle">views/site</text><text x="375.0" y="559.0" class="s" text-anchor="middle">public marketing site</text><text x="585.0" y="537.0" class="t" text-anchor="middle">Console/Commands</text><text x="585.0" y="552.0" class="s" text-anchor="middle">data-audit, daily units</text><text x="585.0" y="565.0" class="s" text-anchor="middle">report, recalc</text><text x="785.0" y="537.0" class="t" text-anchor="middle">Mail</text><text x="785.0" y="552.0" class="s" text-anchor="middle">DailyAvailableUnits</text><text x="785.0" y="565.0" class="s" text-anchor="middle">ReportMail</text><text x="975.0" y="537.0" class="t" text-anchor="middle">Exports</text><text x="975.0" y="552.0" class="s" text-anchor="middle">mPDF, PhpSpreadsheet,</text><text x="975.0" y="565.0" class="s" text-anchor="middle">PhpWord</text><rect x="96.2" y="177.0" width="57.6" height="15" rx="3" class="lblbg"/><text x="125.0" y="188.0" class="lbl" text-anchor="middle">dispatch</text><rect x="313.1" y="181.0" width="63.800000000000004" height="15" rx="3" class="lblbg"/><text x="345.0" y="192.0" class="lbl" text-anchor="middle">authorize</text><rect x="389.3" y="331.0" width="51.4" height="15" rx="3" class="lblbg"/><text x="415.0" y="342.0" class="lbl" text-anchor="middle">persist</text><text x="60" y="470" class="lbl" text-anchor="middle">render</text><rect x="576.7" y="471.5" width="26.6" height="15" rx="3" class="lblbg"/><text x="590.0" y="482.5" class="lbl" text-anchor="middle">use</text><text x="64" y="622" class="s" text-anchor="start">Core code</text><text x="214" y="622" class="s" text-anchor="start">Shared services</text><text x="364" y="622" class="s" text-anchor="start">Schema / data</text><text x="514" y="622" class="s" text-anchor="start">Library / output</text><text x="664" y="622" class="s" text-anchor="start">Dead / risk</text></svg>
</div>

*Figure — Layered Laravel monolith: routes -> permission middleware -> controllers/form requests -> services -> project-scoped Eloquent models. routes/api.php exists but is not wired.* [Open interactive version](diagrams/01-module-map.html)

**Source files:** `routes/web.php`, `routes/api.php`, `bootstrap/app.php`, `app/Http/*`, `app/Services/*`, `app/Models/*`, `database/*`, `resources/views/*`, `app/Console/Commands/*`.

### 5.2 High-level runtime architecture
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 600" width="100%" role="img" aria-label="High-level runtime architecture"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="600" rx="12"/><text x="24" y="34" class="h" text-anchor="start">High-level runtime architecture</text><rect x="870" y="50" width="210" height="470" rx="14" class="grp"/><rect x="280" y="50" width="560" height="470" rx="14" class="grp"/><rect x="20" y="50" width="230" height="470" rx="14" class="grp"/><rect x="40" y="90" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="40" y="190" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="40" y="290" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="40" y="400" width="190" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="300" y="90" width="240" height="70" rx="10" class="ext" stroke-width="1.5"/><rect x="570" y="90" width="250" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="300" y="200" width="240" height="80" rx="10" class="core" stroke-width="1.5"/><rect x="570" y="200" width="250" height="80" rx="10" class="core" stroke-width="1.5"/><rect x="300" y="320" width="240" height="80" rx="10" class="shared" stroke-width="1.5"/><rect x="570" y="320" width="250" height="80" rx="10" class="core" stroke-width="1.5"/><rect x="300" y="430" width="240" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="570" y="430" width="250" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="890" y="110" width="170" height="80" rx="10" class="db" stroke-width="1.5"/><rect x="890" y="240" width="170" height="70" rx="10" class="ext" stroke-width="1.5"/><rect x="890" y="350" width="170" height="70" rx="10" class="ext" stroke-width="1.5"/><path d="M230,125 L300,125" class="ln" marker-end="url(#ah)"/><path d="M230,225 L270,225 L270,140 L300,140" class="ln" marker-end="url(#ah)"/><path d="M540,125 L570,125" class="ln" marker-end="url(#ah)"/><path d="M695,160 L695,180 L420,180 L420,200" class="ln" marker-end="url(#ah)"/><path d="M695,160 L695,200" class="ln" marker-end="url(#ah)"/><path d="M420,280 L420,320" class="ln" marker-end="url(#ah)"/><path d="M820,360 L850,360 L850,150 L890,150" class="ln" marker-end="url(#ah)"/><path d="M540,360 L560,360 L560,300 L850,300 L850,160" class="lnd" marker-end="url(#ah)"/><path d="M820,345 L860,345 L860,275 L890,275" class="ln" marker-end="url(#ah)"/><path d="M890,290 L250,290 L250,325 L230,325" class="lnd" marker-end="url(#ah)"/><path d="M230,435 L300,435 L300,160" class="lnd" marker-end="url(#ah)"/><rect x="40" y="560" width="18" height="12" rx="3" class="client" stroke-width="1.5"/><rect x="190" y="560" width="18" height="12" rx="3" class="core" stroke-width="1.5"/><rect x="340" y="560" width="18" height="12" rx="3" class="shared" stroke-width="1.5"/><rect x="490" y="560" width="18" height="12" rx="3" class="db" stroke-width="1.5"/><rect x="640" y="560" width="18" height="12" rx="3" class="new" stroke-width="1.5"/><text x="32" y="66" class="gl" text-anchor="start">CLIENTS</text><text x="135.0" y="115.0" class="t" text-anchor="middle">Staff browser</text><text x="135.0" y="130.0" class="s" text-anchor="middle">Admin / accountant /</text><text x="135.0" y="143.0" class="s" text-anchor="middle">sales / viewer</text><text x="135.0" y="215.0" class="t" text-anchor="middle">Public visitor</text><text x="135.0" y="230.0" class="s" text-anchor="middle">marketing site,</text><text x="135.0" y="243.0" class="s" text-anchor="middle">contact form</text><text x="135.0" y="315.0" class="t" text-anchor="middle">Email inbox</text><text x="135.0" y="330.0" class="s" text-anchor="middle">daily available-</text><text x="135.0" y="343.0" class="s" text-anchor="middle">units report</text><text x="135.0" y="432.0" class="t" text-anchor="middle">Future mobile apps</text><text x="135.0" y="447.0" class="s" text-anchor="middle">see section 9</text><text x="292" y="66" class="gl" text-anchor="start">HOSTINGER SHARED HOSTING (ELMANAREGY.COM)</text><text x="420.0" y="115.0" class="t" text-anchor="middle">Apache + .htaccess</text><text x="420.0" y="130.0" class="s" text-anchor="middle">only /public reachable;</text><text x="420.0" y="143.0" class="s" text-anchor="middle">HTTPS</text><text x="695.0" y="115.0" class="t" text-anchor="middle">Laravel 12 / PHP 8.3</text><text x="695.0" y="130.0" class="s" text-anchor="middle">single monolith</text><text x="695.0" y="143.0" class="s" text-anchor="middle">(web guard, sessions)</text><text x="420.0" y="230.0" class="t" text-anchor="middle">Admin app  /app/*</text><text x="420.0" y="245.0" class="s" text-anchor="middle">Blade + AdminLTE,</text><text x="420.0" y="258.0" class="s" text-anchor="middle">project-scoped routes</text><text x="695.0" y="230.0" class="t" text-anchor="middle">Public site  /</text><text x="695.0" y="245.0" class="s" text-anchor="middle">Site\SiteController,</text><text x="695.0" y="258.0" class="s" text-anchor="middle">sitemap, contact</text><text x="420.0" y="350.0" class="t" text-anchor="middle">Services</text><text x="420.0" y="365.0" class="s" text-anchor="middle">cashbox ledger, approvals,</text><text x="420.0" y="378.0" class="s" text-anchor="middle">shareholder ledger</text><text x="695.0" y="350.0" class="t" text-anchor="middle">Scheduler / CLI</text><text x="695.0" y="365.0" class="s" text-anchor="middle">reports:daily-available-units</text><text x="695.0" y="378.0" class="s" text-anchor="middle">(every minute, time-gated)</text><text x="420.0" y="455.0" class="t" text-anchor="middle">Storage</text><text x="420.0" y="470.0" class="s" text-anchor="middle">storage/app uploads,</text><text x="420.0" y="483.0" class="s" text-anchor="middle">contract templates</text><text x="695.0" y="455.0" class="t" text-anchor="middle">Database queue / cache</text><text x="695.0" y="470.0" class="s" text-anchor="middle">QUEUE_CONNECTION</text><text x="695.0" y="483.0" class="s" text-anchor="middle">default: database</text><text x="882" y="66" class="gl" text-anchor="start">DATA &amp; EXTERNAL</text><text x="975.0" y="140.0" class="t" text-anchor="middle">MySQL / MariaDB</text><text x="975.0" y="155.0" class="s" text-anchor="middle">single schema,</text><text x="975.0" y="168.0" class="s" text-anchor="middle">project_id column</text><text x="975.0" y="272.0" class="t" text-anchor="middle">SMTP mailer</text><text x="975.0" y="287.0" class="s" text-anchor="middle">config/mail.php</text><text x="975.0" y="375.0" class="t" text-anchor="middle">GitHub</text><text x="975.0" y="390.0" class="s" text-anchor="middle">source; server</text><text x="975.0" y="403.0" class="s" text-anchor="middle">deploys via git pull</text><rect x="245.5" y="116.0" width="39.0" height="15" rx="3" class="lblbg"/><text x="265.0" y="127.0" class="lbl" text-anchor="middle">HTTPS</text><rect x="836.7" y="246.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="850.0" y="257.0" class="lbl" text-anchor="middle">SQL</text><rect x="843.6" y="301.0" width="32.8" height="15" rx="3" class="lblbg"/><text x="860.0" y="312.0" class="lbl" text-anchor="middle">send</text><rect x="745.6" y="281.0" width="32.8" height="15" rx="3" class="lblbg"/><text x="762.0" y="292.0" class="lbl" text-anchor="middle">mail</text><rect x="252.6" y="343.5" width="94.8" height="15" rx="3" class="lblbg"/><text x="300.0" y="354.5" class="lbl" text-anchor="middle">REST (planned)</text><text x="64" y="570" class="s" text-anchor="start">Client</text><text x="214" y="570" class="s" text-anchor="start">App</text><text x="364" y="570" class="s" text-anchor="start">Shared services</text><text x="514" y="570" class="s" text-anchor="start">Storage</text><text x="664" y="570" class="s" text-anchor="start">Proposed</text></svg>
</div>

*Figure — One PHP monolith on shared hosting serves both the public site and the staff app; MySQL holds all projects in one schema separated by project_id.* [Open interactive version](diagrams/02-runtime-architecture.html)

**Source files:** `bootstrap/app.php`, `routes/web.php`, `routes/console.php`, `config/queue.php`, `config/mail.php`, `public/.htaccess`; the Hostinger layout comes from the production deployment (*Assumption* for the cron entry).

### 5.3 Data-flow diagram
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 560" width="100%" role="img" aria-label="Core data flow: from sale to settlement"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="560" rx="12"/><text x="24" y="34" class="h" text-anchor="start">Core data flow: from sale to settlement</text><rect x="30" y="80" width="170" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="30" y="190" width="170" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="30" y="300" width="170" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="30" y="410" width="170" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="250" y="80" width="200" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="250" y="190" width="200" height="70" rx="10" class="shared" stroke-width="1.5"/><rect x="500" y="60" width="200" height="60" rx="10" class="db" stroke-width="1.5"/><rect x="500" y="135" width="200" height="60" rx="10" class="db" stroke-width="1.5"/><rect x="500" y="210" width="200" height="60" rx="10" class="db" stroke-width="1.5"/><rect x="500" y="285" width="200" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="500" y="375" width="200" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="760" y="60" width="300" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="760" y="140" width="300" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="760" y="220" width="300" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="760" y="300" width="300" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="760" y="380" width="300" height="60" rx="10" class="core" stroke-width="1.5"/><path d="M200,115 L225,115 L225,225 L250,225" class="ln" marker-end="url(#ah)"/><path d="M200,225 L225,225 L225,225 L250,225" class="ln" marker-end="url(#ah)"/><path d="M200,335 L225,335 L225,225 L250,225" class="ln" marker-end="url(#ah)"/><path d="M200,445 L225,445 L225,225 L250,225" class="ln" marker-end="url(#ah)"/><path d="M200,115 L250,115" class="ln" marker-end="url(#ah)"/><path d="M450,115 L475,115 L475,90 L500,90" class="ln" marker-end="url(#ah)"/><path d="M450,225 L500,240" class="ln" marker-end="url(#ah)"/><path d="M475,115 L475,165 L500,165" class="ln" marker-end="url(#ah)"/><path d="M450,240 L475,240 L475,320 L500,320" class="ln" marker-end="url(#ah)"/><path d="M475,320 L475,410 L500,410" class="ln" marker-end="url(#ah)"/><path d="M700,90 L760,90" class="ln" marker-end="url(#ah)"/><path d="M700,320 L730,320 L730,170 L760,170" class="ln" marker-end="url(#ah)"/><path d="M700,165 L740,165 L740,250 L760,250" class="ln" marker-end="url(#ah)"/><path d="M700,240 L745,240 L745,330 L760,330" class="ln" marker-end="url(#ah)"/><path d="M700,410 L750,410 L750,340 L760,340" class="ln" marker-end="url(#ah)"/><path d="M700,300 L735,300 L735,410 L760,410" class="lnd" marker-end="url(#ah)"/><text x="115.0" y="112.0" class="t" text-anchor="middle">Sale form</text><text x="115.0" y="127.0" class="s" text-anchor="middle">sales/_form.blade.php</text><text x="115.0" y="222.0" class="t" text-anchor="middle">Revenue form</text><text x="115.0" y="237.0" class="s" text-anchor="middle">installment collected</text><text x="115.0" y="332.0" class="t" text-anchor="middle">Expense / debt</text><text x="115.0" y="347.0" class="s" text-anchor="middle">expenses, debts</text><text x="115.0" y="442.0" class="t" text-anchor="middle">Partner ledger</text><text x="115.0" y="457.0" class="s" text-anchor="middle">capital / withdrawal</text><text x="350.0" y="105.0" class="t" text-anchor="middle">StoreSaleRequest</text><text x="350.0" y="120.0" class="s" text-anchor="middle">unit_number, floor</text><text x="350.0" y="133.0" class="s" text-anchor="middle">capacity, max amount</text><text x="350.0" y="215.0" class="t" text-anchor="middle">Approval gate</text><text x="350.0" y="230.0" class="s" text-anchor="middle">approval_status:</text><text x="350.0" y="243.0" class="s" text-anchor="middle">pending / approved</text><text x="600.0" y="87.0" class="t" text-anchor="middle">sales</text><text x="600.0" y="102.0" class="s" text-anchor="middle">price, down payment</text><text x="600.0" y="162.0" class="t" text-anchor="middle">contracts</text><text x="600.0" y="177.0" class="s" text-anchor="middle">remaining_amount</text><text x="600.0" y="237.0" class="t" text-anchor="middle">revenues / expenses</text><text x="600.0" y="252.0" class="s" text-anchor="middle">debt_payments</text><text x="600.0" y="310.0" class="t" text-anchor="middle">treasury_transactions</text><text x="600.0" y="325.0" class="s" text-anchor="middle">cashbox ledger</text><text x="600.0" y="338.0" class="s" text-anchor="middle">(reference_type/id)</text><text x="600.0" y="407.0" class="t" text-anchor="middle">shareholder_ledger_entries</text><text x="600.0" y="422.0" class="s" text-anchor="middle">credit / debit</text><text x="910.0" y="87.0" class="t" text-anchor="middle">Dashboard  /app/{p}/dashboard</text><text x="910.0" y="102.0" class="s" text-anchor="middle">KPIs, remaining totals</text><text x="910.0" y="167.0" class="t" text-anchor="middle">Cashbox  /app/{p}/cashbox</text><text x="910.0" y="182.0" class="s" text-anchor="middle">approvedBalance()</text><text x="910.0" y="247.0" class="t" text-anchor="middle">Remaining  /app/{p}/remaining</text><text x="910.0" y="262.0" class="s" text-anchor="middle">Contract::active()</text><text x="910.0" y="327.0" class="t" text-anchor="middle">Settlements  /app/{p}/settlements</text><text x="910.0" y="342.0" class="s" text-anchor="middle">realized / expected profit</text><text x="910.0" y="407.0" class="t" text-anchor="middle">Reports  CSV / Excel / PDF</text><text x="910.0" y="422.0" class="s" text-anchor="middle">ReportController</text><rect x="446.2" y="223.5" width="57.6" height="15" rx="3" class="lblbg"/><text x="475.0" y="234.5" class="lbl" text-anchor="middle">approved</text><rect x="455.5" y="271.0" width="39.0" height="15" rx="3" class="lblbg"/><text x="475.0" y="282.0" class="lbl" text-anchor="middle">posts</text><text x="550" y="520" class="s" text-anchor="middle">Every row carries project_id; BelongsToProject scopes all reads to the current project.</text></svg>
</div>

*Figure — Inputs are validated, pass the approval gate, are written to domain tables and mirrored into the cashbox ledger; dashboards and settlements read the approved data only.* [Open interactive version](diagrams/03-data-flow.html)

**Source files:** `app/Http/Requests/StoreSaleRequest.php`, `app/Http/Controllers/{Sale,Revenue,Expense,Debt,Approvals,Dashboard,Cashbox,Remaining,Settlement,Report}Controller.php`, `app/Services/CashboxLedgerService.php`, `app/Models/Contract.php`.

### 5.4 Sequence diagrams
#### 5.4.1 Staff sign-in
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 598" width="100%" role="img" aria-label="Sequence: staff sign-in and project landing"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1080" height="598" rx="12"/><text x="24" y="34" class="h" text-anchor="start">Sequence: staff sign-in and project landing</text><rect x="40.0" y="52" width="150" height="40" rx="10" class="client" stroke-width="1.5"/><line x1="115.0" y1="92" x2="115.0" y2="574" class="life"/><rect x="210.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="285.0" y1="92" x2="285.0" y2="574" class="life"/><rect x="380.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="455.0" y1="92" x2="455.0" y2="574" class="life"/><rect x="550.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="625.0" y1="92" x2="625.0" y2="574" class="life"/><rect x="720.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="795.0" y1="92" x2="795.0" y2="574" class="life"/><rect x="890.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="965.0" y1="92" x2="965.0" y2="574" class="life"/><path d="M115.0,120 L281.0,120" class="ln" marker-end="url(#ah)"/><path d="M285.0,158 L451.0,158" class="ln" marker-end="url(#ah)"/><path d="M455.0,196 L289.0,196" class="lnd" marker-end="url(#ah)"/><path d="M285.0,234 L621.0,234" class="ln" marker-end="url(#ah)"/><path d="M625.0,272 L289.0,272" class="lnd" marker-end="url(#ah)"/><path d="M285.0,310 L791.0,310" class="ln" marker-end="url(#ah)"/><path d="M795.0,348 L289.0,348" class="lnd" marker-end="url(#ah)"/><path d="M285.0,386 L119.0,386" class="lnd" marker-end="url(#ah)"/><path d="M115.0,424 L961.0,424" class="ln" marker-end="url(#ah)"/><rect x="792.5" y="448" width="345" height="22" rx="4" class="note"/><path d="M965.0,500 L119.0,500" class="lnd" marker-end="url(#ah)"/><text x="115.0" y="76.0" class="t" text-anchor="middle">Browser</text><text x="285.0" y="76.0" class="t" text-anchor="middle">LoginController</text><text x="455.0" y="76.0" class="t" text-anchor="middle">RateLimiter</text><text x="625.0" y="76.0" class="t" text-anchor="middle">Auth (session)</text><text x="795.0" y="76.0" class="t" text-anchor="middle">User/permissions</text><text x="965.0" y="76.0" class="t" text-anchor="middle">Dashboard</text><text x="200.0" y="114" class="lbl" text-anchor="middle">POST /app/login (email, password)</text><text x="370.0" y="152" class="lbl" text-anchor="middle">tooManyAttempts(email|ip)?</text><text x="370.0" y="190" class="lbl" text-anchor="middle">ok (5 tries / 15 min)</text><text x="455.0" y="228" class="lbl" text-anchor="middle">attempt(credentials)</text><text x="455.0" y="266" class="lbl" text-anchor="middle">user + regenerated session</text><text x="540.0" y="304" class="lbl" text-anchor="middle">landingUrlFor(user)</text><text x="540.0" y="342" class="lbl" text-anchor="middle">first allowed module</text><text x="200.0" y="380" class="lbl" text-anchor="middle">302 -&gt; /app/{project}/dashboard</text><text x="540.0" y="418" class="lbl" text-anchor="middle">GET dashboard</text><text x="965.0" y="463" class="lbl" text-anchor="middle">AuthorizeRoutePermission + SyncProjectFromRoute</text><text x="540.0" y="494" class="lbl" text-anchor="middle">HTML (project-scoped KPIs)</text></svg>
</div>

*Figure — Credential login with a per email+IP rate limit; the landing page is the first module the user's role is allowed to open.* [Open interactive version](diagrams/04a-seq-login.html)

**Source files:** `app/Http/Controllers/Auth/LoginController.php`, `routes/web.php` (throttle), `app/Http/Middleware/AuthorizeRoutePermission.php`, `app/Http/Middleware/SyncProjectFromRoute.php`.

#### 5.4.2 Register a sale and approve it
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 598" width="100%" role="img" aria-label="Sequence: register a unit sale and approve it"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1080" height="598" rx="12"/><text x="24" y="34" class="h" text-anchor="start">Sequence: register a unit sale and approve it</text><rect x="40.0" y="52" width="150" height="40" rx="10" class="client" stroke-width="1.5"/><line x1="115.0" y1="92" x2="115.0" y2="574" class="life"/><rect x="210.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="285.0" y1="92" x2="285.0" y2="574" class="life"/><rect x="380.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="455.0" y1="92" x2="455.0" y2="574" class="life"/><rect x="550.0" y="52" width="150" height="40" rx="10" class="db" stroke-width="1.5"/><line x1="625.0" y1="92" x2="625.0" y2="574" class="life"/><rect x="720.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="795.0" y1="92" x2="795.0" y2="574" class="life"/><rect x="890.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="965.0" y1="92" x2="965.0" y2="574" class="life"/><path d="M115.0,120 L281.0,120" class="ln" marker-end="url(#ah)"/><path d="M285.0,158 L451.0,158" class="ln" marker-end="url(#ah)"/><path d="M455.0,196 L289.0,196" class="lnd" marker-end="url(#ah)"/><path d="M285.0,234 L621.0,234" class="ln" marker-end="url(#ah)"/><rect x="487.5" y="258" width="275" height="22" rx="4" class="note"/><path d="M285.0,310 L119.0,310" class="lnd" marker-end="url(#ah)"/><line x1="20" y1="338" x2="1060" y2="338" class="life"/><path d="M795.0,386 L629.0,386" class="ln" marker-end="url(#ah)"/><path d="M795.0,424 L961.0,424" class="ln" marker-end="url(#ah)"/><path d="M965.0,462 L799.0,462" class="lnd" marker-end="url(#ah)"/><rect x="654.0" y="486" width="282" height="22" rx="4" class="note"/><text x="115.0" y="76.0" class="t" text-anchor="middle">Sales user</text><text x="285.0" y="76.0" class="t" text-anchor="middle">SaleController</text><text x="455.0" y="76.0" class="t" text-anchor="middle">StoreSaleRequest</text><text x="625.0" y="76.0" class="t" text-anchor="middle">DB (sales, contracts)</text><text x="795.0" y="76.0" class="t" text-anchor="middle">Approvals</text><text x="965.0" y="76.0" class="t" text-anchor="middle">Cashbox ledger</text><text x="200.0" y="114" class="lbl" text-anchor="middle">POST /app/{p}/sales</text><text x="370.0" y="152" class="lbl" text-anchor="middle">validate unit / floor / amounts</text><text x="370.0" y="190" class="lbl" text-anchor="middle">ok or &#x27;unit already sold&#x27;</text><text x="455.0" y="228" class="lbl" text-anchor="middle">create client, sale, contract</text><text x="625.0" y="273" class="lbl" text-anchor="middle">role=sales -&gt; approval_status=pending</text><text x="200.0" y="304" class="lbl" text-anchor="middle">redirect: pending approval</text><text x="26" y="352" class="gl" text-anchor="start">admin / accountant later</text><text x="710.0" y="380" class="lbl" text-anchor="middle">POST /app/approvals/sale/{id}/approve</text><text x="880.0" y="418" class="lbl" text-anchor="middle">post down payment (reference Sale)</text><text x="880.0" y="456" class="lbl" text-anchor="middle">approvedBalance updated</text><text x="795.0" y="501" class="lbl" text-anchor="middle">reject -&gt; unit becomes available again</text></svg>
</div>

*Figure — Sales staff create pending sales; only an approval posts the down payment into the cashbox. Rejected sales release the unit.* [Open interactive version](diagrams/04b-seq-sale-approval.html)

**Source files:** `app/Http/Controllers/SaleController.php`, `app/Http/Requests/StoreSaleRequest.php`, `app/Http/Controllers/ApprovalsController.php`, `app/Services/CashboxLedgerService.php`.

#### 5.4.3 Settlement and partner shares
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 522" width="100%" role="img" aria-label="Sequence: project settlement and partner shares"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1080" height="522" rx="12"/><text x="24" y="34" class="h" text-anchor="start">Sequence: project settlement and partner shares</text><rect x="40.0" y="52" width="150" height="40" rx="10" class="client" stroke-width="1.5"/><line x1="115.0" y1="92" x2="115.0" y2="498" class="life"/><rect x="210.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="285.0" y1="92" x2="285.0" y2="498" class="life"/><rect x="380.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="455.0" y1="92" x2="455.0" y2="498" class="life"/><rect x="550.0" y="52" width="150" height="40" rx="10" class="core" stroke-width="1.5"/><line x1="625.0" y1="92" x2="625.0" y2="498" class="life"/><rect x="720.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="795.0" y1="92" x2="795.0" y2="498" class="life"/><rect x="890.0" y="52" width="150" height="40" rx="10" class="client" stroke-width="1.5"/><line x1="965.0" y1="92" x2="965.0" y2="498" class="life"/><path d="M115.0,120 L281.0,120" class="ln" marker-end="url(#ah)"/><path d="M285.0,158 L451.0,158" class="ln" marker-end="url(#ah)"/><path d="M285.0,196 L621.0,196" class="ln" marker-end="url(#ah)"/><rect x="175.5" y="220" width="219" height="22" rx="4" class="note"/><rect x="137.0" y="258" width="296" height="22" rx="4" class="note"/><path d="M285.0,310 L791.0,310" class="ln" marker-end="url(#ah)"/><path d="M285.0,342 L325.0,342 L325.0,356 L289.0,356" class="ln" marker-end="url(#ah)"/><path d="M285.0,386 L961.0,386" class="ln" marker-end="url(#ah)"/><path d="M965.0,424 L119.0,424" class="lnd" marker-end="url(#ah)"/><text x="115.0" y="76.0" class="t" text-anchor="middle">Owner</text><text x="285.0" y="76.0" class="t" text-anchor="middle">SettlementController</text><text x="455.0" y="76.0" class="t" text-anchor="middle">Sales/Revenues/Expenses</text><text x="625.0" y="76.0" class="t" text-anchor="middle">Debts + Treasury</text><text x="795.0" y="76.0" class="t" text-anchor="middle">Shareholder ledger</text><text x="965.0" y="76.0" class="t" text-anchor="middle">View</text><text x="200.0" y="114" class="lbl" text-anchor="middle">GET /app/{p}/settlements?from&amp;to</text><text x="370.0" y="152" class="lbl" text-anchor="middle">sum approved sales, collections, expenses</text><text x="455.0" y="190" class="lbl" text-anchor="middle">supplier totals, manual cash in/out</text><text x="285.0" y="235" class="lbl" text-anchor="middle">realized = cash in - cash out</text><text x="285.0" y="273" class="lbl" text-anchor="middle">expected = sales + manual in - all costs</text><text x="540.0" y="304" class="lbl" text-anchor="middle">per partner: capital, credit - debit</text><text x="331.0" y="353" class="lbl" text-anchor="start">weight = pct / sum(pct)</text><text x="625.0" y="380" class="lbl" text-anchor="middle">figures + partner rows</text><text x="540.0" y="418" class="lbl" text-anchor="middle">profit cards, cost tables, due-now per partner</text></svg>
</div>

*Figure — The settlement page aggregates approved figures for the project and splits realized and expected profit by partner percentage.* [Open interactive version](diagrams/04c-seq-settlement.html)

**Source files:** `app/Http/Controllers/SettlementController.php`, `resources/views/settlements/index.blade.php`, `app/Models/ProjectShareholder.php`, `app/Models/ShareholderLedgerEntry.php`.

### 5.5 Lifecycle — Sale
<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 420" width="100%" role="img" aria-label="Lifecycle: Sale (central entity)"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="420" rx="12"/><text x="24" y="34" class="h" text-anchor="start">Lifecycle: Sale (central entity)</text><rect x="30" y="160" width="150" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="240" y="160" width="160" height="70" rx="10" class="shared" stroke-width="1.5"/><rect x="470" y="70" width="170" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="260" width="170" height="70" rx="10" class="warn" stroke-width="1.5"/><rect x="710" y="70" width="170" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="930" y="70" width="150" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="710" y="260" width="170" height="70" rx="10" class="warn" stroke-width="1.5"/><path d="M180,195 L240,195" class="ln" marker-end="url(#ah)"/><path d="M400,180 L430,180 L430,105 L470,105" class="ln" marker-end="url(#ah)"/><path d="M400,210 L430,210 L430,295 L470,295" class="ln" marker-end="url(#ah)"/><path d="M640,105 L710,105" class="ln" marker-end="url(#ah)"/><path d="M880,105 L930,105" class="ln" marker-end="url(#ah)"/><path d="M795,140 L795,260" class="lnd" marker-end="url(#ah)"/><path d="M640,295 L710,295" class="lnd" marker-end="url(#ah)"/><text x="105.0" y="185.0" class="t" text-anchor="middle">Draft form</text><text x="105.0" y="200.0" class="s" text-anchor="middle">unit picked,</text><text x="105.0" y="213.0" class="s" text-anchor="middle">not saved</text><text x="320.0" y="185.0" class="t" text-anchor="middle">Pending</text><text x="320.0" y="200.0" class="s" text-anchor="middle">approval_status</text><text x="320.0" y="213.0" class="s" text-anchor="middle">= pending</text><text x="555.0" y="95.0" class="t" text-anchor="middle">Approved</text><text x="555.0" y="110.0" class="s" text-anchor="middle">down payment</text><text x="555.0" y="123.0" class="s" text-anchor="middle">in cashbox</text><text x="555.0" y="292.0" class="t" text-anchor="middle">Rejected</text><text x="555.0" y="307.0" class="s" text-anchor="middle">unit released</text><text x="795.0" y="95.0" class="t" text-anchor="middle">Collecting</text><text x="795.0" y="110.0" class="s" text-anchor="middle">installments -&gt;</text><text x="795.0" y="123.0" class="s" text-anchor="middle">contract.remaining</text><text x="1005.0" y="102.0" class="t" text-anchor="middle">Fully paid</text><text x="1005.0" y="117.0" class="s" text-anchor="middle">remaining = 0</text><text x="795.0" y="285.0" class="t" text-anchor="middle">Deleted</text><text x="795.0" y="300.0" class="s" text-anchor="middle">admin only, no</text><text x="795.0" y="313.0" class="s" text-anchor="middle">collections, cash &gt;= 0</text><rect x="187.4" y="186.0" width="45.2" height="15" rx="3" class="lblbg"/><text x="210.0" y="197.0" class="lbl" text-anchor="middle">submit</text><rect x="364.0" y="133.5" width="132.0" height="15" rx="3" class="lblbg"/><text x="430.0" y="144.5" class="lbl" text-anchor="middle">approve / admin auto</text><rect x="407.4" y="243.5" width="45.2" height="15" rx="3" class="lblbg"/><text x="430.0" y="254.5" class="lbl" text-anchor="middle">reject</text><rect x="636.9" y="96.0" width="76.2" height="15" rx="3" class="lblbg"/><text x="675.0" y="107.0" class="lbl" text-anchor="middle">installment</text><rect x="863.8" y="96.0" width="82.4" height="15" rx="3" class="lblbg"/><text x="905.0" y="107.0" class="lbl" text-anchor="middle">last payment</text><rect x="772.4" y="191.0" width="45.2" height="15" rx="3" class="lblbg"/><text x="795.0" y="202.0" class="lbl" text-anchor="middle">delete</text><rect x="652.4" y="286.0" width="45.2" height="15" rx="3" class="lblbg"/><text x="675.0" y="297.0" class="lbl" text-anchor="middle">delete</text><text x="550" y="390" class="s" text-anchor="middle">Source: SaleController@store/destroy, ApprovalsController, StoreSaleRequest, Contract::scopeActive</text></svg>
</div>

*Figure — A sale is created pending (sales role) or auto-approved (admin), then accrues collections until fully paid; deletion is guarded.* [Open interactive version](diagrams/05-sale-lifecycle.html)

**Source files:** `app/Models/Sale.php` (`approval_status`), `app/Http/Controllers/SaleController.php`, `app/Http/Controllers/ApprovalsController.php`, `app/Models/Contract.php` (`scopeActive`).

---

<a id="dashboard-analysis"></a>
## 6. Dashboard analysis

### 6.1 Roles
Default grants come from `database/seeders/PermissionSeeder.php`. Admins can widen any user's access with `extra_permissions` (`app/Models/User.php`).

| Role | Intent | Highlights |
|---|---|---|
| **admin** | Owner / partner | Everything, including approvals, users, projects, fund transfers, website and settings |
| **accountant** | Back office | Properties, expenses, cashbox, debts, shareholders, land, reports export; cannot approve |
| **sales** | Sales staff | Sales, collections, CRM, land-trading entries, site sketch; entries go to approval |
| **viewer** | Stakeholder | Read-only views of all project modules |

### 6.2 Modules, pages, actions and access
Generated from `php artisan route:list` and the effective role grants. "Pages" are GET routes, and "Actions" are the state-changing routes. Admins can also grant extra access per user.

| Module | Pages (GET) | Actions | Controller(s) | Roles that can open it |
|---|---|---|---|---|
| Public website | 6 | 1 | `Site\SiteController` | public |
| Activity log | 1 | 0 | `ActivityLogController` | admin, accountant |
| Approvals | 1 | 2 | `ApprovalsController` | admin |
| Areas | 3 | 3 | `AreaController` | admin, accountant |
| Cashbox | 1 | 1 | `CashboxController` | admin, accountant, sales, viewer |
| Clients | 2 | 0 | `ClientController` | admin, accountant, sales, viewer |
| Contracts | 3 | 0 | `ContractController` | admin, accountant, sales, viewer |
| CRM leads | 4 | 4 | `CrmLeadController` | admin, accountant, sales, viewer |
| Project dashboard | 1 | 0 | `DashboardController` | admin, accountant, sales, viewer |
| Debts | 3 | 4 | `DebtController` | admin, accountant, sales, viewer |
| Expense types | 3 | 3 | `ExpenseTypeController` | admin, accountant |
| Expenses | 4 | 3 | `ExpenseController` | admin, accountant, sales, viewer |
| Facings | 3 | 3 | `FacingController` | admin, accountant |
| Fund transfers | 1 | 1 | `FundTransferController` | admin, accountant, sales, viewer |
| Global cashbox | 1 | 0 | `GlobalCashboxController` | admin, accountant, sales, viewer |
| App home / landing | 1 | 0 | `Closure` | admin, accountant, sales, viewer |
| Land cashbox | 1 | 1 | `LandCashboxController` | admin, accountant, sales, viewer |
| Land trading | 5 | 10 | `LandTradingController` | admin, accountant, sales, viewer |
| Lands | 3 | 3 | `LandController` | admin, accountant |
| Login | 1 | 1 | `Auth\LoginController` | guest |
| Logout | 0 | 1 | `Auth\LoginController` |  |
| Projects | 4 | 6 | `Closure`, `ProjectController` | admin, accountant, sales, viewer |
| Properties | 4 | 3 | `PropertyController` | admin, accountant, sales, viewer |
| Remaining | 1 | 0 | `RemainingController` | admin, accountant, sales, viewer |
| Reports | 6 | 0 | `ReportController` | admin, accountant, sales, viewer |
| Revenues | 4 | 3 | `RevenueController` | admin, accountant, sales, viewer |
| Sales | 4 | 3 | `SaleController` | admin, accountant, sales, viewer |
| Settings | 1 | 2 | `SettingController` | admin |
| Settlements | 1 | 0 | `SettlementController` | admin, accountant, sales, viewer |
| Shareholders | 4 | 9 | `ShareholderController`, `ShareholderLedgerController` | admin, accountant, sales, viewer |
| Site sketch | 1 | 2 | `SiteSketchController` | admin, accountant, sales, viewer |
| Tasks | 6 | 4 | `TaskController` | admin, accountant, sales, viewer |
| Users | 3 | 3 | `UserController` | admin |
| Website CMS | 4 | 4 | `WebsiteProjectController`, `WebsiteSettingsController` | admin |

### 6.3 Key screens and what they consume
| Screen | Route(s) consumed | Notes |
|---|---|---|
| Project dashboard | `GET /app/{project}/dashboard` | KPIs, approved remaining, cashbox |
| Sales list / form | `GET /app/{project}/sales`, `GET /app/{project}/sales/create`, `POST /app/{project}/sales` | Unit picker disables sold units; AJAX delete with JSON errors |
| Approvals inbox | `GET /app/approvals`, `POST /app/approvals/{type}/{id}/approve`, `POST /app/approvals/{type}/{id}/reject` | Tabs: sales, revenues, expenses, debt payments, linked treasury, land payments |
| Cashbox | `GET /app/{project}/cashbox`, `POST /app/{project}/cashbox` | Approved balance and manual movements |
| Remaining | `GET /app/{project}/remaining` | Contracts with balance, approved sales only |
| Settlements | `GET /app/{project}/settlements` | Realized / expected profit, partner shares |
| Shareholder ledger | `GET /app/shareholders/{shareholder}`, `POST /app/shareholders/{shareholder}/ledger` | Capital, withdrawals, allocations |
| Site sketch | `GET /app/site-sketch`, `POST /app/site-sketch/properties/{property}/cells` | Visual floor × unit grid |
| Reports | `GET /app/{project}/reports`, `GET /app/{project}/reports/export-excel`, `GET /app/{project}/reports/export-pdf` | CSV / Excel / PDF |
| CRM & tasks | `GET /app/crm/leads`, `POST /app/crm/leads/{lead}/activities`, `GET /app/tasks/mine` | Lead pipeline and follow-ups |

---

<a id="api-reference"></a>
## 7. API reference — endpoint inventory

**Transport.** All routes below are **web routes** that return HTML or redirects, except where they return files (exports, Word contracts) or JSON on AJAX delete.

- **Auth.** Session cookie plus a CSRF token.
- **Middleware.** `/app/*` routes run `auth` and `AuthorizeRoutePermission`. Project routes also run `SyncProjectFromRoute`.
- **Request shapes.** These are defined by the matching `app/Http/Requests/*` class or by inline `validate()` calls in the handler.
- **Not in this table.**
  - `routes/api.php` is not loaded, so none of its routes exist at runtime (see [Decisions](#decisions)).
  - There are no GraphQL or WebSocket endpoints.

| # | Method | Path | Route name | Handler | Access (default roles) |
|---|---|---|---|---|---|
| 1 | GET | `/` | `site.home` | `Site\SiteController@home` | public |
| 2 | GET | `/about` | `site.about` | `Site\SiteController@about` | public |
| 3 | GET | `/contact` | `site.contact` | `Site\SiteController@contact` | public |
| 4 | POST | `/contact` | `site.contact.store` | `Site\SiteController@contactStore` | public |
| 5 | GET | `/projects` | `site.projects` | `Site\SiteController@projects` | public |
| 6 | GET | `/projects/{project}` | `site.projects.show` | `Site\SiteController@projectShow` | public |
| 7 | GET | `/sitemap.xml` | `site.sitemap` | `Site\SiteController@sitemap` | public |
| 8 | GET | `/app/activity-log` | `activity-log.index` | `ActivityLogController@index` | admin, accountant |
| 9 | GET | `/app/approvals` | `approvals.index` | `ApprovalsController@index` | admin |
| 10 | POST | `/app/approvals/{type}/{id}/approve` | `approvals.approve` | `ApprovalsController@approve` | admin |
| 11 | POST | `/app/approvals/{type}/{id}/reject` | `approvals.reject` | `ApprovalsController@reject` | admin |
| 12 | GET | `/app/{project}/areas` | `areas.index` | `AreaController@index` | admin, accountant |
| 13 | POST | `/app/{project}/areas` | `areas.store` | `AreaController@store` | admin, accountant |
| 14 | GET | `/app/{project}/areas/create` | `areas.create` | `AreaController@create` | admin, accountant |
| 15 | DELETE | `/app/{project}/areas/{area}` | `areas.destroy` | `AreaController@destroy` | admin, accountant |
| 16 | PUT/PATCH | `/app/{project}/areas/{area}` | `areas.update` | `AreaController@update` | admin, accountant |
| 17 | GET | `/app/{project}/areas/{area}/edit` | `areas.edit` | `AreaController@edit` | admin, accountant |
| 18 | GET | `/app/{project}/cashbox` | `cashbox.index` | `CashboxController@index` | admin, accountant, sales, viewer |
| 19 | POST | `/app/{project}/cashbox` | `cashbox.store` | `CashboxController@store` | admin, accountant |
| 20 | GET | `/app/{project}/clients` | `clients.index` | `ClientController@index` | admin, accountant, sales, viewer |
| 21 | GET | `/app/{project}/clients/{client}` | `clients.show` | `ClientController@show` | admin, accountant, sales, viewer |
| 22 | GET | `/app/{project}/contracts` | `contracts.index` | `ContractController@index` | admin, accountant, sales, viewer |
| 23 | GET | `/app/{project}/contracts/{contract}` | `contracts.show` | `ContractController@show` | admin, accountant, sales, viewer |
| 24 | GET | `/app/{project}/contracts/{contract}/word` | `contracts.word` | `ContractController@downloadWord` | admin, accountant, sales, viewer |
| 25 | GET | `/app/crm/leads` | `crm-leads.index` | `CrmLeadController@index` | admin, accountant, sales, viewer |
| 26 | POST | `/app/crm/leads` | `crm-leads.store` | `CrmLeadController@store` | admin, sales |
| 27 | GET | `/app/crm/leads/create` | `crm-leads.create` | `CrmLeadController@create` | admin, sales |
| 28 | DELETE | `/app/crm/leads/{lead}` | `crm-leads.destroy` | `CrmLeadController@destroy` | admin, sales |
| 29 | GET | `/app/crm/leads/{lead}` | `crm-leads.show` | `CrmLeadController@show` | admin, accountant, sales, viewer |
| 30 | PUT | `/app/crm/leads/{lead}` | `crm-leads.update` | `CrmLeadController@update` | admin, sales |
| 31 | POST | `/app/crm/leads/{lead}/activities` | `crm-leads.activities.store` | `CrmLeadController@storeActivity` | admin, sales |
| 32 | GET | `/app/crm/leads/{lead}/edit` | `crm-leads.edit` | `CrmLeadController@edit` | admin, sales |
| 33 | GET | `/app/{project}/dashboard` | `dashboard` | `DashboardController@index` | admin, accountant, sales, viewer |
| 34 | GET | `/app/{project}/debts` | `debts.index` | `DebtController@index` | admin, accountant, sales, viewer |
| 35 | POST | `/app/{project}/debts` | `debts.store` | `DebtController@store` | admin, accountant |
| 36 | GET | `/app/{project}/debts/create` | `debts.create` | `DebtController@create` | admin, accountant |
| 37 | DELETE | `/app/{project}/debts/{debt}` | `debts.destroy` | `DebtController@destroy` | admin, accountant |
| 38 | PUT/PATCH | `/app/{project}/debts/{debt}` | `debts.update` | `DebtController@update` | admin, accountant |
| 39 | GET | `/app/{project}/debts/{debt}/edit` | `debts.edit` | `DebtController@edit` | admin, accountant |
| 40 | POST | `/app/{project}/debts/{debt}/pay-from-cashbox` | `debts.pay-from-cashbox` | `DebtController@payFromCashbox` | admin, accountant |
| 41 | GET | `/app/{project}/expense-types` | `expense-types.index` | `ExpenseTypeController@index` | admin, accountant |
| 42 | POST | `/app/{project}/expense-types` | `expense-types.store` | `ExpenseTypeController@store` | admin, accountant |
| 43 | GET | `/app/{project}/expense-types/create` | `expense-types.create` | `ExpenseTypeController@create` | admin, accountant |
| 44 | DELETE | `/app/{project}/expense-types/{expense_type}` | `expense-types.destroy` | `ExpenseTypeController@destroy` | admin, accountant |
| 45 | PUT/PATCH | `/app/{project}/expense-types/{expense_type}` | `expense-types.update` | `ExpenseTypeController@update` | admin, accountant |
| 46 | GET | `/app/{project}/expense-types/{expense_type}/edit` | `expense-types.edit` | `ExpenseTypeController@edit` | admin, accountant |
| 47 | GET | `/app/{project}/expenses` | `expenses.index` | `ExpenseController@index` | admin, accountant, sales, viewer |
| 48 | POST | `/app/{project}/expenses` | `expenses.store` | `ExpenseController@store` | admin, accountant |
| 49 | GET | `/app/{project}/expenses/create` | `expenses.create` | `ExpenseController@create` | admin, accountant |
| 50 | DELETE | `/app/{project}/expenses/{expense}` | `expenses.destroy` | `ExpenseController@destroy` | admin, accountant |
| 51 | GET | `/app/{project}/expenses/{expense}` | `expenses.show` | `ExpenseController@show` | admin, accountant, sales, viewer |
| 52 | PUT/PATCH | `/app/{project}/expenses/{expense}` | `expenses.update` | `ExpenseController@update` | admin, accountant |
| 53 | GET | `/app/{project}/expenses/{expense}/edit` | `expenses.edit` | `ExpenseController@edit` | admin, accountant |
| 54 | GET | `/app/{project}/facings` | `facings.index` | `FacingController@index` | admin, accountant |
| 55 | POST | `/app/{project}/facings` | `facings.store` | `FacingController@store` | admin, accountant |
| 56 | GET | `/app/{project}/facings/create` | `facings.create` | `FacingController@create` | admin, accountant |
| 57 | DELETE | `/app/{project}/facings/{facing}` | `facings.destroy` | `FacingController@destroy` | admin, accountant |
| 58 | PUT/PATCH | `/app/{project}/facings/{facing}` | `facings.update` | `FacingController@update` | admin, accountant |
| 59 | GET | `/app/{project}/facings/{facing}/edit` | `facings.edit` | `FacingController@edit` | admin, accountant |
| 60 | GET | `/app/fund-transfers` | `fund-transfers.index` | `FundTransferController@index` | admin, accountant, sales, viewer |
| 61 | POST | `/app/fund-transfers` | `fund-transfers.store` | `FundTransferController@store` | admin, accountant |
| 62 | GET | `/app/global-cashbox` | `global-cashbox.index` | `GlobalCashboxController@index` | admin, accountant, sales, viewer |
| 63 | GET | `/app` | `home` | `Closure` | admin, accountant, sales, viewer |
| 64 | GET | `/app/land-cashbox` | `land-cashbox.index` | `LandCashboxController@index` | admin, accountant, sales, viewer |
| 65 | POST | `/app/land-cashbox` | `land-cashbox.store` | `LandCashboxController@store` | admin, accountant |
| 66 | GET | `/app/land-trading` | `land-trading.index` | `LandTradingController@index` | admin, accountant, sales, viewer |
| 67 | POST | `/app/land-trading` | `land-trading.store` | `LandTradingController@store` | admin, accountant, sales |
| 68 | GET | `/app/land-trading/create` | `land-trading.create` | `LandTradingController@create` | admin, accountant, sales |
| 69 | GET | `/app/land-trading/sales` | `land-trading.sales` | `LandTradingController@sales` | admin, accountant, sales, viewer |
| 70 | DELETE | `/app/land-trading/{parcel}` | `land-trading.destroy` | `LandTradingController@destroy` | admin, accountant, sales |
| 71 | GET | `/app/land-trading/{parcel}` | `land-trading.show` | `LandTradingController@show` | admin, accountant, sales, viewer |
| 72 | PUT | `/app/land-trading/{parcel}` | `land-trading.update` | `LandTradingController@update` | admin, accountant, sales |
| 73 | POST | `/app/land-trading/{parcel}/adopt-plan` | `land-trading.adopt-plan` | `LandTradingController@adoptPlan` | admin, accountant, sales |
| 74 | GET | `/app/land-trading/{parcel}/edit` | `land-trading.edit` | `LandTradingController@edit` | admin, accountant, sales |
| 75 | POST | `/app/land-trading/{parcel}/parts` | `land-trading.parts.store` | `LandTradingController@storePart` | admin, accountant, sales |
| 76 | DELETE | `/app/land-trading/{parcel}/parts/{part}` | `land-trading.parts.destroy` | `LandTradingController@destroyPart` | admin, accountant, sales |
| 77 | PUT | `/app/land-trading/{parcel}/parts/{part}` | `land-trading.parts.update` | `LandTradingController@updatePart` | admin, accountant, sales |
| 78 | POST | `/app/land-trading/{parcel}/payments` | `land-trading.payments.store` | `LandTradingController@storePayment` | admin, accountant, sales |
| 79 | DELETE | `/app/land-trading/{parcel}/payments/{payment}` | `land-trading.payments.destroy` | `LandTradingController@destroyPayment` | admin, accountant, sales |
| 80 | POST | `/app/land-trading/{parcel}/payments/{payment}/distribute` | `land-trading.payments.distribute` | `LandTradingController@distributePayment` | admin, accountant, sales |
| 81 | GET | `/app/{project}/lands` | `lands.index` | `LandController@index` | admin, accountant |
| 82 | POST | `/app/{project}/lands` | `lands.store` | `LandController@store` | admin, accountant |
| 83 | GET | `/app/{project}/lands/create` | `lands.create` | `LandController@create` | admin, accountant |
| 84 | DELETE | `/app/{project}/lands/{land}` | `lands.destroy` | `LandController@destroy` | admin, accountant |
| 85 | PUT/PATCH | `/app/{project}/lands/{land}` | `lands.update` | `LandController@update` | admin, accountant |
| 86 | GET | `/app/{project}/lands/{land}/edit` | `lands.edit` | `LandController@edit` | admin, accountant |
| 87 | GET | `/app/login` | `login` | `Auth\LoginController@create` | guest |
| 88 | POST | `/app/login` | `—` | `Auth\LoginController@store` | guest |
| 89 | POST | `/app/logout` | `logout` | `Auth\LoginController@destroy` | admin |
| 90 | GET | `/app/projects` | `projects.index` | `ProjectController@index` | admin, accountant, sales, viewer |
| 91 | POST | `/app/projects` | `projects.store` | `ProjectController@store` | admin |
| 92 | POST | `/app/projects/{draftProject}/restore` | `projects.restore` | `ProjectController@restore` | admin |
| 93 | POST | `/app/projects/{managedProject}/draft` | `projects.draft` | `ProjectController@toDraft` | admin |
| 94 | DELETE | `/app/projects/{project}` | `projects.destroy` | `ProjectController@destroy` | admin |
| 95 | GET | `/app/projects/{project}` | `projects.landing` | `Closure` | admin, accountant, sales, viewer |
| 96 | PUT | `/app/projects/{project}` | `projects.update` | `ProjectController@update` | admin |
| 97 | POST | `/app/projects/{project}/adopt-plan` | `projects.adopt-plan` | `ProjectController@adoptPlan` | admin |
| 98 | GET | `/app/projects/{project}/contract-template` | `projects.contract-template` | `ProjectController@downloadContractTemplate` | admin |
| 99 | GET | `/app/projects/{project}/edit` | `projects.edit` | `ProjectController@edit` | admin |
| 100 | GET | `/app/{project}/properties` | `properties.index` | `PropertyController@index` | admin, accountant, sales, viewer |
| 101 | POST | `/app/{project}/properties` | `properties.store` | `PropertyController@store` | admin, accountant |
| 102 | GET | `/app/{project}/properties/create` | `properties.create` | `PropertyController@create` | admin, accountant |
| 103 | DELETE | `/app/{project}/properties/{property}` | `properties.destroy` | `PropertyController@destroy` | admin, accountant |
| 104 | GET | `/app/{project}/properties/{property}` | `properties.show` | `PropertyController@show` | admin, accountant, sales, viewer |
| 105 | PUT/PATCH | `/app/{project}/properties/{property}` | `properties.update` | `PropertyController@update` | admin, accountant |
| 106 | GET | `/app/{project}/properties/{property}/edit` | `properties.edit` | `PropertyController@edit` | admin, accountant |
| 107 | GET | `/app/{project}/remaining` | `remaining.index` | `RemainingController@index` | admin, accountant, sales, viewer |
| 108 | GET | `/app/{project}/reports` | `reports.index` | `ReportController@index` | admin, accountant, sales, viewer |
| 109 | GET | `/app/{project}/reports/export` | `reports.export` | `ReportController@exportCsv` | admin, accountant |
| 110 | GET | `/app/{project}/reports/export-excel` | `reports.export-excel` | `ReportController@exportExcel` | admin, accountant |
| 111 | GET | `/app/{project}/reports/export-pdf` | `reports.export-pdf` | `ReportController@exportPdf` | admin, accountant |
| 112 | GET | `/app/{project}/reports/export_excel` | `reports.export_excel` | `ReportController@exportExcel` | admin, accountant |
| 113 | GET | `/app/{project}/reports/export_pdf` | `reports.export_pdf` | `ReportController@exportPdf` | admin, accountant |
| 114 | GET | `/app/{project}/revenues` | `revenues.index` | `RevenueController@index` | admin, accountant, sales, viewer |
| 115 | POST | `/app/{project}/revenues` | `revenues.store` | `RevenueController@store` | admin, accountant, sales |
| 116 | GET | `/app/{project}/revenues/create` | `revenues.create` | `RevenueController@create` | admin, accountant, sales |
| 117 | DELETE | `/app/{project}/revenues/{revenue}` | `revenues.destroy` | `RevenueController@destroy` | admin, accountant, sales |
| 118 | GET | `/app/{project}/revenues/{revenue}` | `revenues.show` | `RevenueController@show` | admin, accountant, sales, viewer |
| 119 | PUT/PATCH | `/app/{project}/revenues/{revenue}` | `revenues.update` | `RevenueController@update` | admin, accountant, sales |
| 120 | GET | `/app/{project}/revenues/{revenue}/edit` | `revenues.edit` | `RevenueController@edit` | admin, accountant, sales |
| 121 | GET | `/app/{project}/sales` | `sales.index` | `SaleController@index` | admin, accountant, sales, viewer |
| 122 | POST | `/app/{project}/sales` | `sales.store` | `SaleController@store` | admin, sales |
| 123 | GET | `/app/{project}/sales/create` | `sales.create` | `SaleController@create` | admin, sales |
| 124 | DELETE | `/app/{project}/sales/{sale}` | `sales.destroy` | `SaleController@destroy` | admin, sales |
| 125 | GET | `/app/{project}/sales/{sale}` | `sales.show` | `SaleController@show` | admin, accountant, sales, viewer |
| 126 | PUT/PATCH | `/app/{project}/sales/{sale}` | `sales.update` | `SaleController@update` | admin, sales |
| 127 | GET | `/app/{project}/sales/{sale}/edit` | `sales.edit` | `SaleController@edit` | admin, sales |
| 128 | GET | `/app/{project}/settings` | `settings.edit` | `SettingController@edit` | admin |
| 129 | PUT | `/app/{project}/settings` | `settings.update` | `SettingController@update` | admin |
| 130 | POST | `/app/{project}/settings/send-available-units-report` | `settings.send-available-units-report` | `SettingController@sendAvailableUnitsReportNow` | admin |
| 131 | GET | `/app/{project}/settlements` | `settlements.index` | `SettlementController@index` | admin, accountant, sales, viewer |
| 132 | GET | `/app/shareholders` | `shareholders.index` | `ShareholderController@index` | admin, accountant, sales, viewer |
| 133 | POST | `/app/shareholders` | `shareholders.store` | `ShareholderController@store` | admin, accountant |
| 134 | GET | `/app/shareholders/create` | `shareholders.create` | `ShareholderController@create` | admin, accountant |
| 135 | DELETE | `/app/shareholders/{shareholder}` | `shareholders.destroy` | `ShareholderController@destroy` | admin, accountant |
| 136 | GET | `/app/shareholders/{shareholder}` | `shareholders.show` | `ShareholderController@show` | admin, accountant, sales, viewer |
| 137 | PUT/PATCH | `/app/shareholders/{shareholder}` | `shareholders.update` | `ShareholderController@update` | admin, accountant |
| 138 | GET | `/app/shareholders/{shareholder}/edit` | `shareholders.edit` | `ShareholderController@edit` | admin, accountant |
| 139 | PUT | `/app/shareholders/{shareholder}/funding` | `shareholders.funding.update` | `ShareholderController@updateFunding` | admin, accountant |
| 140 | POST | `/app/shareholders/{shareholder}/lands` | `shareholders.lands.attach` | `ShareholderController@attachLand` | admin, accountant |
| 141 | POST | `/app/shareholders/{shareholder}/ledger` | `shareholders.ledger.store` | `ShareholderLedgerController@store` | admin, accountant |
| 142 | DELETE | `/app/shareholders/{shareholder}/ledger/{ledger}` | `shareholders.ledger.destroy` | `ShareholderLedgerController@destroy` | admin, accountant |
| 143 | POST | `/app/shareholders/{shareholder}/ledger/{ledger}/allocate` | `shareholders.ledger.allocate` | `ShareholderLedgerController@allocate` | admin, accountant |
| 144 | POST | `/app/shareholders/{shareholder}/projects` | `shareholders.projects.attach` | `ShareholderController@attachProject` | admin, accountant |
| 145 | GET | `/app/site-sketch` | `site-sketch.index` | `SiteSketchController@index` | admin, accountant, sales, viewer |
| 146 | POST | `/app/site-sketch/properties/{property}/cells` | `site-sketch.cells.update` | `SiteSketchController@updateCell` | admin, sales |
| 147 | POST | `/app/site-sketch/properties/{property}/reset` | `site-sketch.reset` | `SiteSketchController@reset` | admin, sales |
| 148 | GET | `/app/tasks` | `tasks.index` | `TaskController@index` | admin, accountant, sales, viewer |
| 149 | POST | `/app/tasks` | `tasks.store` | `TaskController@store` | admin |
| 150 | GET | `/app/tasks/create` | `tasks.create` | `TaskController@create` | admin |
| 151 | GET | `/app/tasks/mine` | `tasks.mine` | `TaskController@mine` | admin, accountant, sales, viewer |
| 152 | DELETE | `/app/tasks/{task}` | `tasks.destroy` | `TaskController@destroy` | admin |
| 153 | GET | `/app/tasks/{task}` | `tasks.show` | `TaskController@show` | admin, accountant, sales, viewer |
| 154 | PUT | `/app/tasks/{task}` | `tasks.update` | `TaskController@update` | admin |
| 155 | GET | `/app/tasks/{task}/edit` | `tasks.edit` | `TaskController@edit` | admin |
| 156 | POST | `/app/tasks/{task}/updates` | `tasks.updates.store` | `TaskController@storeUpdate` | admin, accountant, sales, viewer |
| 157 | GET | `/app/tasks/{task}/updates/{update}/attachment` | `tasks.updates.attachment` | `TaskController@downloadUpdateAttachment` | admin, accountant, sales, viewer |
| 158 | GET | `/app/users` | `users.index` | `UserController@index` | admin |
| 159 | POST | `/app/users` | `users.store` | `UserController@store` | admin |
| 160 | GET | `/app/users/create` | `users.create` | `UserController@create` | admin |
| 161 | DELETE | `/app/users/{user}` | `users.destroy` | `UserController@destroy` | admin |
| 162 | PUT/PATCH | `/app/users/{user}` | `users.update` | `UserController@update` | admin |
| 163 | GET | `/app/users/{user}/edit` | `users.edit` | `UserController@edit` | admin |
| 164 | GET | `/app/website` | `website.index` | `WebsiteSettingsController@index` | admin |
| 165 | PUT | `/app/website/brand` | `website.brand.update` | `WebsiteSettingsController@updateBrand` | admin |
| 166 | GET | `/app/website/projects` | `website.projects.index` | `WebsiteProjectController@index` | admin |
| 167 | PUT | `/app/website/projects/{project}` | `website.projects.update` | `WebsiteProjectController@update` | admin |
| 168 | GET | `/app/website/projects/{project}/edit` | `website.projects.edit` | `WebsiteProjectController@edit` | admin |
| 169 | POST | `/app/website/projects/{project}/toggle` | `website.projects.toggle` | `WebsiteProjectController@quickToggle` | admin |
| 170 | GET | `/app/website/{section}` | `website.edit` | `WebsiteSettingsController@edit` | admin |
| 171 | PUT | `/app/website/{section}` | `website.update` | `WebsiteSettingsController@update` | admin |

*171 application routes listed. Framework internals omitted: `GET /up` (health check) and the local-disk `storage/{path}` routes.*

---

<a id="mobile-opportunity"></a>
## 8. Mobile opportunity analysis

### 8.1 What belongs on mobile
| Capability | Why on mobile | Primary persona |
|---|---|---|
| Approvals (sales, revenues, expenses, debt and land payments, linked treasury) | Time-critical, binary decision, owner is rarely at a desk | Owner |
| Cashbox balance and latest movements | Glanceable and checked many times a day | Owner, accountant |
| Settlement and partner "due now" | Partners ask "what is my share?"; a read-only summary suits mobile | Owner, partners |
| Unit availability grid | Used on site in front of the buyer | Sales |
| Register a sale (pending) | Captures the deal at the moment of sale; camera for the ID | Sales |
| CRM leads, activities, tasks | Calls and WhatsApp happen on the phone | Sales |
| Record a collection (cash received in the field) | Receipt photo and immediate pending entry | Sales, accountant |
| Buyer: my units, paid / remaining, schedule, receipts | Self-service reduces calls to the office | Buyer |

### 8.2 What should stay web-only
- **Project, property and area set-up**: wide forms with many fields, modelling floors, mezzanines and apartment models.
- **Land-trading plan adoption, parts and payment distribution**: complex, multi-step and rarely urgent.
- **Bulk reports and exports, the activity log, user and permission management, and website content editing.**
- **Fund transfers between project cashboxes**: high risk; keep them behind the desktop and approval flow.

### 8.3 Mobile-native opportunities
- **Push notifications.**
  - "New sale pending approval".
  - "Instalment due in 3 days / overdue".
  - "Cashbox below threshold".
  - "Task assigned".
- **Biometric confirmation** before approve, reject or withdraw.
- **Camera.**
  - National-ID capture, with optional OCR.
  - Receipt and cheque photos attached to revenues and expenses.
  - Site progress photos per property.
- **Offline cache** of the unit-availability grid and lead list for basement or rural sites, with queued submissions.
- **WhatsApp / phone deep links** from leads and clients.
- **Share sheet** for PDF receipts and contracts.
- **Home-screen widget** showing the cashbox balance and pending-approval count.
- **GPS stamp** on field collections (*Phase 2, compliance value*).

### 8.4 API gap analysis (summary)
Today **no capability has a JSON endpoint**; every proposed feature maps to existing *logic* behind a web route.

- **Recommended foundation.** Add a versioned `/api/v1` with Laravel Sanctum personal access tokens and the existing `SetApiCurrentProject` middleware (`X-Project-Id` header). Thin API controllers should call the same services and form requests the web controllers use.
- **New data concepts required.**
  - `device_tokens` table for push.
  - `installment_schedules` (or a materialised schedule) for buyers and overdue alerts. Today the plan lives only as fields on the sale.
  - A client-auth guard with phone OTP.
  - `attachments` for photos on revenues and expenses.
- The detailed mapping is in each app's table in §9.

### 8.5 SWOT
| Strengths | Weaknesses |
|---|---|
| Deep, Egypt-specific developer finance: partners, settlement, land trading | No usable API; web-only, server-rendered UI |
| Maker–checker approvals and a cashbox ledger already enforce integrity | Single-tenant deployment; no billing |
| Arabic documents (Word / PDF / Excel) built in | Shared hosting: limited workers, no queue daemon, no websockets |
| Recently hardened and covered by flow tests | Instalment schedule not stored as rows; repository README is still the Laravel default |

| Opportunities | Threats |
|---|---|
| Mobile approvals and buyer portal are clear differentiators against spreadsheets and freelance builds | General ERPs (Odoo, Zoho) moving down-market with local partners |
| A market under cost pressure values cash and receivables control | Market correction can shrink small-developer budgets |
| WhatsApp-centric workflows can be productised | Data-protection obligations (Egypt Law 151/2020) once buyers log in |

<a id="risks"></a>
### 8.6 Risks
- **Technical.**
  - Building the API without duplicating logic. *Mitigation:* reuse the form requests and services, and add API feature tests.
  - Session-project scoping leaking into token requests. *Mitigation:* use `SetApiCurrentProject` only, and never read the session.
  - Shared-hosting limits for queues and push fan-out. *Mitigation:* a database queue run by cron, or a small VPS later.
- **Business.**
  - Single-tenant installs do not scale commercially. *Mitigation:* decide early between a SaaS (multi-tenant) and a managed-install model.
  - The buyer app adds support load. *Mitigation:* keep it read-only.
- **Compliance.**
  - Storing national-ID images and buyer phone numbers. *Mitigation:* explicit consent, encrypted storage, retention policy, and alignment with Egypt's Personal Data Protection Law No. 151/2020 (*assumption: legal review required*).
  - Financial approvals on a phone. *Mitigation:* biometric step-up and an audit trail through the activity log.

---

<a id="mobile-apps"></a>
## 9. Mobile app proposals

**Decision:** three apps. Each targets a persona whose context, security model and release cadence differ:

- **Staff apps.** The owner and sales staff use the same staff accounts and permission system, but their home screens and risk levels differ.
- **Buyer app.** Buyers need a separate identity and guard.

A single "super-app" with role switching is a valid alternative for Apps 1 and 2 (see [Decisions](#decisions)).

**Shared foundation (prerequisite, size M).**

- `/api/v1` built on Sanctum.
- Endpoints `POST /api/v1/auth/login`, `POST /api/v1/auth/logout`, `GET /api/v1/me` and `GET /api/v1/projects`.
- `X-Project-Id` handled by the existing `SetApiCurrentProject` middleware.
- Permission checks that reuse `config/route-permissions.php` slugs.
- JSON resources, rate limiting, a `device_tokens` table, and an FCM notification channel.

<a id="app-owner"></a>
### 9.1 App 1 — Owner & Partner companion

- **Persona:** the owner or partner (admin role), often away from the office. Optionally, partners get read-only access to their own settlement figures.
- **Value proposition:** the owner approves money movements in seconds from a push notification, sees every project's cash and receivables at a glance, and knows what each partner is owed today. All of this logic already runs the dashboard, so the app adds speed and reach rather than new accounting rules.

**Features**

| MVP | Phase 2 | Future |
|---|---|---|
| Login + biometric unlock; project picker | Partner read-only accounts (own share only) | Home-screen widget (balance, pending count) |
| Home KPIs (cashbox, remaining, sales count) | Cashbox movement feed with filters | Voice summary / AI digest |
| Approvals inbox + approve / reject with reason | Low-balance and large-amount alerts | Multi-company switcher (if SaaS) |
| Push on new pending items | Settlement PDF share | |
| Settlement summary per project | | |

**Endpoint mapping**

| Feature | Existing endpoint (web, logic to reuse) | Mobile endpoint |
|---|---|---|
| Login | `POST /app/login` — `Auth\LoginController@store` | **NEW** `POST /api/v1/auth/login` → `{token, user, permissions}` |
| Project picker | `GET /app/projects` — `ProjectController@index` | **NEW** `GET /api/v1/projects` |
| Home KPIs | `GET /app/{project}/dashboard` — `DashboardController@index` | **NEW** `GET /api/v1/projects/{p}/summary` → `{cashbox, remaining, sales_count, pending_approvals}` |
| Approvals list | `GET /app/approvals` — `ApprovalsController@index` | **NEW** `GET /api/v1/approvals?type=` |
| Approve | `POST /app/approvals/{type}/{id}/approve` — `ApprovalsController@approve` | **NEW** `POST /api/v1/approvals/{type}/{id}/approve` |
| Reject | `POST /app/approvals/{type}/{id}/reject` — `ApprovalsController@reject` | **NEW** `POST /api/v1/approvals/{type}/{id}/reject` `{reason}` |
| Settlement | `GET /app/{project}/settlements` — `SettlementController@index` | **NEW** `GET /api/v1/projects/{p}/settlement?from&to` (extract figures into a `SettlementService`) |
| Cashbox feed | `GET /app/{project}/cashbox` — `CashboxController@index` | **NEW** `GET /api/v1/projects/{p}/cashbox?page` |
| Push registration | — | **NEW** `POST /api/v1/devices` `{platform, token}` |

<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 570" width="100%" role="img" aria-label="App 1 - Owner &amp; Partner companion: architecture"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="570" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 1 - Owner &amp; Partner companion: architecture</text><rect x="260" y="50" width="560" height="450" rx="14" class="grp"/><rect x="30" y="220" width="190" height="90" rx="10" class="new" stroke-width="1.5"/><rect x="30" y="90" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="280" y="90" width="240" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="560" y="90" width="240" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="280" y="190" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="254" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="318" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="382" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="560" y="190" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="254" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="318" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="382" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="860" y="120" width="210" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="860" y="240" width="210" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="860" y="360" width="210" height="70" rx="10" class="db" stroke-width="1.5"/><path d="M220,125 L280,125" class="ln" marker-end="url(#ah)"/><path d="M220,250 L540,250 L540,125 L560,125" class="ln" marker-end="url(#ah)"/><path d="M520,140 L540,140 L540,175 L400,175 L400,190" class="ln" marker-end="url(#ah)"/><path d="M680,160 L680,175 L420,175" class="ln" marker-end="url(#ah)"/><path d="M800,155 L860,155" class="ln" marker-end="url(#ah)"/><path d="M800,275 L860,275" class="ln" marker-end="url(#ah)"/><path d="M1070,275 L1088,275 L1088,512 L125,512 L125,310" class="lnd" marker-end="url(#ah)"/><rect x="30" y="532" width="18" height="12" rx="3" class="shared" stroke-width="1.5"/><rect x="230" y="532" width="18" height="12" rx="3" class="new" stroke-width="1.5"/><rect x="430" y="532" width="18" height="12" rx="3" class="db" stroke-width="1.5"/><rect x="630" y="532" width="18" height="12" rx="3" class="client" stroke-width="1.5"/><text x="125.0" y="262.0" class="t" text-anchor="middle">Owner app</text><text x="125.0" y="277.0" class="s" text-anchor="middle">Flutter, iOS + Android</text><text x="125.0" y="122.0" class="t" text-anchor="middle">Web dashboard</text><text x="125.0" y="137.0" class="s" text-anchor="middle">existing Blade UI</text><text x="272" y="66" class="gl" text-anchor="start">SAME LARAVEL BACKEND (ELMANAREGY.COM)</text><text x="400.0" y="122.0" class="t" text-anchor="middle">Web routes  /app/*</text><text x="400.0" y="137.0" class="s" text-anchor="middle">session auth (existing)</text><text x="680.0" y="115.0" class="t" text-anchor="middle">REST API  /api/v1/*</text><text x="680.0" y="130.0" class="s" text-anchor="middle">Sanctum tokens,</text><text x="680.0" y="143.0" class="s" text-anchor="middle">X-Project-Id  (NEW)</text><text x="400.0" y="214.0" class="t" text-anchor="middle">ApprovalsController logic</text><text x="400.0" y="229.0" class="s" text-anchor="middle">approve / reject</text><text x="400.0" y="278.0" class="t" text-anchor="middle">CashboxBalanceService</text><text x="400.0" y="293.0" class="s" text-anchor="middle">approvedBalance</text><text x="400.0" y="342.0" class="t" text-anchor="middle">Settlement figures</text><text x="400.0" y="357.0" class="s" text-anchor="middle">SettlementController</text><text x="400.0" y="406.0" class="t" text-anchor="middle">Dashboard KPIs</text><text x="400.0" y="421.0" class="s" text-anchor="middle">DashboardController</text><text x="680.0" y="214.0" class="t" text-anchor="middle">/api/v1/approvals</text><text x="680.0" y="229.0" class="s" text-anchor="middle">list, approve, reject</text><text x="680.0" y="278.0" class="t" text-anchor="middle">/api/v1/projects/{p}/summary</text><text x="680.0" y="293.0" class="s" text-anchor="middle">KPIs + cashbox</text><text x="680.0" y="342.0" class="t" text-anchor="middle">/api/v1/projects/{p}/settlement</text><text x="680.0" y="357.0" class="s" text-anchor="middle">JSON figures</text><text x="680.0" y="406.0" class="t" text-anchor="middle">/api/v1/devices</text><text x="680.0" y="421.0" class="s" text-anchor="middle">push token register</text><text x="965.0" y="145.0" class="t" text-anchor="middle">MySQL</text><text x="965.0" y="160.0" class="s" text-anchor="middle">same schema,</text><text x="965.0" y="173.0" class="s" text-anchor="middle">project_id scoped</text><text x="965.0" y="272.0" class="t" text-anchor="middle">Push (FCM / APNs)</text><text x="965.0" y="287.0" class="s" text-anchor="middle">NEW integration</text><text x="965.0" y="392.0" class="t" text-anchor="middle">Storage</text><text x="965.0" y="407.0" class="s" text-anchor="middle">receipts, photos</text><rect x="230.5" y="116.0" width="39.0" height="15" rx="3" class="lblbg"/><text x="250.0" y="127.0" class="lbl" text-anchor="middle">HTTPS</text><rect x="335.7" y="241.0" width="88.60000000000001" height="15" rx="3" class="lblbg"/><text x="380.0" y="252.0" class="lbl" text-anchor="middle">HTTPS + token</text><rect x="453.7" y="166.0" width="88.60000000000001" height="15" rx="3" class="lblbg"/><text x="498.0" y="177.0" class="lbl" text-anchor="middle">same services</text><rect x="816.7" y="146.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="830.0" y="157.0" class="lbl" text-anchor="middle">SQL</text><rect x="807.4" y="266.0" width="45.2" height="15" rx="3" class="lblbg"/><text x="830.0" y="277.0" class="lbl" text-anchor="middle">notify</text><rect x="590.1" y="503.0" width="32.8" height="15" rx="3" class="lblbg"/><text x="606.5" y="514.0" class="lbl" text-anchor="middle">push</text><text x="54" y="542" class="s" text-anchor="start">Shared (existing) service</text><text x="254" y="542" class="s" text-anchor="start">New / proposed</text><text x="454" y="542" class="s" text-anchor="start">Storage</text><text x="654" y="542" class="s" text-anchor="start">Existing client</text></svg>
</div>

*Figure — The owner app reuses the approval, cashbox and settlement logic already used by the dashboard, exposed through a new token-authenticated JSON layer.* [Open interactive version](diagrams/06a-owner-arch.html)


<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 910 560" width="100%" role="img" aria-label="App 1 primary flow: approve a pending sale from a push notification"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="910" height="560" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 1 primary flow: approve a pending sale from a push notification</text><rect x="40.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="115.0" y1="92" x2="115.0" y2="536" class="life"/><rect x="210.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="285.0" y1="92" x2="285.0" y2="536" class="life"/><rect x="380.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="455.0" y1="92" x2="455.0" y2="536" class="life"/><rect x="550.0" y="52" width="150" height="40" rx="10" class="db" stroke-width="1.5"/><line x1="625.0" y1="92" x2="625.0" y2="536" class="life"/><rect x="720.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="795.0" y1="92" x2="795.0" y2="536" class="life"/><rect x="508.5" y="106" width="233" height="22" rx="4" class="note"/><path d="M285.0,158 L791.0,158" class="ln" marker-end="url(#ah)"/><path d="M795.0,196 L119.0,196" class="lnd" marker-end="url(#ah)"/><path d="M115.0,234 L281.0,234" class="ln" marker-end="url(#ah)"/><path d="M285.0,272 L119.0,272" class="lnd" marker-end="url(#ah)"/><path d="M115.0,304 L155.0,304 L155.0,318 L119.0,318" class="ln" marker-end="url(#ah)"/><path d="M115.0,348 L281.0,348" class="ln" marker-end="url(#ah)"/><path d="M285.0,386 L451.0,386" class="ln" marker-end="url(#ah)"/><path d="M455.0,424 L621.0,424" class="ln" marker-end="url(#ah)"/><path d="M285.0,462 L119.0,462" class="lnd" marker-end="url(#ah)"/><text x="115.0" y="76.0" class="t" text-anchor="middle">Owner app</text><text x="285.0" y="76.0" class="t" text-anchor="middle">API /api/v1</text><text x="455.0" y="76.0" class="t" text-anchor="middle">Approvals service</text><text x="625.0" y="76.0" class="t" text-anchor="middle">DB</text><text x="795.0" y="76.0" class="t" text-anchor="middle">Push (FCM)</text><text x="625.0" y="121" class="lbl" text-anchor="middle">sales user saves a pending sale</text><text x="540.0" y="152" class="lbl" text-anchor="middle">event: approval.created</text><text x="455.0" y="190" class="lbl" text-anchor="middle">notification: new sale 1,200,000</text><text x="200.0" y="228" class="lbl" text-anchor="middle">GET /approvals/sale/{id}</text><text x="200.0" y="266" class="lbl" text-anchor="middle">sale, unit, client, amounts</text><text x="161.0" y="315" class="lbl" text-anchor="start">biometric confirm</text><text x="200.0" y="342" class="lbl" text-anchor="middle">POST /approvals/sale/{id}/approve</text><text x="370.0" y="380" class="lbl" text-anchor="middle">approve() + assertCanSpend</text><text x="540.0" y="418" class="lbl" text-anchor="middle">status=approved, post cashbox row</text><text x="200.0" y="456" class="lbl" text-anchor="middle">200 + new cashbox balance</text></svg>
</div>

*Figure — Push-to-approve loop that removes the need to open the web dashboard for routine approvals.* [Open interactive version](diagrams/06b-owner-seq.html)


<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 420" width="100%" role="img" aria-label="App 1 screen flow"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="420" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 1 screen flow</text><rect x="30" y="160" width="170" height="60" rx="10" class="client" stroke-width="1.5"/><rect x="250" y="160" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="160" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="690" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="910" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="690" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="910" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="320" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><path d="M200,190.0 L250,190.0" class="ln" marker-end="url(#ah)"/><path d="M420,190.0 L470,190.0" class="ln" marker-end="url(#ah)"/><path d="M555.0,160 L555.0,140.0 L775.0,140.0 L775.0,120" class="ln" marker-end="url(#ah)"/><path d="M860,90.0 L910,90.0" class="ln" marker-end="url(#ah)"/><path d="M555.0,220 L555.0,240.0 L775.0,240.0 L775.0,260" class="ln" marker-end="url(#ah)"/><path d="M860,290.0 L910,290.0" class="ln" marker-end="url(#ah)"/><path d="M555.0,220 L555.0,270.0 L555.0,270.0 L555.0,320" class="ln" marker-end="url(#ah)"/><text x="115.0" y="187.0" class="t" text-anchor="middle">Login</text><text x="115.0" y="202.0" class="s" text-anchor="middle">email + biometric</text><text x="335.0" y="187.0" class="t" text-anchor="middle">Project picker</text><text x="335.0" y="202.0" class="s" text-anchor="middle">my projects</text><text x="555.0" y="187.0" class="t" text-anchor="middle">Home KPIs</text><text x="555.0" y="202.0" class="s" text-anchor="middle">cashbox, remaining</text><text x="775.0" y="80.0" class="t" text-anchor="middle">Approvals inbox</text><text x="775.0" y="95.0" class="s" text-anchor="middle">sales / revenues /</text><text x="775.0" y="108.0" class="s" text-anchor="middle">expenses</text><text x="995.0" y="87.0" class="t" text-anchor="middle">Approval detail</text><text x="995.0" y="102.0" class="s" text-anchor="middle">approve / reject</text><text x="775.0" y="287.0" class="t" text-anchor="middle">Settlement</text><text x="775.0" y="302.0" class="s" text-anchor="middle">profit + partners</text><text x="995.0" y="287.0" class="t" text-anchor="middle">Cashbox feed</text><text x="995.0" y="302.0" class="s" text-anchor="middle">latest movements</text><text x="555.0" y="347.0" class="t" text-anchor="middle">Notifications</text><text x="555.0" y="362.0" class="s" text-anchor="middle">push history</text><rect x="651.7" y="131.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="665.0" y="142.0" class="lbl" text-anchor="middle">tab</text><rect x="651.7" y="231.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="665.0" y="242.0" class="lbl" text-anchor="middle">tab</text></svg>
</div>

*Figure — Five tabs at most: home, approvals, settlement, cashbox, notifications.* [Open interactive version](diagrams/06c-owner-screens.html)


- **Recommended stack:** Flutter. One codebase for iOS and Android, strong RTL support, and mature biometric and FCM plugins.
- **Effort:** **M** (about 6–8 weeks for one developer after the API foundation).
- **Backend dependencies:**
  - the API foundation;
  - extracting `SettlementController` figures into a reusable service;
  - an approval-created event that triggers the push.

<a id="app-sales"></a>
### 9.2 App 2 — Sales field app

- **Persona:** sales staff (sales role) on site with prospective buyers.
- **Value proposition:** the salesperson shows the live unit grid in front of the client, reserves a specific unit with the same validation the office uses (so no unit is sold twice), captures the client's ID with the camera, and manages leads and follow-ups from the phone. Every sale still goes to the owner for approval.

**Features**

| MVP | Phase 2 | Future |
|---|---|---|
| Login; assigned projects | Field collection with receipt photo (pending) | ID OCR auto-fill |
| Availability grid (floor × unit, sold flags) | Offline cache + queued submissions | Commission tracking |
| New sale (pending) with instalment plan | Share unit sheet / price quote PDF via WhatsApp | Lead import from ad platforms |
| CRM leads list, detail, log activity | GPS stamp on activities | |
| My tasks + update status | | |

**Endpoint mapping**

| Feature | Existing endpoint (web, logic to reuse) | Mobile endpoint |
|---|---|---|
| Availability grid | `GET /app/site-sketch` — `SiteSketchController@index`; `GET /app/{project}/sales/create` — `SaleController@create` (`takenUnits`) | **NEW** `GET /api/v1/projects/{p}/properties/{id}/availability` |
| Create sale | `POST /app/{project}/sales` — `SaleController@store` + `StoreSaleRequest` | **NEW** `POST /api/v1/projects/{p}/sales` (same request rules) |
| Sale status | `GET /app/{project}/sales/{sale}` — `SaleController@show` | **NEW** `GET /api/v1/projects/{p}/sales/{id}` |
| Leads | `GET /app/crm/leads` — `CrmLeadController@index`; `POST /app/crm/leads` — `CrmLeadController@store` | **NEW** `GET`/`POST /api/v1/crm/leads` |
| Log activity | `POST /app/crm/leads/{lead}/activities` — `CrmLeadController@storeActivity` | **NEW** `POST /api/v1/crm/leads/{id}/activities` |
| My tasks | `GET /app/tasks/mine` — `TaskController@mine`; `POST /app/tasks/{task}/updates` — `TaskController@storeUpdate` | **NEW** `GET /api/v1/tasks/mine`, `POST /api/v1/tasks/{id}/updates` |
| Field collection | `POST /app/{project}/revenues` — `RevenueController@store` | **NEW** `POST /api/v1/projects/{p}/revenues` + attachment |
| ID / receipt photos | — | **NEW** `POST /api/v1/uploads` → `{id, url}` (new `attachments` table) |

<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 570" width="100%" role="img" aria-label="App 2 - Sales field app: architecture"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="570" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 2 - Sales field app: architecture</text><rect x="260" y="50" width="560" height="450" rx="14" class="grp"/><rect x="30" y="220" width="190" height="90" rx="10" class="new" stroke-width="1.5"/><rect x="30" y="90" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="280" y="90" width="240" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="560" y="90" width="240" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="280" y="190" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="254" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="318" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="382" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="560" y="190" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="254" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="318" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="382" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="860" y="120" width="210" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="860" y="240" width="210" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="860" y="360" width="210" height="70" rx="10" class="db" stroke-width="1.5"/><path d="M220,125 L280,125" class="ln" marker-end="url(#ah)"/><path d="M220,250 L540,250 L540,125 L560,125" class="ln" marker-end="url(#ah)"/><path d="M520,140 L540,140 L540,175 L400,175 L400,190" class="ln" marker-end="url(#ah)"/><path d="M680,160 L680,175 L420,175" class="ln" marker-end="url(#ah)"/><path d="M800,155 L860,155" class="ln" marker-end="url(#ah)"/><path d="M800,275 L860,275" class="ln" marker-end="url(#ah)"/><path d="M1070,275 L1088,275 L1088,512 L125,512 L125,310" class="lnd" marker-end="url(#ah)"/><rect x="30" y="532" width="18" height="12" rx="3" class="shared" stroke-width="1.5"/><rect x="230" y="532" width="18" height="12" rx="3" class="new" stroke-width="1.5"/><rect x="430" y="532" width="18" height="12" rx="3" class="db" stroke-width="1.5"/><rect x="630" y="532" width="18" height="12" rx="3" class="client" stroke-width="1.5"/><text x="125.0" y="262.0" class="t" text-anchor="middle">Sales app</text><text x="125.0" y="277.0" class="s" text-anchor="middle">Flutter, offline cache</text><text x="125.0" y="122.0" class="t" text-anchor="middle">Web dashboard</text><text x="125.0" y="137.0" class="s" text-anchor="middle">existing Blade UI</text><text x="272" y="66" class="gl" text-anchor="start">SAME LARAVEL BACKEND (ELMANAREGY.COM)</text><text x="400.0" y="122.0" class="t" text-anchor="middle">Web routes  /app/*</text><text x="400.0" y="137.0" class="s" text-anchor="middle">session auth (existing)</text><text x="680.0" y="115.0" class="t" text-anchor="middle">REST API  /api/v1/*</text><text x="680.0" y="130.0" class="s" text-anchor="middle">Sanctum tokens,</text><text x="680.0" y="143.0" class="s" text-anchor="middle">X-Project-Id  (NEW)</text><text x="400.0" y="214.0" class="t" text-anchor="middle">StoreSaleRequest rules</text><text x="400.0" y="229.0" class="s" text-anchor="middle">unit + floor checks</text><text x="400.0" y="278.0" class="t" text-anchor="middle">SaleController::takenUnits</text><text x="400.0" y="293.0" class="s" text-anchor="middle">availability</text><text x="400.0" y="342.0" class="t" text-anchor="middle">CrmLeadController</text><text x="400.0" y="357.0" class="s" text-anchor="middle">leads + activities</text><text x="400.0" y="406.0" class="t" text-anchor="middle">TaskController</text><text x="400.0" y="421.0" class="s" text-anchor="middle">my tasks</text><text x="680.0" y="214.0" class="t" text-anchor="middle">/api/v1/projects/{p}/availability</text><text x="680.0" y="229.0" class="s" text-anchor="middle">units per floor</text><text x="680.0" y="278.0" class="t" text-anchor="middle">/api/v1/sales</text><text x="680.0" y="293.0" class="s" text-anchor="middle">create pending sale</text><text x="680.0" y="342.0" class="t" text-anchor="middle">/api/v1/crm/leads</text><text x="680.0" y="357.0" class="s" text-anchor="middle">CRUD + activities</text><text x="680.0" y="406.0" class="t" text-anchor="middle">/api/v1/uploads</text><text x="680.0" y="421.0" class="s" text-anchor="middle">ID / contract photos</text><text x="965.0" y="145.0" class="t" text-anchor="middle">MySQL</text><text x="965.0" y="160.0" class="s" text-anchor="middle">same schema,</text><text x="965.0" y="173.0" class="s" text-anchor="middle">project_id scoped</text><text x="965.0" y="272.0" class="t" text-anchor="middle">Push (FCM / APNs)</text><text x="965.0" y="287.0" class="s" text-anchor="middle">NEW integration</text><text x="965.0" y="392.0" class="t" text-anchor="middle">Storage</text><text x="965.0" y="407.0" class="s" text-anchor="middle">receipts, photos</text><rect x="230.5" y="116.0" width="39.0" height="15" rx="3" class="lblbg"/><text x="250.0" y="127.0" class="lbl" text-anchor="middle">HTTPS</text><rect x="335.7" y="241.0" width="88.60000000000001" height="15" rx="3" class="lblbg"/><text x="380.0" y="252.0" class="lbl" text-anchor="middle">HTTPS + token</text><rect x="453.7" y="166.0" width="88.60000000000001" height="15" rx="3" class="lblbg"/><text x="498.0" y="177.0" class="lbl" text-anchor="middle">same services</text><rect x="816.7" y="146.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="830.0" y="157.0" class="lbl" text-anchor="middle">SQL</text><rect x="807.4" y="266.0" width="45.2" height="15" rx="3" class="lblbg"/><text x="830.0" y="277.0" class="lbl" text-anchor="middle">notify</text><rect x="590.1" y="503.0" width="32.8" height="15" rx="3" class="lblbg"/><text x="606.5" y="514.0" class="lbl" text-anchor="middle">push</text><text x="54" y="542" class="s" text-anchor="start">Shared (existing) service</text><text x="254" y="542" class="s" text-anchor="start">New / proposed</text><text x="454" y="542" class="s" text-anchor="start">Storage</text><text x="654" y="542" class="s" text-anchor="start">Existing client</text></svg>
</div>

*Figure — Sales staff reuse the exact validation (unit numbering, floor capacity, max amount) and the approval workflow already enforced on the web.* [Open interactive version](diagrams/07a-sales-arch.html)


<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 910 560" width="100%" role="img" aria-label="App 2 primary flow: reserve a unit at the site"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="910" height="560" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 2 primary flow: reserve a unit at the site</text><rect x="40.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="115.0" y1="92" x2="115.0" y2="536" class="life"/><rect x="210.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="285.0" y1="92" x2="285.0" y2="536" class="life"/><rect x="380.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="455.0" y1="92" x2="455.0" y2="536" class="life"/><rect x="550.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="625.0" y1="92" x2="625.0" y2="536" class="life"/><rect x="720.0" y="52" width="150" height="40" rx="10" class="db" stroke-width="1.5"/><line x1="795.0" y1="92" x2="795.0" y2="536" class="life"/><path d="M115.0,120 L281.0,120" class="ln" marker-end="url(#ah)"/><path d="M285.0,158 L451.0,158" class="ln" marker-end="url(#ah)"/><path d="M455.0,196 L119.0,196" class="lnd" marker-end="url(#ah)"/><path d="M115.0,228 L155.0,228 L155.0,242 L119.0,242" class="ln" marker-end="url(#ah)"/><path d="M115.0,272 L281.0,272" class="ln" marker-end="url(#ah)"/><path d="M285.0,310 L621.0,310" class="ln" marker-end="url(#ah)"/><path d="M625.0,348 L289.0,348" class="lnd" marker-end="url(#ah)"/><path d="M285.0,386 L791.0,386" class="ln" marker-end="url(#ah)"/><path d="M285.0,424 L119.0,424" class="lnd" marker-end="url(#ah)"/><rect x="165.0" y="448" width="240" height="22" rx="4" class="note"/><text x="115.0" y="76.0" class="t" text-anchor="middle">Sales app</text><text x="285.0" y="76.0" class="t" text-anchor="middle">API /api/v1</text><text x="455.0" y="76.0" class="t" text-anchor="middle">Availability</text><text x="625.0" y="76.0" class="t" text-anchor="middle">StoreSaleRequest</text><text x="795.0" y="76.0" class="t" text-anchor="middle">DB</text><text x="200.0" y="114" class="lbl" text-anchor="middle">GET /projects/{p}/availability?property</text><text x="370.0" y="152" class="lbl" text-anchor="middle">takenUnits(property)</text><text x="285.0" y="190" class="lbl" text-anchor="middle">floors x units, sold flags</text><text x="161.0" y="239" class="lbl" text-anchor="start">scan client national ID (camera)</text><text x="200.0" y="266" class="lbl" text-anchor="middle">POST /sales (unit, price, down payment)</text><text x="455.0" y="304" class="lbl" text-anchor="middle">same rules as the web form</text><text x="455.0" y="342" class="lbl" text-anchor="middle">422 &#x27;already sold&#x27; or ok</text><text x="540.0" y="380" class="lbl" text-anchor="middle">sale pending + contract + client</text><text x="200.0" y="418" class="lbl" text-anchor="middle">201 pending approval</text><text x="285.0" y="463" class="lbl" text-anchor="middle">owner app receives approval push</text></svg>
</div>

*Figure — Live availability prevents double-selling a unit while the client is still on site.* [Open interactive version](diagrams/07b-sales-seq.html)


<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 420" width="100%" role="img" aria-label="App 2 screen flow"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="420" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 2 screen flow</text><rect x="30" y="160" width="170" height="60" rx="10" class="client" stroke-width="1.5"/><rect x="250" y="160" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="690" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="910" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="690" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="910" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><path d="M200,190.0 L250,190.0" class="ln" marker-end="url(#ah)"/><path d="M335.0,160 L335.0,140.0 L555.0,140.0 L555.0,120" class="ln" marker-end="url(#ah)"/><path d="M640,90.0 L690,90.0" class="ln" marker-end="url(#ah)"/><path d="M860,90.0 L910,90.0" class="ln" marker-end="url(#ah)"/><path d="M335.0,220 L335.0,240.0 L555.0,240.0 L555.0,260" class="ln" marker-end="url(#ah)"/><path d="M640,290.0 L690,290.0" class="ln" marker-end="url(#ah)"/><path d="M860,290.0 L910,290.0" class="ln" marker-end="url(#ah)"/><text x="115.0" y="187.0" class="t" text-anchor="middle">Login</text><text x="115.0" y="202.0" class="s" text-anchor="middle">email + PIN</text><text x="335.0" y="187.0" class="t" text-anchor="middle">Projects</text><text x="335.0" y="202.0" class="s" text-anchor="middle">assigned projects</text><text x="555.0" y="87.0" class="t" text-anchor="middle">Availability grid</text><text x="555.0" y="102.0" class="s" text-anchor="middle">floors x units</text><text x="775.0" y="87.0" class="t" text-anchor="middle">New sale</text><text x="775.0" y="102.0" class="s" text-anchor="middle">client, price, plan</text><text x="995.0" y="87.0" class="t" text-anchor="middle">Sale submitted</text><text x="995.0" y="102.0" class="s" text-anchor="middle">pending status</text><text x="555.0" y="287.0" class="t" text-anchor="middle">Leads</text><text x="555.0" y="302.0" class="s" text-anchor="middle">CRM list</text><text x="775.0" y="280.0" class="t" text-anchor="middle">Lead detail</text><text x="775.0" y="295.0" class="s" text-anchor="middle">call / WhatsApp,</text><text x="775.0" y="308.0" class="s" text-anchor="middle">log activity</text><text x="995.0" y="287.0" class="t" text-anchor="middle">My tasks</text><text x="995.0" y="302.0" class="s" text-anchor="middle">due today</text><rect x="431.7" y="131.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="445.0" y="142.0" class="lbl" text-anchor="middle">tab</text><rect x="636.2" y="81.0" width="57.6" height="15" rx="3" class="lblbg"/><text x="665.0" y="92.0" class="lbl" text-anchor="middle">tap unit</text><rect x="431.7" y="231.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="445.0" y="242.0" class="lbl" text-anchor="middle">tab</text></svg>
</div>

*Figure — Availability first: the unit grid is the entry point to a sale.* [Open interactive version](diagrams/07c-sales-screens.html)


- **Recommended stack:** Flutter. It shares the design system and API client with App 1, and its offline storage (Drift/SQLite) is proven.
- **Effort:** **M–L** (about 8–10 weeks; offline sync is the main cost).
- **Backend dependencies:**
  - the API foundation;
  - extracting `takenUnits()` into a service;
  - an uploads/attachments table;
  - idempotency keys on create endpoints, for offline retries.

<a id="app-buyer"></a>
### 9.3 App 3 — Buyer portal

- **Persona:** buyers who purchased units on instalments.
- **Value proposition:** buyers see what they own, what they have paid, what is left and when the next instalment is due, and they download their receipts and contract without calling the office. Reminders push collections forward, and the developer gets a branded channel to announce new projects.

**Features**

| MVP | Phase 2 | Future |
|---|---|---|
| Phone OTP login | Online payment (Fawry / Paymob / InstaPay link) | Referral program |
| My units: price, paid, remaining | Contract document download | Construction progress photo feed |
| Instalment schedule + due reminders | Support chat / WhatsApp hand-off | Resale / transfer requests |
| Receipts list (approved revenues) | New projects catalogue (from website module) | |

**Endpoint mapping**

| Feature | Existing endpoint (web, logic to reuse) | Mobile endpoint |
|---|---|---|
| OTP login | — | **NEW** `POST /api/v1/client/auth/otp`, `POST /api/v1/client/auth/verify` (client guard keyed on `clients.phone`) |
| My units | `GET /app/{project}/clients/{client}` — `ClientController@show` | **NEW** `GET /api/v1/client/contracts` |
| Unit detail | `GET /app/{project}/contracts/{contract}` — `ContractController@show` | **NEW** `GET /api/v1/client/contracts/{id}` |
| Schedule | — (plan stored as sale fields only) | **NEW** `GET /api/v1/client/contracts/{id}/schedule` + **NEW** `installment_schedules` table |
| Receipts | `GET /app/{project}/revenues/{revenue}` — `RevenueController@show` | **NEW** `GET /api/v1/client/payments` |
| Contract document | `GET /app/{project}/contracts/{contract}/word` — `ContractController@downloadWord` | **NEW** `GET /api/v1/client/contracts/{id}/document` (PDF) |
| New projects | `GET /projects` — `Site\SiteController@projects` | **NEW** `GET /api/v1/public/projects` |
| Reminders | — | **NEW** scheduled command `installments:remind` + push |

<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 570" width="100%" role="img" aria-label="App 3 - Buyer (client) portal: architecture"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="570" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 3 - Buyer (client) portal: architecture</text><rect x="260" y="50" width="560" height="450" rx="14" class="grp"/><rect x="30" y="220" width="190" height="90" rx="10" class="new" stroke-width="1.5"/><rect x="30" y="90" width="190" height="70" rx="10" class="client" stroke-width="1.5"/><rect x="280" y="90" width="240" height="70" rx="10" class="core" stroke-width="1.5"/><rect x="560" y="90" width="240" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="280" y="190" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="254" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="318" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="280" y="382" width="240" height="54" rx="10" class="shared" stroke-width="1.5"/><rect x="560" y="190" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="254" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="318" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="560" y="382" width="240" height="54" rx="10" class="new" stroke-width="1.5"/><rect x="860" y="120" width="210" height="70" rx="10" class="db" stroke-width="1.5"/><rect x="860" y="240" width="210" height="70" rx="10" class="new" stroke-width="1.5"/><rect x="860" y="360" width="210" height="70" rx="10" class="db" stroke-width="1.5"/><path d="M220,125 L280,125" class="ln" marker-end="url(#ah)"/><path d="M220,250 L540,250 L540,125 L560,125" class="ln" marker-end="url(#ah)"/><path d="M520,140 L540,140 L540,175 L400,175 L400,190" class="ln" marker-end="url(#ah)"/><path d="M680,160 L680,175 L420,175" class="ln" marker-end="url(#ah)"/><path d="M800,155 L860,155" class="ln" marker-end="url(#ah)"/><path d="M800,275 L860,275" class="ln" marker-end="url(#ah)"/><path d="M1070,275 L1088,275 L1088,512 L125,512 L125,310" class="lnd" marker-end="url(#ah)"/><rect x="30" y="532" width="18" height="12" rx="3" class="shared" stroke-width="1.5"/><rect x="230" y="532" width="18" height="12" rx="3" class="new" stroke-width="1.5"/><rect x="430" y="532" width="18" height="12" rx="3" class="db" stroke-width="1.5"/><rect x="630" y="532" width="18" height="12" rx="3" class="client" stroke-width="1.5"/><text x="125.0" y="262.0" class="t" text-anchor="middle">Buyer app</text><text x="125.0" y="277.0" class="s" text-anchor="middle">Flutter or PWA</text><text x="125.0" y="122.0" class="t" text-anchor="middle">Web dashboard</text><text x="125.0" y="137.0" class="s" text-anchor="middle">existing Blade UI</text><text x="272" y="66" class="gl" text-anchor="start">SAME LARAVEL BACKEND (ELMANAREGY.COM)</text><text x="400.0" y="122.0" class="t" text-anchor="middle">Web routes  /app/*</text><text x="400.0" y="137.0" class="s" text-anchor="middle">session auth (existing)</text><text x="680.0" y="115.0" class="t" text-anchor="middle">REST API  /api/v1/*</text><text x="680.0" y="130.0" class="s" text-anchor="middle">Sanctum tokens,</text><text x="680.0" y="143.0" class="s" text-anchor="middle">X-Project-Id  (NEW)</text><text x="400.0" y="214.0" class="t" text-anchor="middle">Contract + remaining</text><text x="400.0" y="229.0" class="s" text-anchor="middle">Contract model</text><text x="400.0" y="278.0" class="t" text-anchor="middle">Revenues (receipts)</text><text x="400.0" y="293.0" class="s" text-anchor="middle">approved collections</text><text x="400.0" y="342.0" class="t" text-anchor="middle">ContractWordDocumentService</text><text x="400.0" y="357.0" class="s" text-anchor="middle">contract document</text><text x="400.0" y="406.0" class="t" text-anchor="middle">Website projects</text><text x="400.0" y="421.0" class="s" text-anchor="middle">public listings</text><text x="680.0" y="214.0" class="t" text-anchor="middle">/api/v1/client/auth/otp</text><text x="680.0" y="229.0" class="s" text-anchor="middle">phone OTP login</text><text x="680.0" y="278.0" class="t" text-anchor="middle">/api/v1/client/contracts</text><text x="680.0" y="293.0" class="s" text-anchor="middle">my units + schedule</text><text x="680.0" y="342.0" class="t" text-anchor="middle">/api/v1/client/payments</text><text x="680.0" y="357.0" class="s" text-anchor="middle">receipts, due dates</text><text x="680.0" y="406.0" class="t" text-anchor="middle">installment_schedules</text><text x="680.0" y="421.0" class="s" text-anchor="middle">NEW table</text><text x="965.0" y="145.0" class="t" text-anchor="middle">MySQL</text><text x="965.0" y="160.0" class="s" text-anchor="middle">same schema,</text><text x="965.0" y="173.0" class="s" text-anchor="middle">project_id scoped</text><text x="965.0" y="272.0" class="t" text-anchor="middle">Push (FCM / APNs)</text><text x="965.0" y="287.0" class="s" text-anchor="middle">NEW integration</text><text x="965.0" y="392.0" class="t" text-anchor="middle">Storage</text><text x="965.0" y="407.0" class="s" text-anchor="middle">receipts, photos</text><rect x="230.5" y="116.0" width="39.0" height="15" rx="3" class="lblbg"/><text x="250.0" y="127.0" class="lbl" text-anchor="middle">HTTPS</text><rect x="335.7" y="241.0" width="88.60000000000001" height="15" rx="3" class="lblbg"/><text x="380.0" y="252.0" class="lbl" text-anchor="middle">HTTPS + token</text><rect x="453.7" y="166.0" width="88.60000000000001" height="15" rx="3" class="lblbg"/><text x="498.0" y="177.0" class="lbl" text-anchor="middle">same services</text><rect x="816.7" y="146.0" width="26.6" height="15" rx="3" class="lblbg"/><text x="830.0" y="157.0" class="lbl" text-anchor="middle">SQL</text><rect x="807.4" y="266.0" width="45.2" height="15" rx="3" class="lblbg"/><text x="830.0" y="277.0" class="lbl" text-anchor="middle">notify</text><rect x="590.1" y="503.0" width="32.8" height="15" rx="3" class="lblbg"/><text x="606.5" y="514.0" class="lbl" text-anchor="middle">push</text><text x="54" y="542" class="s" text-anchor="start">Shared (existing) service</text><text x="254" y="542" class="s" text-anchor="start">New / proposed</text><text x="454" y="542" class="s" text-anchor="start">Storage</text><text x="654" y="542" class="s" text-anchor="start">Existing client</text></svg>
</div>

*Figure — A read-mostly portal for buyers; it needs a client identity, an installment schedule table and OTP login that do not exist yet.* [Open interactive version](diagrams/08a-buyer-arch.html)


<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 910 522" width="100%" role="img" aria-label="App 3 primary flow: buyer checks the next installment"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="910" height="522" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 3 primary flow: buyer checks the next installment</text><rect x="40.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="115.0" y1="92" x2="115.0" y2="498" class="life"/><rect x="210.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="285.0" y1="92" x2="285.0" y2="498" class="life"/><rect x="380.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="455.0" y1="92" x2="455.0" y2="498" class="life"/><rect x="550.0" y="52" width="150" height="40" rx="10" class="shared" stroke-width="1.5"/><line x1="625.0" y1="92" x2="625.0" y2="498" class="life"/><rect x="720.0" y="52" width="150" height="40" rx="10" class="new" stroke-width="1.5"/><line x1="795.0" y1="92" x2="795.0" y2="498" class="life"/><path d="M115.0,120 L281.0,120" class="ln" marker-end="url(#ah)"/><path d="M285.0,158 L451.0,158" class="ln" marker-end="url(#ah)"/><path d="M115.0,196 L281.0,196" class="ln" marker-end="url(#ah)"/><path d="M285.0,234 L119.0,234" class="lnd" marker-end="url(#ah)"/><path d="M115.0,272 L281.0,272" class="ln" marker-end="url(#ah)"/><path d="M285.0,310 L621.0,310" class="ln" marker-end="url(#ah)"/><path d="M285.0,348 L119.0,348" class="lnd" marker-end="url(#ah)"/><line x1="20" y1="376" x2="890" y2="376" class="life"/><path d="M795.0,424 L119.0,424" class="lnd" marker-end="url(#ah)"/><text x="115.0" y="76.0" class="t" text-anchor="middle">Buyer app</text><text x="285.0" y="76.0" class="t" text-anchor="middle">API /api/v1/client</text><text x="455.0" y="76.0" class="t" text-anchor="middle">OTP / SMS</text><text x="625.0" y="76.0" class="t" text-anchor="middle">Contracts</text><text x="795.0" y="76.0" class="t" text-anchor="middle">Push (FCM)</text><text x="200.0" y="114" class="lbl" text-anchor="middle">POST /auth/otp (phone)</text><text x="370.0" y="152" class="lbl" text-anchor="middle">send code</text><text x="200.0" y="190" class="lbl" text-anchor="middle">POST /auth/verify (code)</text><text x="200.0" y="228" class="lbl" text-anchor="middle">token (client guard)</text><text x="200.0" y="266" class="lbl" text-anchor="middle">GET /contracts</text><text x="455.0" y="304" class="lbl" text-anchor="middle">contracts where client.phone = me</text><text x="200.0" y="342" class="lbl" text-anchor="middle">unit, paid, remaining, next due</text><text x="26" y="390" class="gl" text-anchor="start">3 days before due date</text><text x="455.0" y="418" class="lbl" text-anchor="middle">reminder: installment due 01/12</text></svg>
</div>

*Figure — OTP login keyed on the phone number already stored on the client record.* [Open interactive version](diagrams/08b-buyer-seq.html)


<div align="center">
<svg class="dg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 420" width="100%" role="img" aria-label="App 3 screen flow"><style>.dg{font-family:Inter,Segoe UI,Roboto,Arial,sans-serif}.dg .bg{fill:#ffffff}.dg .t{fill:#0f172a;font-size:13px;font-weight:600}.dg .s{fill:#475569;font-size:11px}.dg .h{fill:#0f172a;font-size:16px;font-weight:700}.dg .gl{fill:#64748b;font-size:11px;font-weight:600;letter-spacing:.04em}.dg .core{fill:#eef2ff;stroke:#4f46e5}.dg .shared{fill:#ecfdf5;stroke:#059669}.dg .new{fill:#fff7ed;stroke:#ea580c;stroke-dasharray:6 4}.dg .ext{fill:#f1f5f9;stroke:#64748b}.dg .db{fill:#fefce8;stroke:#ca8a04}.dg .client{fill:#f0f9ff;stroke:#0284c7}.dg .warn{fill:#fef2f2;stroke:#dc2626}.dg .grp{fill:none;stroke:#94a3b8;stroke-dasharray:4 4}.dg .ln{stroke:#334155;fill:none;stroke-width:1.4}.dg .lnd{stroke:#334155;fill:none;stroke-width:1.2;stroke-dasharray:5 4}.dg .ah{fill:#334155}.dg .lbl{fill:#1e293b;font-size:10.5px}.dg .lblbg{fill:#ffffff;opacity:.9}.dg .life{stroke:#94a3b8;stroke-dasharray:3 4}.dg .act{fill:#e2e8f0;stroke:#64748b}.dg .note{fill:#fffbeb;stroke:#d97706}@media (prefers-color-scheme: dark){.dg .bg{fill:#0b1220}.dg .t,.dg .h{fill:#e2e8f0}.dg .s,.dg .gl{fill:#94a3b8}.dg .core{fill:#1e1b4b;stroke:#818cf8}.dg .shared{fill:#052e2b;stroke:#34d399}.dg .new{fill:#3b1d0a;stroke:#fb923c}.dg .ext{fill:#1e293b;stroke:#94a3b8}.dg .db{fill:#33280a;stroke:#facc15}.dg .client{fill:#082f49;stroke:#38bdf8}.dg .warn{fill:#3f0d0d;stroke:#f87171}.dg .ln,.dg .lnd{stroke:#cbd5e1}.dg .ah{fill:#cbd5e1}.dg .lbl{fill:#e2e8f0}.dg .lblbg{fill:#0b1220}.dg .act{fill:#334155;stroke:#94a3b8}.dg .note{fill:#3a2a06;stroke:#f59e0b}}</style><defs><marker id="ah" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker><marker id="ahs" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" class="ah"/></marker></defs><rect class="bg" x="0" y="0" width="1100" height="420" rx="12"/><text x="24" y="34" class="h" text-anchor="start">App 3 screen flow</text><rect x="30" y="160" width="170" height="60" rx="10" class="client" stroke-width="1.5"/><rect x="250" y="160" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="690" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="910" y="60" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="470" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="690" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><rect x="910" y="260" width="170" height="60" rx="10" class="core" stroke-width="1.5"/><path d="M200,190.0 L250,190.0" class="ln" marker-end="url(#ah)"/><path d="M335.0,160 L335.0,140.0 L555.0,140.0 L555.0,120" class="ln" marker-end="url(#ah)"/><path d="M640,90.0 L690,90.0" class="ln" marker-end="url(#ah)"/><path d="M860,90.0 L910,90.0" class="ln" marker-end="url(#ah)"/><path d="M335.0,220 L335.0,240.0 L555.0,240.0 L555.0,260" class="ln" marker-end="url(#ah)"/><path d="M640,290.0 L690,290.0" class="ln" marker-end="url(#ah)"/><path d="M860,290.0 L910,290.0" class="ln" marker-end="url(#ah)"/><text x="115.0" y="187.0" class="t" text-anchor="middle">OTP login</text><text x="115.0" y="202.0" class="s" text-anchor="middle">phone + code</text><text x="335.0" y="187.0" class="t" text-anchor="middle">My units</text><text x="335.0" y="202.0" class="s" text-anchor="middle">contracts list</text><text x="555.0" y="87.0" class="t" text-anchor="middle">Unit detail</text><text x="555.0" y="102.0" class="s" text-anchor="middle">paid / remaining</text><text x="775.0" y="87.0" class="t" text-anchor="middle">Schedule</text><text x="775.0" y="102.0" class="s" text-anchor="middle">due dates</text><text x="995.0" y="87.0" class="t" text-anchor="middle">Receipts</text><text x="995.0" y="102.0" class="s" text-anchor="middle">approved payments</text><text x="555.0" y="287.0" class="t" text-anchor="middle">Documents</text><text x="555.0" y="302.0" class="s" text-anchor="middle">contract file</text><text x="775.0" y="287.0" class="t" text-anchor="middle">New projects</text><text x="775.0" y="302.0" class="s" text-anchor="middle">public listings</text><text x="995.0" y="287.0" class="t" text-anchor="middle">Support</text><text x="995.0" y="302.0" class="s" text-anchor="middle">WhatsApp / call</text></svg>
</div>

*Figure — Read-only by design: buyers view, never edit, financial data.* [Open interactive version](diagrams/08c-buyer-screens.html)


- **Recommended stack:** Flutter (shared packages with Apps 1–2), or a PWA if store presence is not required. A PWA is cheaper but its iOS push support is weaker.
- **Effort:** **L** (about 10–12 weeks including new data model, OTP/SMS provider and privacy work).
- **Backend dependencies:**
  - the API foundation;
  - a client auth guard;
  - an SMS/OTP provider;
  - the `installment_schedules` table, generated when a sale is approved and backfilled for existing contracts;
  - a reminder scheduler;
  - privacy review.

---

<a id="roadmap"></a>
## 10. Comparison matrix and recommended roadmap

| App | Business value | Effort | Risk | New endpoints needed | Notes |
|---|---|---|---|---|---|
| 1. Owner & Partner companion | **High**: speeds up approvals and builds owner trust | M | Low | 9 (thin wrappers over existing logic; 3 are shared foundation) | Best first release |
| 2. Sales field app | **High**: fewer lost deals, no double-selling on site | M–L | Medium (offline sync) | 11 + uploads table | Reuses App 1 auth and design |
| 3. Buyer portal | **Medium–High**: fewer support calls, faster collections, brand | L | Medium–High (PII, OTP, new schedule model) | 9 + client guard + schedule table + reminder job | Do after the schedule model is proven internally |

**Recommended build order**

1. **Phase 0 — API foundation (about 4 weeks):**
   - Sanctum and `/api/v1`;
   - project header middleware;
   - permission reuse;
   - JSON resources and API tests;
   - device tokens and FCM.
2. **Phase 1 — App 1 MVP (about 6–8 weeks):** approvals, KPIs, settlement, push.
3. **Phase 2 — App 2 MVP (about 8–10 weeks):** availability, pending sale, CRM, tasks. Add uploads.
4. **Phase 3 — instalment schedule model (about 3 weeks):** used first in the web dashboard for an overdue-instalments list and a client statement of account.
5. **Phase 4 — App 3 MVP (about 10–12 weeks):** OTP, contracts, schedule, receipts, reminders.

---

<a id="decisions"></a>
## 11. Decisions and assumptions

| # | Decision / assumption | Basis |
|---|---|---|
| D1 | The analysis ran on the local working copy (`e7f3cf8` + the uncommitted flash-message change in `resources/views/layouts/admin.blade.php`). | Git status at analysis time |
| D2 | The work was done by one analyst instead of the seven parallel sub-agents described in the brief, at the user's request, to save cost. The same scope was covered. | Instruction from the user |
| D3 | Diagrams were drawn as hand-authored, theme-aware SVG instead of with the Archify skill, which was not installed in this environment. Each has an HTML original with a light/dark toggle. | Tooling availability |
| D4 | `routes/api.php` is treated as **non-existent at runtime**: `bootstrap/app.php` does not register it, and `app/Http/Controllers/Api/` does not exist. Its design (Sanctum, `X-Project-Id`) is reused as the starting point for `/api/v1`. | Code inspection |
| D5 | The role access in §6.2 is taken from the role grants in the analysis database seeded by `PermissionSeeder`. Production may differ if an admin changed role grants or user `extra_permissions`. | `database/seeders/PermissionSeeder.php` |
| D6 | Request and response shapes in §7 refer to the form-request classes rather than being expanded per route, to keep the inventory readable. | Brevity |
| D7 | The revenue model is an assumption: no billing code exists. | Code inspection |
| D8 | Market figures are headline-level and cited; they were not independently verified. | Desk research |
| D9 | Flutter is recommended for all apps so that RTL handling, design system and API client are shared. React Native is a reasonable alternative if the team prefers JavaScript. | Team-size assumption |
| D10 | Effort sizes assume one experienced mobile developer plus part-time backend support. | Assumption |
| D11 | Apps 1 and 2 could ship as one staff app with role-based home screens. Keeping them separate is recommended for a simpler UX and independent release cadence. | Product judgement |
| D12 | No secrets, `.env` values or credentials are included in this document. | Brief §1 |

---

<a id="appendix"></a>
## 12. Appendix

### 12.1 Directory tree (depth 3, excluding vendor, node_modules, build and .git)
```text
mohaseb_aqary/
    app/
        Console/
            … (1 dirs, 0 files)
        Http/
            … (3 dirs, 0 files)
        Mail/
            … (0 dirs, 1 files)
        Models/
            … (1 dirs, 35 files)
        Policies/
            … (0 dirs, 2 files)
        Providers/
            … (0 dirs, 1 files)
        Repositories/
            … (2 dirs, 0 files)
        Services/
            … (0 dirs, 13 files)
        Support/
            … (0 dirs, 8 files)
    bootstrap/
        cache/
            … (0 dirs, 3 files)
        app.php
        providers.php
    config/
        … 15 files
    database/
        factories/
            … (0 dirs, 1 files)
        migrations/
            … (0 dirs, 65 files)
        seeders/
            … (0 dirs, 2 files)
        database.sqlite
    docs/
        blueprint/
            … (1 dirs, 0 files)
    lang/
        en/
            … (0 dirs, 1 files)
    public/
        favicon.ico
        index.php
        robots.txt
    resources/
        css/
            … (0 dirs, 4 files)
        js/
            … (0 dirs, 5 files)
        views/
            … (35 dirs, 2 files)
    routes/
        api.php
        console.php
        web.php
    scripts/
    tests/
        Feature/
            … (0 dirs, 3 files)
        Unit/
            … (0 dirs, 3 files)
        TestCase.php
    tools/
    README.md
    artisan
    composer.json
    composer.lock
    package-lock.json
    package.json
    phpunit.xml
    vite.config.js
    storage/  (runtime; not expanded)
```

### 12.2 Glossary
| Term | Meaning |
|---|---|
| مشروع (Project) | A development project; the unit of data isolation (`project_id`) |
| عقار (Property) | A building inside a project, with floors, units and models |
| بيعة (Sale) | Sale of one unit (floor + unit number), cash or instalments |
| عقد (Contract) | Created with a sale; holds `remaining_amount` |
| تحصيل / إيراد (Revenue) | A collection against a contract |
| مصروف (Expense) | Project cost classified by expense type |
| ذمة دائنة (Debt) | Supplier payable; paid from the cashbox via debt payments |
| الصندوق (Cashbox) | The project ledger (`treasury_transactions`); only approved rows count |
| مساهم / شريك (Shareholder) | Partner with a percentage in a project or land parcel |
| جاري المساهم (Shareholder ledger) | Partner capital (credit) and withdrawals (debit) |
| التسويات (Settlement) | Realized and expected profit and partner shares |
| تجارة الأراضي (Land trading) | Buying, splitting and selling land parcels with a dedicated cashbox |
| الاعتمادات (Approvals) | Maker–checker queue for money-moving entries |
| مخطط الموقع (Site sketch) | Visual floor × unit grid |

### 12.3 HTML diagram originals
- [Repository / module map](diagrams/01-module-map.html) — `docs/blueprint/diagrams/01-module-map.html`
- [High-level runtime architecture](diagrams/02-runtime-architecture.html) — `docs/blueprint/diagrams/02-runtime-architecture.html`
- [Core data flow: from sale to settlement](diagrams/03-data-flow.html) — `docs/blueprint/diagrams/03-data-flow.html`
- [Sequence: staff sign-in](diagrams/04a-seq-login.html) — `docs/blueprint/diagrams/04a-seq-login.html`
- [Sequence: sale and approval](diagrams/04b-seq-sale-approval.html) — `docs/blueprint/diagrams/04b-seq-sale-approval.html`
- [Sequence: settlement](diagrams/04c-seq-settlement.html) — `docs/blueprint/diagrams/04c-seq-settlement.html`
- [Lifecycle: Sale](diagrams/05-sale-lifecycle.html) — `docs/blueprint/diagrams/05-sale-lifecycle.html`
- [App 1 - Owner & Partner companion: architecture](diagrams/06a-owner-arch.html) — `docs/blueprint/diagrams/06a-owner-arch.html`
- [App 1 sequence: approve from push](diagrams/06b-owner-seq.html) — `docs/blueprint/diagrams/06b-owner-seq.html`
- [App 1 screen flow](diagrams/06c-owner-screens.html) — `docs/blueprint/diagrams/06c-owner-screens.html`
- [App 2 - Sales field app: architecture](diagrams/07a-sales-arch.html) — `docs/blueprint/diagrams/07a-sales-arch.html`
- [App 2 sequence: reserve a unit](diagrams/07b-sales-seq.html) — `docs/blueprint/diagrams/07b-sales-seq.html`
- [App 2 screen flow](diagrams/07c-sales-screens.html) — `docs/blueprint/diagrams/07c-sales-screens.html`
- [App 3 - Buyer (client) portal: architecture](diagrams/08a-buyer-arch.html) — `docs/blueprint/diagrams/08a-buyer-arch.html`
- [App 3 sequence: next installment](diagrams/08b-buyer-seq.html) — `docs/blueprint/diagrams/08b-buyer-seq.html`
- [App 3 screen flow](diagrams/08c-buyer-screens.html) — `docs/blueprint/diagrams/08c-buyer-screens.html`
