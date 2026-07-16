#!/usr/bin/env bash

OUTPUT="manual-installment-context.txt"

: > "$OUTPUT"

FILES=(
    "app/Models/Loan.php"
    "app/Models/LoanInstallment.php"
    "app/Models/InstallmentPayment.php"
    "app/Http/Controllers/LoanController.php"
    "app/Services/CashLedgerService.php"
    "app/Services/Accounting/AccountingJournalService.php"
    "resources/views/installments/index.blade.php"
    "resources/views/loans/show.blade.php"
    "routes/web.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

for file in app/Http/Controllers/*Installment*Controller.php; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

for file in database/migrations/*loan* database/migrations/*installment*; do
    if [ -f "$file" ]; then
        printf "\n\n===== %s =====\n" "$file" >> "$OUTPUT"
        cat "$file" >> "$OUTPUT"
    fi
done

echo "Berhasil membuat $OUTPUT"
