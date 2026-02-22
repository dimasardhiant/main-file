<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        $currentYear = date('Y');
        
        // Fixed holidays for consistent data
        $holidays = [
            [
                'name' => 'New Year\'s Day',
                'start_date' => $currentYear . '-01-01',
                'end_date' => null,
                'category' => 'National',
                'description' => 'Celebration of the beginning of the new calendar year',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Republic Day',
                'start_date' => $currentYear . '-01-26',
                'end_date' => null,
                'category' => 'National',
                'description' => 'Commemorates the adoption of the Constitution of India',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Independence Day',
                'start_date' => $currentYear . '-08-15',
                'end_date' => null,
                'category' => 'National',
                'description' => 'Celebrates the independence from British colonial rule',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Gandhi Jayanti',
                'start_date' => $currentYear . '-10-02',
                'end_date' => null,
                'category' => 'National',
                'description' => 'Birthday of Mahatma Gandhi, Father of the Nation',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Diwali',
                'start_date' => $currentYear . '-11-01',
                'end_date' => null,
                'category' => 'Religious',
                'description' => 'Festival of lights celebrated by Hindu, Sikh, and Jain communities',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Holi',
                'start_date' => $currentYear . '-03-25',
                'end_date' => null,
                'category' => 'Religious',
                'description' => 'Festival of colors celebrating the arrival of spring',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Eid al-Fitr',
                'start_date' => $currentYear . '-04-10',
                'end_date' => null,
                'category' => 'Religious',
                'description' => 'Islamic festival marking the end of Ramadan fasting period',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Christmas Day',
                'start_date' => $currentYear . '-12-25',
                'end_date' => null,
                'category' => 'Religious',
                'description' => 'Christian festival celebrating the birth of Jesus Christ',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Good Friday',
                'start_date' => $currentYear . '-03-29',
                'end_date' => null,
                'category' => 'Religious',
                'description' => 'Christian observance commemorating the crucifixion of Jesus Christ',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Company Foundation Day',
                'start_date' => $currentYear . '-06-15',
                'end_date' => null,
                'category' => 'Company Specific',
                'description' => 'Annual celebration of company establishment and achievements',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Annual Team Outing',
                'start_date' => $currentYear . '-09-20',
                'end_date' => $currentYear . '-09-21',
                'category' => 'Company Specific',
                'description' => 'Two-day company team building and recreational activities',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => false
            ],
            [
                'name' => 'Employee Appreciation Day',
                'start_date' => $currentYear . '-05-10',
                'end_date' => null,
                'category' => 'Company Specific',
                'description' => 'Special day to recognize and appreciate employee contributions',
                'is_recurring' => true,
                'is_paid' => true,
                'is_half_day' => true
            ]
        ];

        foreach ($companies as $company) {
            // Get branches for this company
            $branches = Branch::where('created_by', $company->id)->get();

            foreach ($holidays as $holidayData) {
                // Check if holiday already exists for this company
                if (Holiday::where('name', $holidayData['name'])->where('created_by', $company->id)->exists()) {
                    continue;
                }

                try {
                    $holiday = Holiday::create([
                        'name' => $holidayData['name'],
                        'start_date' => $holidayData['start_date'],
                        'end_date' => $holidayData['end_date'],
                        'category' => $holidayData['category'],
                        'description' => $holidayData['description'],
                        'is_recurring' => $holidayData['is_recurring'],
                        'is_paid' => $holidayData['is_paid'],
                        'is_half_day' => $holidayData['is_half_day'],
                        'created_by' => $company->id,
                    ]);

                    // Attach holiday to all branches of the company
                    if ($branches->isNotEmpty()) {
                        $holiday->branches()->attach($branches->pluck('id'));
                    }
                } catch (\Exception $e) {
                    $this->command->error('Failed to create holiday: ' . $holidayData['name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Holiday seeder completed successfully!');
    }
}
