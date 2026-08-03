# Financial Tracker Context

## 1. System Summary

Financial Tracker is a Laravel 10 web application for importing, categorizing, reviewing, and analyzing personal financial transactions. It supports multiple banks and financial institutions, bank-statement imports, spending-type classification, investment balance tracking, and protected transaction records.

The application is intended for a single user account or a small multi-user deployment. Transactions and most financial data are scoped to the authenticated user. The local development environment runs with Docker Compose. The frontend uses Blade templates, Bootstrap-style components, Chart.js, Font Awesome, and Vite.

## 2. Technology Stack

- Backend: PHP 8.1+, Laravel 10, Eloquent ORM
- Database: MySQL through Laravel migrations and Eloquent
- Authentication: Laravel Breeze authentication, email verification, password confirmation, and forced password change support
- Frontend: Blade, Bootstrap 5 styling, JavaScript, Chart.js, Font Awesome
- Assets: Vite, Tailwind/PostCSS dependencies, Axios, Alpine.js
- File parsing: CSV/TXT parsing and `smalot/pdfparser` for PDF statements
- Deployment: Docker Compose locally; PHP-FPM/Nginx-style production deployment
- Data protection: Eloquent soft deletes and transaction locking

## 3. Main Data Model

### User

Owns transactions and account-related data. Authentication and profile management are provided by Laravel Breeze.

### Bank / Financial Institution

Represents a bank or institution such as Maybank, CIMB, Public Bank, Bank Islam, Tabung Haji, or ASB. Institutions can be marked as investment providers.

Important fields include:

- `name`
- `type` - bank or other financial institution
- `is_investment` - identifies investment accounts

### Transaction

Represents an imported or manually entered financial record.

Important fields include:

- Posted date and transaction date
- Transaction detail
- Debit and credit amounts
- Running balance, when supplied by the statement
- Bank/institution
- Spending type
- `is_locked` to protect a manually reviewed record
- Soft-delete timestamp

### Spending Type

A configurable category for transactions. Spending types contain names, codes, icons, badge classes, sort order, active status, and keyword rules used for automatic categorization.

## 4. Features and Completion Estimates

Percentages are practical engineering estimates of how complete each module is for its current intended scope. They are not formal test-coverage percentages.

| Module | Completion | Current capability | Main remaining work |
| --- | ---: | --- | --- |
| Authentication and user access | 90% | Registration, login, logout, password reset, password update, email verification, password confirmation, profile management, forced password-change flow | More authorization tests, account administration, optional 2FA |
| Transaction CRUD | 90% | List, filter, sort, paginate, create, edit, delete, date shortcuts, bank/type/search/lock filters | Better validation messages, bulk import preview, audit history |
| CSV/TXT importing | 85% | Multiple bank formats, automatic type detection, duplicate-aware import, error collection | More bank fixtures, preview before import, configurable column mapping |
| Maybank PDF importing | 80% | Dedicated PDF extraction and transaction parsing | More statement-layout fixtures and regression tests |
| Tabung Haji PDF importing | 85% | Parses transaction descriptions and running balances, determines debit/credit from balance changes | More layout variations, reconciliation warnings, fixture-based tests |
| ASB PDF importing | 80% | Parses detailed ASB transaction tables and stores balances; increased parser memory limit for production-sized PDFs | More statement formats, upload progress, background processing |
| Automatic spending categorization | 85% | Keyword-based detection, manual type updates, keyword suggestions, recategorization | Rule priority/negative rules, explainable match history, better bulk review |
| Spending type management | 90% | Create/edit types, active status, icons, colors, keywords, ordering, recategorization | Import/export of category rules, type usage statistics |
| Transaction locking | 85% | Individual and bulk lock/unlock; locked records are skipped during re-import | Audit trail, lock reason, stronger authorization tests |
| Duplicate management | 80% | Find duplicate groups and delete duplicate records | Safer merge workflow, duplicate similarity scoring, restore support |
| Bulk operations | 85% | Bulk lock, unlock, update type, and soft-delete selected transactions | Confirmation summaries, undo/restore workflow, larger-selection performance |
| Dashboard | 80% | High-level financial summaries and yearly spending-by-type data | More account-level balances, date-range controls, budget comparison |
| Monitor | 85% | Monthly spending-by-type charts, year selection, clickable bars opening filtered transactions in a new tab | Drill-down totals, income/expense toggle, chart export |
| Analytics | 75% | Detailed analytics view with filters and transaction details | Currently hidden from the sidebar temporarily; needs UX polish and more test coverage |
| Investment tracking | 80% | Tabung Haji and ASB selectors, balance-over-time charts, yearly growth summaries, balance dates | Deposits/withdrawals/returns separation, targets, contributions, reconciliation |
| Data safety and recovery | 70% | User scoping, soft deletes, transaction locks, duplicate prevention | Backups, restore UI, audit log, import rollback, database constraints review |
| Automated testing | 35% | Laravel default/auth/profile feature tests are present | Import fixtures, parser tests, lock-protection tests, controller and browser tests |
| Production operations | 70% | Docker/local workflow, production deployment, cache clearing, PDF memory fix | CI/CD, monitoring, scheduled backups, queue workers, health checks |

### Overall estimated completion

**Approximately 78% for the current personal-finance tracking scope.**

The core workflow is usable: authenticate, import statements, categorize transactions, protect reviewed rows, and inspect spending or investment trends. The largest risks are automated test coverage, reconciliation, backups, and support for additional statement formats.

## 5. User Workflows

### Import and review transactions

1. Open the transaction import page.
2. Select a bank or institution.
3. Upload one or more CSV, TXT, or supported PDF files.
4. The importer parses each row and attempts automatic spending-type detection.
5. Existing matching unlocked transactions are updated.
6. Existing matching locked transactions are skipped so reviewed data is not overwritten.
7. New transactions are created and import errors are reported.
8. Review, recategorize, lock, or remove records from the transaction list.

### Monitor monthly spending

1. Open Monitor.
2. Select a year.
3. View one chart per spending type.
4. Click a monthly bar to open a new tab containing transactions filtered by type, month, and year.

### Review investments

1. Open Investments.
2. Select ASB or Tabung Haji.
3. Review the latest balance and its date.
4. Compare yearly growth and balance history.

## 6. Important Current Behaviors

- Analytics is still available through `/analytics`, but its sidebar link is temporarily hidden.
- Monitor chart clicks open `/transactions` in a new browser tab with `spending_type_id`, `month`, and `year` query parameters.
- Transaction imports use an identity composed of user, posted date, transaction date, detail, and bank.
- Locked matching transactions must not have their amount, balance, or spending type changed by re-import.
- Investment balances are stored on transactions through the nullable `balance` field.
- Soft-deleted records are excluded by Eloquent by default and can be recovered later if a restore workflow is added.

## 7. Known Gaps and Risks

- Parser behavior depends on the exact layout of each bank's statement. A new PDF layout can break extraction without an automated fixture test.
- Imports currently process synchronously. Large PDF files may be slow or memory intensive.
- There is no complete audit history for edits, imports, locks, category changes, or deletes.
- There is no formal reconciliation workflow to compare statement ending balances with imported balances.
- There is no visible restore screen for soft-deleted transactions.
- Automated tests do not yet cover the most financially sensitive paths.
- Production changes require careful approval, backup, deployment, cache clearing, and verification.

## 8. Recommended Additions

### Highest priority

1. **Statement reconciliation**
   - Let the user enter or extract statement opening and closing balances.
   - Compare expected and imported balances.
   - Show missing, duplicated, or mismatched transactions.

2. **Import preview and rollback**
   - Parse files before writing to the database.
   - Show new, updated, skipped-locked, duplicate, and invalid rows.
   - Commit the import only after confirmation.
   - Group rows by an import batch so the batch can be rolled back.

3. **Automated parser and lock tests**
   - Add representative CSV/PDF fixtures for every supported institution.
   - Test locked rows remain unchanged during re-import.
   - Test duplicate detection, date parsing, balance changes, and category detection.

4. **Backup and restore**
   - Schedule encrypted database backups.
   - Show backup status and retention information.
   - Add a protected restore process or documented recovery procedure.

5. **Audit log**
   - Record imports, edits, category changes, lock/unlock actions, deletes, restores, and login/security events.
   - Include timestamp, user, old value, and new value.

### Useful product features

6. **Budgets and spending limits**
   - Monthly budgets by spending type.
   - Progress bars, overspending warnings, and rollover support.

7. **Recurring transactions**
   - Detect recurring salary, bills, subscriptions, and transfers.
   - Forecast upcoming transactions.

8. **Cash-flow forecast**
   - Project future income, expenses, and account balances from recurring patterns.

9. **Transfers between accounts**
   - Link the debit and credit sides of an internal transfer so it is not counted as spending twice.

10. **Improved investment accounting**
    - Separate deposits, withdrawals, dividends, fees, unit price, units held, and market value.
    - Add contribution totals, return percentage, and target tracking.

11. **Custom date-range analytics**
    - Add daily, weekly, monthly, and yearly views.
    - Compare periods and export charts or tables.

12. **Export tools**
    - Export filtered transactions to CSV, Excel, or PDF.
    - Export charts and yearly summaries.

13. **Saved filters and views**
    - Save commonly used bank, category, account, and date filters.

14. **Notifications**
    - Alert on unusual spending, budget breaches, failed imports, low balances, and upcoming recurring payments.

15. **Mobile-friendly PWA support**
    - Installable mobile experience, offline shell, and quick manual transaction entry.

### Engineering improvements

16. **Background import jobs**
    - Move large PDF parsing to queued jobs.
    - Show progress and allow users to continue working.

17. **CI pipeline**
    - Run PHP syntax checks, Laravel tests, Pint, Blade compilation, and asset builds on every change.

18. **Observability**
    - Add structured logs, error reporting, slow-query tracking, and an application health endpoint.

19. **Database constraints and indexes**
    - Review composite indexes for user/date/bank/type queries.
    - Enforce appropriate foreign keys and uniqueness rules.

20. **API and integration layer**
    - Add a versioned API for mobile clients or integrations with budgeting tools, while keeping personal financial data protected.

## 9. Suggested Delivery Roadmap

### Phase 1: Protect and verify

- Add import fixture tests.
- Add locked-import regression tests.
- Add statement reconciliation warnings.
- Add production backups and health checks.

### Phase 2: Make importing safer

- Add import preview.
- Add import batches and rollback.
- Add parser error diagnostics.
- Add background processing for large PDFs.

### Phase 3: Improve financial planning

- Add budgets.
- Add recurring transactions.
- Add cash-flow forecasting.
- Add transfer matching.

### Phase 4: Expand reporting and investment tracking

- Add custom comparisons and exports.
- Add richer investment performance calculations.
- Re-enable and polish Analytics after its UX and test coverage are improved.

## 10. Useful Development Commands

```text
docker-compose up -d
docker-compose exec -T app php artisan migrate
docker-compose exec -T app php artisan test
docker-compose exec -T app php -l app/Http/Controllers/TransactionController.php
docker-compose exec -T app php artisan view:cache
docker-compose exec -T app npm run build
docker-compose down
```

## 11. Key Files

- `routes/web.php` - authenticated web routes
- `app/Http/Controllers/TransactionController.php` - transactions, imports, parsers, bulk operations, locking
- `app/Http/Controllers/MonitorController.php` - monitor page and chart data
- `app/Http/Controllers/InvestmentController.php` - investment summaries and charts
- `app/Http/Controllers/AnalyticsController.php` - analytics reporting
- `app/Models/Transaction.php` - transaction model and relationships
- `app/Models/Bank.php` - bank/institution model and investment flag
- `app/Models/RefSpendingType.php` - category and keyword model
- `resources/views/transactions/` - transaction screens and import UI
- `resources/views/monitor/` - spending monitor charts
- `resources/views/investments/` - investment dashboard
- `resources/views/layouts/app.blade.php` - authenticated application layout and sidebar
- `database/migrations/` - database schema history
- `tests/` - automated test suite
- `docker-compose.yml` - local services
