<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->date('down_payment_due_date')->nullable()->after('down_payment');
        });

        $paymentPlans = DB::table('payment_plans')
            ->whereNotNull('down_payment')
            ->where('down_payment', '>', 0)
            ->get();

        foreach ($paymentPlans as $plan) {
            $firstInstallment = DB::table('payment_plan_installments')
                ->where('payment_plan_id', $plan->id)
                ->whereNull('deleted_at')
                ->orderBy('installment_number', 'asc')
                ->first();

            if ($firstInstallment) {
                DB::table('payment_plans')
                    ->where('id', $plan->id)
                    ->update(['down_payment_due_date' => $firstInstallment->due_date]);
            } else {
                $today = Carbon::now();
                $dueDay = $plan->due_day;
                $dueDate = Carbon::create($today->year, $today->month, min($dueDay, $today->daysInMonth));

                DB::table('payment_plans')
                    ->where('id', $plan->id)
                    ->update(['down_payment_due_date' => $dueDate->format('Y-m-d')]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('payment_plans', function (Blueprint $table) {
            $table->dropColumn('down_payment_due_date');
        });
    }
};
