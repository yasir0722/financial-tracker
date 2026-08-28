# Database Safety

This application contains personal financial data. Destructive database commands are intentionally blocked in `app/Console/Kernel.php`.

## Blocked Commands

The following commands exit without touching the database:

```text
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan migrate:rollback
php artisan migrate --seed
php artisan db:wipe
```

This protection applies through Docker, VS Code tasks, production Artisan calls, and direct CLI usage because it is enforced inside Laravel's console kernel.

## Allowed Database Operation

Normal additive migrations remain available:

```text
docker-compose exec -T app php artisan migrate --force
```

Review the migration file and create a backup before running it in production. Do not use `--seed` on production data.

The following seed command remains available for intentional, manually reviewed reference-data updates:

```text
php artisan db:seed --class=BankSeeder
php artisan db:seed --class=UserSeeder
```

Seeding is not run automatically by the application or by normal migrations.

## Docker Data Safety

Use this to stop services without removing the MySQL volume:

```text
docker-compose down
```

Never use `docker-compose down -v` for this project. The `-v` flag can remove the persistent MySQL volume and erase the local database.

Do not run these commands against this project:

```text
docker-compose down -v
docker volume rm financial-tracker_db_data
docker system prune --volumes
```

## Recovery Procedure

1. Stop and verify the application before any database change.
2. Take a MySQL dump or provider snapshot.
3. Review the migration and its `down()` method.
4. Run only the required additive migration.
5. Verify users, banks, transactions, balances, and maintenance records.
6. Keep the backup until the application has been verified.

The application does not provide an emergency bypass for blocked destructive commands. If a new database must be created, use a separate development project or database rather than deleting this application's data.
