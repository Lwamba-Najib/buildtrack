<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Insert 50 records into the 'transactions' table
        foreach (range(1, 50) as $index) {
            // Randomly decide whether this transaction is credit or debit
            $transactionType = $faker->randomElement(['credit', 'debit']);

            // Logic for credit and debit being mutually exclusive
            if ($transactionType === 'debit') {
                $transactionCredit = 0;
                $transactionDebit = 1000000; // Debit is 1,000,000
                $transactionNarration = 'Credited Beneficiary';
            } else {
                $transactionCredit = $faker->randomElement([5000000, 10000000]); // Credit is either 5,000,000 or 10,000,000
                $transactionDebit = 0; // No debit when there's credit
                $transactionNarration = 'Credited Client';
            }

            DB::table('transactions')->insert([
                'transaction_type' => $transactionType,
                'transaction_narration' => $transactionNarration,
                'transaction_method' => $faker->randomElement(['bank', 'cash', 'credit_card', 'online']),
                'transaction_id' => $faker->unique()->uuid(),
                'transaction_credit' => $transactionCredit,
                'transaction_debit' => $transactionDebit,
                'transaction_date' => $faker->dateTimeBetween('2024-10-01', '2024-11-16')->format('Y-m-d'),
                'transaction_time' => $faker->time('H:i:s'),
                'environment' => 'TEST', // Environment set to TEST
                'client_id' => $faker->numberBetween(1, 10), // Assuming clients are from 1 to 10
                'created_by' => $faker->numberBetween(1, 10), // Assuming users are from 1 to 10
                'updated_by' => $faker->numberBetween(1, 10), // Assuming users are from 1 to 10
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'deleted_at' => null, // assuming no soft deletes at seeding time
            ]);
        }
    }
}
