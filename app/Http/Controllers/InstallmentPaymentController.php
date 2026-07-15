<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use Illuminate\View\View;

class InstallmentPaymentController extends Controller
{
    public function show(
        InstallmentPayment $installmentPayment
    ): View {
        $installmentPayment->load([
            'installment.loan.member',
            'user:id,name',
        ]);

        return view(
            'installment-payments.show',
            compact('installmentPayment')
        );
    }
}
