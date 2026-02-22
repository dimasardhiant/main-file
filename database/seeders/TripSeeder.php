<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TripSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        // Trip purposes and destinations
        $tripPurposes = [
            'Client Meeting',
            'Conference Attendance',
            'Training Program',
            'Site Visit',
            'Business Development',
            'Project Implementation',
            'Vendor Meeting',
            'Market Research',
            'Team Building',
            'Product Launch'
        ];

        $destinations = [
            'New York, USA',
            'London, UK',
            'Tokyo, Japan',
            'Singapore',
            'Dubai, UAE',
            'Mumbai, India',
            'Delhi, India',
            'Bangalore, India',
            'Chennai, India',
            'Pune, India',
            'Sydney, Australia',
            'Toronto, Canada'
        ];

        // Trip descriptions based on purpose
        $tripDescriptions = [
            'Client Meeting' => 'Meeting with key clients to discuss project requirements, deliverables, and future business opportunities.',
            'Conference Attendance' => 'Attending industry conference to gain insights on latest trends, technologies, and networking opportunities.',
            'Training Program' => 'Participating in professional development training to enhance skills and knowledge in specific domain areas.',
            'Site Visit' => 'Visiting client site or project location to assess requirements, progress, and coordinate implementation activities.',
            'Business Development' => 'Exploring new business opportunities, meeting potential clients, and expanding market presence in target regions.',
            'Project Implementation' => 'On-site project implementation, system deployment, and providing technical support to client teams.',
            'Vendor Meeting' => 'Meeting with vendors and suppliers to discuss partnerships, contracts, and service level agreements.',
            'Market Research' => 'Conducting market research, competitor analysis, and gathering insights for strategic business decisions.',
            'Team Building' => 'Participating in team building activities and workshops to improve collaboration and team dynamics.',
            'Product Launch' => 'Attending product launch events, demonstrations, and marketing activities in target markets.'
        ];

        foreach ($companies as $company) {
            // Get employees for this company
            $employees = User::where('type', 'employee')
                ->where('created_by', $company->id)
                ->get();

            if ($employees->isEmpty()) {
                $this->command->warn('No employees found for company: ' . $company->name . '. Please run EmployeeSeeder first.');
                continue;
            }

            // Get managers/HR for approval
            $approvers = User::whereIn('type', ['manager', 'hr'])
                ->where('created_by', $company->id)
                ->get();

            // Create 8-15 trips for this company
            $tripCount = rand(8, 15);

            for ($i = 0; $i < $tripCount; $i++) {
                $employee = $employees->take(5)->random();
                $purpose = $faker->randomElement($tripPurposes);
                $destination = $faker->randomElement($destinations);
                $description = $tripDescriptions[$purpose];

                $startDate = $faker->dateTimeBetween('-6 months', '+3 months');
                $endDate = (clone $startDate)->modify('+' . rand(1, 7) . ' days');

                $status = $faker->randomElement(['planned', 'ongoing', 'completed', 'cancelled']);
                $approver = $approvers->isNotEmpty() ? $approvers->random() : null;

                $advanceAmount = $faker->optional(0.7)->randomFloat(2, 5000, 50000);
                $totalExpenses = $status === 'completed' ? $faker->randomFloat(2, 3000, 60000) : null;

                try {
                    $trip = Trip::create([
                        'employee_id' => $employee->id,
                        'purpose' => $purpose,
                        'destination' => $destination,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'description' => $description,
                        'expected_outcomes' => $faker->sentence(12),
                        'status' => $status,
                        'documents' => randomImage(),
                        'advance_amount' => $advanceAmount,
                        'advance_status' => $advanceAmount ? $faker->randomElement(['requested', 'approved', 'paid', 'reconciled']) : null,
                        'total_expenses' => $totalExpenses,
                        'reimbursement_status' => $totalExpenses ? $faker->randomElement(['pending', 'approved', 'paid']) : null,
                        'approved_by' => $status !== 'planned' ? $approver?->id : null,
                        'approved_at' => $status !== 'planned' ? $faker->dateTimeBetween('-6 months', 'now') : null,
                        'trip_report' => $status === 'completed' ? $faker->paragraph(3) : null,
                        'created_by' => $company->id,
                    ]);

                    // Create trip expenses for completed trips
                    if ($status === 'completed') {
                        $this->createTripExpenses($trip, $faker);
                    }
                } catch (\Exception $e) {
                    $this->command->error($e->getMessage());
                    continue;
                }
            }
        }

        $this->command->info('Trip seeder completed successfully!');
    }

    /**
     * Create trip expenses for a trip
     */
    private function createTripExpenses($trip, $faker)
    {
        $expenseTypes = [
            'Transportation' => ['Flight tickets', 'Train tickets', 'Taxi fare', 'Bus fare', 'Car rental'],
            'Accommodation' => ['Hotel charges', 'Guest house', 'Service apartment', 'Lodging'],
            'Meals' => ['Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Business meal'],
            'Communication' => ['Phone calls', 'Internet charges', 'Mobile roaming'],
            'Miscellaneous' => ['Visa fees', 'Travel insurance', 'Airport parking', 'Tips', 'Laundry']
        ];

        // Create 3-5 expenses per trip (max = available expense types)
        $expenseCount = rand(3, min(5, count($expenseTypes)));
        $selectedExpenseTypes = $faker->randomElements(array_keys($expenseTypes), $expenseCount);

        foreach ($selectedExpenseTypes as $expenseType) {
            $expenseDescription = $faker->randomElement($expenseTypes[$expenseType]);

            $startDate = new \DateTime($trip->start_date);
            $endDate = new \DateTime($trip->end_date);
            // Ensure start date is before end date
            if ($startDate >= $endDate) {
                $expenseDate = $startDate;
            } else {
                $expenseDate = $faker->dateTimeBetween($startDate, $endDate);
            }

            try {
                TripExpense::create([
                    'trip_id' => $trip->id,
                    'expense_type' => $expenseType,
                    'expense_date' => $expenseDate->format('Y-m-d'),
                    'amount' => $faker->randomFloat(2, 100, 5000),
                    'currency' => $faker->randomElement(['USD', 'EUR', 'INR', 'GBP']),
                    'description' => $expenseDescription,
                    'receipt' => randomImage(),
                    'is_reimbursable' => $faker->boolean(90),
                    'status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                    'created_by' => $trip->employee_id,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
