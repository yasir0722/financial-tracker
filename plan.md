# Financial Tracker Plan

## Recommended Next Feature: Statement Reconciliation

Add a reconciliation workflow that compares bank transactions with investment statements and highlights missing or inconsistent data.

### User Flow

1. Import a CIMB CSV, Tabung Haji PDF, or ASB PDF.
2. Automatically detect investment contributions from CIMB descriptions.
3. Match CIMB contributions to Tabung Haji or ASB transactions by amount and date.
4. Show unmatched contributions, duplicate imports, and balance gaps.
5. Let the user confirm or ignore each match.
6. Display the last statement date and warn when an investment is outdated.

### Useful Screens

- Reconciliation dashboard
- Unmatched investment contributions
- Statement upload history
- Balance timeline with missing-period markers
- Confirmed and ignored matches

### Suggested Data Additions

- `statement_imports`: bank, filename, period start, period end, imported date
- `investment_matches`: CIMB transaction, investment transaction, match status
- `statement_period_start` and `statement_period_end` on imported statement records
- `source_file` on transactions for traceability

### Important Rules

- Never create an investment balance from a CIMB contribution alone.
- Use Tabung Haji or ASB statement balances as the authoritative investment balance.
- Keep imported transactions idempotent so re-uploading a statement does not duplicate rows.
- Show absolute increase when the starting balance is zero; show percentage growth only when the starting balance is greater than zero.

## Other Good Additions

### 1. Monthly Budgeting

- Set budgets by spending type.
- Show spent, remaining, and percentage used.
- Warn when a category exceeds its budget.

### 2. Recurring Transaction Detection

- Detect recurring salary, rent, bills, and investment contributions.
- Show expected upcoming transactions.
- Flag a recurring payment that did not appear this month.

### 3. Investment Goal Tracking

- Create goals such as `RM50,000 Tabung Haji` or `RM10,000 ASB`.
- Show current balance, target, remaining amount, and projected completion date.

### 4. Import History and Undo

- Record every uploaded file and import result.
- Show how many rows were created, updated, skipped, or rejected.
- Allow undo for a complete import batch using soft deletes.

### 5. Better Reports

- Monthly income versus expenses.
- Net worth across banks and investments.
- Annual investment contributions versus profit or distribution.
- CSV and PDF export for selected date ranges.

## Suggested Build Order

1. Statement reconciliation
2. Import history and undo
3. Investment goals
4. Monthly budgets
5. Recurring transaction detection
6. Advanced reports and exports
