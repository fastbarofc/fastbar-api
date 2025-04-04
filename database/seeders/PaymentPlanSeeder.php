<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\PaymentMethod;
use App\Models\Receivable;
use Faker\Factory as Faker;
use Carbon\Carbon;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $events = Event::inRandomOrder()->take(Event::count() / 3)->get();
        $paymentMethods = PaymentMethod::all();

        foreach ($events as $event) {
            $paymentMethod = $paymentMethods->random();
            $totalAmount = $faker->randomFloat(2, 1000, 10000);
            $downPayment = $faker->randomFloat(2, 100, 1000);
            $numInstallments = $faker->numberBetween(1, 12);
            $installmentAmount = round(($totalAmount - $downPayment) / $numInstallments, 2);
            $dueDay = $faker->numberBetween(1, 28);

            // Criar PaymentPlan vinculado ao evento
            $paymentPlan = $event->paymentPlan()->create([
                'total_amount' => $totalAmount,
                'down_payment' => $downPayment,
                'num_installments' => $numInstallments,
                'installment_amount' => $installmentAmount,
                'payment_method_id' => $paymentMethod->id,
                'due_day' => $dueDay,
            ]);

            // Data inicial da primeira parcela
            $firstDueDate = Carbon::now()->day($dueDay)->format('Y-m-d');

            // Criar Receivable para a entrada, se houver
            if ($downPayment > 0) {
                Receivable::create([
                    'receivableable_id' => $event->id,
                    'receivableable_type' => Event::class,
                    'description' => "Entrada para evento #{$event->id}",
                    'amount' => $downPayment,
                    'due_date' => Carbon::now()->format('Y-m-d'),
                    'status' => 'pending',
                    'payment_method_id' => $paymentMethod->id,
                ]);
            }

            // Criar parcelas mensais
            for ($i = 0; $i < $numInstallments; $i++) {
                Receivable::create([
                    'receivableable_id' => $event->id,
                    'receivableable_type' => Event::class,
                    'description' => "Parcela " . ($i + 1) . " de {$numInstallments} do evento #{$event->id}",
                    'amount' => $installmentAmount,
                    'due_date' => Carbon::parse($firstDueDate)->addMonths($i)->format('Y-m-d'),
                    'status' => 'pending',
                    'payment_method_id' => $paymentMethod->id,
                ]);
            }
        }
    }
}
