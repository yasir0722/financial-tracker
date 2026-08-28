# Financial Tracker Safety Rules

This application contains personal financial data. Preserve existing users, banks, transactions, balances, locked transactions, and car-maintenance records.

Never run or recommend these commands unless the user explicitly requests a destructive reset and confirms the data loss:

- `php artisan migrate:fresh`
- `php artisan migrate:refresh`
- `php artisan migrate:reset`
- `php artisan migrate:rollback`
- `php artisan migrate --seed`
- `php artisan db:wipe`
- `docker-compose down -v`
- `docker volume rm ...`
- `docker system prune --volumes`

Normal additive migrations may use `php artisan migrate --force` after review. Manual reference-data seeding is allowed only when explicitly requested, for example `php artisan db:seed --class=BankSeeder`. Never run `db:seed` automatically.

Before any production database operation, request explicit user approval, recommend a backup, and verify users, banks, transactions, balances, and maintenance records afterward.