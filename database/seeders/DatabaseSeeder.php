<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\EventNote;
use App\Models\OfficeBudget;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CollaboratorSeeder::class,
            EventPlaceSeeder::class,
            PartnerSeeder::class,
            MaterialSeeder::class,
            DecoratorSeeder::class,
            KitSeeder::class,
            ProductSeeder::class,
            ClientSeeder::class,
            CeremonialSeeder::class,
            EventSeeder::class,
            EventGiftSeeder::class,
            BlockedDateSeeder::class,
            UtensilSeeder::class,
            StockSeeder::class,
            ProductGroupSeeder::class,
            EventKitSeeder::class,
            EventNoteSeeder::class,
            EventBatchSeeder::class,
            BreakItemSeeder::class,
            HowDidYouHearSeeder::class,
            CompetingCompanySeeder::class,
            LostReasonSeeder::class,
            ParthenonBankAccountSeeder::class,
            FailureSeeder::class,
            PaymentMethodSeeder::class,
            OfficeBudgetSeeder::class,
            EventPaymentSeeder::class,
            SupplierSeeder::class,
            FinancialOperationSeeder::class,
            PayableSeeder::class,
            ReceivableSeeder::class,


        ]);
    }
}
