#!/usr/bin/env bash

OUTPUT="administration-expense-context.txt"

: > "$OUTPUT"

FILES=(
    "app/Models/AppSetting.php"
    "app/Models/Loan.php"
    "app/Models/LoanInstallment.php"
    "app/Models/InstallmentPayment.php"
    "app/Models/CashTransaction.php"

    "app/Http/Controllers/LoanController.php"
    "app/Http/Controllers/ManualInstallmentController.php"
    "app/Http/Controllers/InstallmentController.php"

    "app/Services/CashLedgerService.php"
    "app/Services/Accounting/AccountingJournalService.php"

    "resources/views/loans/create.blade.php"
    "resources/views/loans/show.blade.php"
    "resources/views/installments/manual-create.blade.php"
    "resources/views/installments/index.blade.php"

    "routes/web.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

for file in app/Http/Controllers/*Cash*Controller.php; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

for file in resources/views/cash*/*.blade.php resources/views/*cash*/*.blade.php; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

for file in \
    database/migrations/*loan* \
    database/migrations/*installment* \
    database/migrations/*cash* \
    database/migrations/*accounting* \
    database/migrations/*journal*; do

    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

for file in database/seeders/*Account*Seeder.php database/seeders/*Accounting*Seeder.php; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

echo "Berhasil membuat $OUTPUT"
