<?php

namespace Database\Seeders;

use App\Models\Award;
use App\Models\AwardType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class AwardSeeder extends Seeder
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

        // Gift options for awards
        $gifts = [
            'Certificate of Excellence',
            'Trophy',
            'Medal',
            'Gift Voucher',
            'Plaque',
            'Watch',
            'Laptop Bag',
            'Bonus Cash',
            'Extra Leave Days',
            'Dinner Voucher'
        ];

        // Award descriptions based on award types
        $awardDescriptions = [
            'Employee of the Month' => [
                'Recognized for exceptional performance and dedication in achieving monthly targets',
                'Outstanding contribution to team productivity and maintaining high work standards',
                'Demonstrated excellent customer service and positive attitude throughout the month'
            ],
            'Employee of the Year' => [
                'Consistently delivered exceptional results and exceeded expectations throughout the year',
                'Showed remarkable leadership qualities and mentored junior team members effectively',
                'Achieved all annual goals while maintaining highest quality standards'
            ],
            'Excellence Award' => [
                'Demonstrated excellence in project execution and delivered outstanding results',
                'Maintained highest quality standards and attention to detail in all assignments',
                'Consistently exceeded performance expectations and set new benchmarks'
            ],
            'Innovation Award' => [
                'Developed innovative solution that improved process efficiency by 30%',
                'Introduced creative approach that reduced operational costs significantly',
                'Implemented new technology that enhanced team productivity and workflow'
            ],
            'Leadership Award' => [
                'Effectively led cross-functional team and delivered project ahead of schedule',
                'Mentored team members and contributed to their professional development',
                'Demonstrated exceptional leadership during challenging project situations'
            ],
            'Customer Service Award' => [
                'Achieved highest customer satisfaction rating and received excellent feedback',
                'Resolved complex customer issues with professionalism and efficiency',
                'Built strong client relationships resulting in increased business opportunities'
            ],
            'Team Player Award' => [
                'Collaborated effectively across departments and facilitated smooth project execution',
                'Supported team members during peak workload and maintained positive team dynamics',
                'Contributed to team success through excellent cooperation and communication skills'
            ],
            'Achievement Award' => [
                'Successfully completed challenging project within tight deadline and budget constraints',
                'Achieved 120% of assigned targets and contributed significantly to company revenue',
                'Accomplished major milestone that had positive impact on business growth'
            ],
            'Long Service Award' => [
                'Completed 5 years of dedicated service with consistent performance and loyalty',
                'Demonstrated unwavering commitment and contributed to organizational stability',
                'Served as valuable team member for 10 years with exemplary attendance record'
            ],
            'Safety Award' => [
                'Maintained zero safety incidents and promoted safety awareness among colleagues',
                'Implemented safety protocols that reduced workplace accidents by 50%',
                'Demonstrated exceptional commitment to workplace safety and emergency preparedness'
            ],
            'Quality Award' => [
                'Achieved zero defect rate and maintained highest quality standards in deliverables',
                'Implemented quality improvement measures that enhanced overall product quality',
                'Consistently delivered error-free work and exceeded quality benchmarks'
            ],
            'Sales Achievement Award' => [
                'Exceeded annual sales target by 150% and acquired 25 new major clients',
                'Generated highest revenue in company history through strategic sales initiatives',
                'Achieved outstanding sales performance and contributed to market expansion'
            ]
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

            // Get award types for this company
            $awardTypes = AwardType::where('created_by', $company->id)->get();

            if ($awardTypes->isEmpty()) {
                $this->command->warn('No award types found for company: ' . $company->name . '. Please run AwardTypeSeeder first.');
                continue;
            }

            // Create 10-15 awards for this company
            $awardCount = rand(5, 7);

            for ($i = 0; $i < $awardCount; $i++) {
                $employee = $employees->take(7)->random();
                $awardType = $awardTypes->random();

                try {
                    Award::create([
                        'employee_id' => $employee->id,
                        'award_type_id' => $awardType->id,
                        'award_date' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                        'gift' => $faker->randomElement($gifts),
                        'monetary_value' => $faker->optional(0.6)->randomFloat(2, 500, 10000),
                        'description' => $this->getAwardDescription($awardType->name, $awardDescriptions),
                        'certificate' => randomImage(),
                        'photo' => randomImage(),
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create award for employee: ' . $employee->name . ' in company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('Award seeder completed successfully!');
    }

    /**
     * Get award description based on award type
     */
    private function getAwardDescription($awardTypeName, $awardDescriptions)
    {
        if (isset($awardDescriptions[$awardTypeName])) {
            $descriptions = $awardDescriptions[$awardTypeName];
            return $descriptions[array_rand($descriptions)];
        }

        // Default description if award type not found
        return 'Recognized for outstanding performance and valuable contribution to the organization';
    }
}
