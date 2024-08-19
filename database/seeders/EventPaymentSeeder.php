<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventPayment;
use App\Models\ParthenonBankAccount;
use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::all();  

        foreach ($events as $event) {
            EventPayment::create([
                'event_id' => $event->id,
                'parthenon_bank_account_id' => ParthenonBankAccount::inRandomOrder()->first()->id,
                'payment_method_id' => PaymentMethod::inRandomOrder()->first()->id,
                'value' => fake()->randomFloat(2, 100, 10000),  
                'date' => fake()->dateTime(), 
                'comment' => fake()->optional()->sentence(),  
            ]);
        }
    }
}
