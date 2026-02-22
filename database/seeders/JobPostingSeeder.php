<?php

namespace Database\Seeders;

use App\Models\JobPosting;
use App\Models\JobType;
use App\Models\JobLocation;
use App\Models\Department;
use App\Models\Branch;
use App\Models\User;
use App\Models\CustomQuestion;
use Illuminate\Database\Seeder;

class JobPostingSeeder extends Seeder
{
    public function run(): void
    {
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        $jobPostingData = [
            [
                'title' => 'Senior Software Engineer',
                'min_experience' => 5.0,
                'max_experience' => 8.0,
                'min_salary' => 80000,
                'max_salary' => 120000,
                'positions' => 2,
                'priority' => 'High',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => true,
                'description' => 'We are seeking a talented Senior Software Engineer to join our dynamic development team. You will be responsible for designing, developing, and maintaining high-quality software solutions. This role requires strong technical expertise, leadership skills, and the ability to work collaboratively in an agile environment. You will mentor junior developers, participate in code reviews, and contribute to architectural decisions that shape our technology stack.',
                'requirements' => 'Bachelor\'s degree in Computer Science or related field. Minimum 5 years of professional experience in web application development. Strong proficiency in PHP, Laravel framework, and modern JavaScript. Experience with MySQL database design and optimization. Solid understanding of RESTful APIs, version control (Git), and agile methodologies. Excellent problem-solving skills and attention to detail. Strong communication and teamwork abilities.',
                'benefits' => 'Comprehensive health insurance coverage for you and your family. Provident fund with company matching. Annual performance-based bonus. Professional development budget for courses and certifications. Flexible working hours and remote work options. Modern office with latest technology and equipment. Team building activities and company events. Paid time off and sick leave.',
                'skills' => ['PHP', 'Laravel', 'JavaScript', 'MySQL'],
                'applicant' => ['gender', 'date_of_birth'],
                'visibility' => ['terms_and_conditions', 'cover_letter']
            ],
            [
                'title' => 'Marketing Manager',
                'min_experience' => 3.0,
                'max_experience' => 6.0,
                'min_salary' => 60000,
                'max_salary' => 90000,
                'positions' => 1,
                'priority' => 'Medium',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'We are seeking an experienced Marketing Manager to lead our marketing initiatives and drive brand growth. You will develop and execute comprehensive marketing strategies, manage digital campaigns, and analyze market trends. This role requires creativity, strategic thinking, and strong leadership skills. You will work closely with cross-functional teams to ensure consistent brand messaging and achieve business objectives.',
                'requirements' => 'MBA in Marketing or related field preferred. Minimum 3 years of experience in digital marketing and brand management. Proven track record of successful marketing campaigns. Strong understanding of SEO, SEM, social media marketing, and content strategy. Experience with marketing analytics tools and data-driven decision making. Excellent communication, presentation, and project management skills. Creative mindset with attention to detail.',
                'benefits' => 'Comprehensive medical coverage including dental and vision. Annual leave and paid holidays. Professional training and development programs. Performance-based incentives and bonuses. Modern office environment with collaborative spaces. Employee wellness programs. Career advancement opportunities. Company-sponsored team outings and events.',
                'skills' => ['Digital Marketing', 'SEO', 'Content Strategy', 'Analytics'],
                'applicant' => ['gender'],
                'visibility' => ['terms_and_conditions']
            ],
            [
                'title' => 'HR Specialist',
                'min_experience' => 2.0,
                'max_experience' => 4.0,
                'min_salary' => 45000,
                'max_salary' => 65000,
                'positions' => 1,
                'priority' => 'Medium',
                'status' => 'Draft',
                'is_published' => false,
                'is_featured' => false,
                'description' => 'Join our HR team as an HR Specialist focusing on talent acquisition and employee relations. This remote position offers the flexibility to work from anywhere while contributing to our company culture. You will manage the full recruitment cycle, handle employee inquiries, maintain HR records, and support various HR initiatives. The ideal candidate is passionate about people, organized, and has excellent interpersonal skills.',
                'requirements' => 'Bachelor\'s degree in Human Resources, Business Administration, or related field. Minimum 2 years of experience in HR operations and recruitment. Strong knowledge of HR policies, labor laws, and best practices. Experience with applicant tracking systems and HRIS. Excellent communication and interpersonal skills. Ability to handle confidential information with discretion. Strong organizational and time management abilities. Self-motivated and able to work independently in a remote environment.',
                'benefits' => 'Remote work allowance for home office setup. Comprehensive health and wellness benefits. Flexible work schedule to maintain work-life balance. Professional development opportunities and HR certifications support. Paid vacation, sick leave, and personal days. Company-provided laptop and necessary equipment. Virtual team building activities. Annual performance reviews with salary adjustments.',
                'skills' => ['Recruitment', 'Employee Relations', 'HR Policies', 'Communication']
            ],
            [
                'title' => 'Frontend Developer',
                'min_experience' => 2.0,
                'max_experience' => 5.0,
                'min_salary' => 50000,
                'max_salary' => 75000,
                'positions' => 3,
                'priority' => 'High',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => true,
                'description' => 'We are looking for passionate Frontend Developers to join our growing team and build amazing user interfaces. You will work on cutting-edge web applications, collaborate with designers and backend developers, and contribute to creating seamless user experiences. This role offers opportunities to work with modern frameworks, participate in technical discussions, and grow your skills in a supportive environment.',
                'requirements' => 'Bachelor\'s degree in Computer Science or equivalent experience. Minimum 2 years of professional frontend development experience. Strong proficiency in React or Vue.js frameworks. Solid understanding of JavaScript, HTML5, and CSS3. Experience with responsive design and cross-browser compatibility. Knowledge of state management (Redux, Vuex) and RESTful APIs. Familiarity with version control systems (Git). Understanding of web performance optimization. Portfolio of previous work required.',
                'benefits' => 'Comprehensive health insurance for you and dependents. Annual learning and development budget for courses and conferences. Flexible working hours with option for remote work. Modern office with ergonomic workstations. Latest technology and tools. Collaborative and innovative work environment. Regular team events and hackathons. Competitive salary with annual reviews. Paid time off and holidays.',
                'skills' => ['React', 'Vue.js', 'JavaScript', 'CSS', 'HTML']
            ],
            [
                'title' => 'Data Analyst',
                'min_experience' => 1.0,
                'max_experience' => 3.0,
                'min_salary' => 40000,
                'max_salary' => 60000,
                'positions' => 2,
                'priority' => 'Medium',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'We are seeking detail-oriented Data Analysts to join our analytics team and help drive data-driven decision making across the organization. You will collect, process, and analyze large datasets to uncover insights and trends. This role involves creating reports, dashboards, and visualizations to communicate findings to stakeholders. You will work closely with various departments to understand their data needs and provide actionable recommendations.',
                'requirements' => 'Bachelor\'s degree in Statistics, Mathematics, Computer Science, or related field. Minimum 1 year of experience in data analysis or related role. Strong proficiency in SQL for data extraction and manipulation. Experience with Python or R for statistical analysis. Knowledge of data visualization tools like Tableau, Power BI, or similar. Strong analytical and problem-solving skills. Excellent attention to detail. Ability to communicate complex data insights to non-technical audiences. Experience with Excel for data analysis and reporting.',
                'benefits' => 'Comprehensive health coverage including medical, dental, and vision. Professional training programs and certifications support. Career growth opportunities within the analytics team. Collaborative work environment with experienced mentors. Modern office with latest analytics tools and software. Flexible work arrangements. Paid vacation and sick leave. Performance-based bonuses. Employee assistance programs.',
                'skills' => ['SQL', 'Python', 'Excel', 'Tableau', 'Statistics']
            ],
            [
                'title' => 'Project Manager',
                'min_experience' => 4.0,
                'max_experience' => 7.0,
                'min_salary' => 70000,
                'max_salary' => 100000,
                'positions' => 1,
                'priority' => 'High',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => true,
                'description' => 'Lead cross-functional teams to deliver projects on time.',
                'requirements' => 'PMP certification preferred, 4+ years PM experience',
                'benefits' => 'Health insurance, Bonus, Professional development',
                'skills' => ['Project Management', 'Agile', 'Scrum', 'Leadership']
            ],
            [
                'title' => 'UX Designer',
                'min_experience' => 2.0,
                'max_experience' => 5.0,
                'min_salary' => 55000,
                'max_salary' => 80000,
                'positions' => 1,
                'priority' => 'Medium',
                'status' => 'Draft',
                'is_published' => false,
                'is_featured' => false,
                'description' => 'Design intuitive user experiences for our products.',
                'requirements' => 'Bachelor in Design, portfolio required',
                'benefits' => 'Creative environment, Health benefits, Equipment allowance',
                'skills' => ['Figma', 'Sketch', 'User Research', 'Prototyping']
            ],
            [
                'title' => 'DevOps Engineer',
                'min_experience' => 3.0,
                'max_experience' => 6.0,
                'min_salary' => 65000,
                'max_salary' => 95000,
                'positions' => 2,
                'priority' => 'High',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'Manage infrastructure and deployment pipelines.',
                'requirements' => 'Bachelor in CS, AWS/Azure experience',
                'benefits' => 'Health insurance, Certification budget, Remote work',
                'skills' => ['AWS', 'Docker', 'Kubernetes', 'CI/CD', 'Linux']
            ],
            [
                'title' => 'Sales Executive',
                'min_experience' => 1.0,
                'max_experience' => 4.0,
                'min_salary' => 35000,
                'max_salary' => 55000,
                'positions' => 4,
                'priority' => 'Medium',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'Drive sales growth and build client relationships.',
                'requirements' => 'Bachelor degree, excellent communication skills',
                'benefits' => 'Commission structure, Health benefits, Travel allowance',
                'skills' => ['Sales', 'Communication', 'CRM', 'Negotiation']
            ],
            [
                'title' => 'Quality Assurance Engineer',
                'min_experience' => 2.0,
                'max_experience' => 5.0,
                'min_salary' => 45000,
                'max_salary' => 70000,
                'positions' => 2,
                'priority' => 'Medium',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'Ensure product quality through comprehensive testing.',
                'requirements' => 'Bachelor in CS, testing experience required',
                'benefits' => 'Health insurance, Learning opportunities, Flexible schedule',
                'skills' => ['Manual Testing', 'Automation', 'Selenium', 'API Testing']
            ],
            [
                'title' => 'Business Analyst',
                'min_experience' => 2.0,
                'max_experience' => 5.0,
                'min_salary' => 50000,
                'max_salary' => 75000,
                'positions' => 1,
                'priority' => 'Medium',
                'status' => 'Draft',
                'is_published' => false,
                'is_featured' => false,
                'description' => 'Analyze business processes and recommend improvements.',
                'requirements' => 'MBA preferred, analytical mindset required',
                'benefits' => 'Health coverage, Professional development, Bonus',
                'skills' => ['Business Analysis', 'Requirements Gathering', 'Process Improvement']
            ],
            [
                'title' => 'Content Writer',
                'min_experience' => 1.0,
                'max_experience' => 3.0,
                'min_salary' => 30000,
                'max_salary' => 45000,
                'positions' => 2,
                'priority' => 'Low',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'Create engaging content for various marketing channels.',
                'requirements' => 'Bachelor in English/Journalism, writing portfolio',
                'benefits' => 'Creative environment, Health benefits, Flexible hours',
                'skills' => ['Content Writing', 'SEO Writing', 'Research', 'Editing']
            ],
            [
                'title' => 'Network Administrator',
                'min_experience' => 3.0,
                'max_experience' => 6.0,
                'min_salary' => 55000,
                'max_salary' => 80000,
                'positions' => 1,
                'priority' => 'Medium',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'Maintain and optimize network infrastructure.',
                'requirements' => 'Bachelor in IT, networking certifications preferred',
                'benefits' => 'Health insurance, Certification support, Equipment',
                'skills' => ['Networking', 'Cisco', 'Security', 'Troubleshooting']
            ],
            [
                'title' => 'Financial Analyst',
                'min_experience' => 2.0,
                'max_experience' => 4.0,
                'min_salary' => 45000,
                'max_salary' => 65000,
                'positions' => 1,
                'priority' => 'Medium',
                'status' => 'Draft',
                'is_published' => false,
                'is_featured' => false,
                'description' => 'Analyze financial data and prepare reports.',
                'requirements' => 'Bachelor in Finance/Accounting, Excel proficiency',
                'benefits' => 'Health coverage, Retirement plan, Professional development',
                'skills' => ['Financial Analysis', 'Excel', 'Reporting', 'Budgeting']
            ],
            [
                'title' => 'Customer Support Representative',
                'min_experience' => 0.0,
                'max_experience' => 2.0,
                'min_salary' => 25000,
                'max_salary' => 35000,
                'positions' => 5,
                'priority' => 'Low',
                'status' => 'Published',
                'is_published' => true,
                'is_featured' => false,
                'description' => 'Provide excellent customer service and support.',
                'requirements' => 'High school diploma, excellent communication skills',
                'benefits' => 'Health benefits, Training provided, Career growth',
                'skills' => ['Customer Service', 'Communication', 'Problem Solving', 'Patience']
            ]
        ];

        foreach ($companies as $company) {
            $jobTypes = JobType::where('created_by', $company->id)->get();
            $jobLocations = JobLocation::where('created_by', $company->id)->get();
            $departments = Department::where('created_by', $company->id)->get();
            $branches = Branch::where('created_by', $company->id)->get();
            $customQuestions = CustomQuestion::where('created_by', $company->id)->pluck('id')->toArray();

            if ($jobTypes->isEmpty() || $jobLocations->isEmpty()) {
                $this->command->warn('Missing job types or locations for company: ' . $company->name);
                continue;
            }

            foreach ($jobPostingData as $index => $data) {
                // Generate job code manually since creatorId() requires auth
                $jobCode = 'JOB-' . $company->id . '-' . str_pad(($index + 1), 5, '0', STR_PAD_LEFT);

                if (JobPosting::where('job_code', $jobCode)->exists()) {
                    continue;
                }

                // Dynamically select job type, location, branch and department
                $jobType = $jobTypes->skip($index % $jobTypes->count())->first();
                $jobLocation = $jobLocations->skip($index % $jobLocations->count())->first();
                $branch = $branches->skip($index % max(1, $branches->count()))->first();


                // Get departments for the selected branch
                $branchDepartments = $departments->where('branch_id', $branch?->id);
                $department = $branchDepartments->isNotEmpty() ?
                    $branchDepartments->skip($index % $branchDepartments->count())->first() :
                    null;

                $applicationDeadline = $data['is_published'] ? date('Y-m-d', strtotime('+30 days')) : null;
                $publishDate = $data['is_published'] ? now() : null;
                $startDate = $data['is_published'] ? date('Y-m-d', strtotime('+7 days')) : null;

                // Get random custom questions (2-3 questions)
                $selectedQuestions = !empty($customQuestions) ? array_slice($customQuestions, 0, rand(2, min(3, count($customQuestions)))) : null;

                try {
                    JobPosting::create([
                        'job_code' => $jobCode,
                        'code' => strtoupper(uniqid('JP')),
                        'title' => $data['title'],
                        'job_type_id' => $jobType->id,
                        'location_id' => $jobLocation->id,
                        'department_id' => $department?->id,
                        'branch_id' => $branch?->id,
                        'min_experience' => $data['min_experience'],
                        'max_experience' => $data['max_experience'],
                        'min_salary' => $data['min_salary'],
                        'max_salary' => $data['max_salary'],
                        'positions' => $data['positions'],
                        'priority' => $data['priority'],
                        'description' => $data['description'],
                        'requirements' => $data['requirements'],
                        'benefits' => $data['benefits'],
                        'skills' => $data['skills'],
                        'applicant' => $data['applicant'] ?? null,
                        'visibility' => $data['visibility'] ?? null,
                        'custom_question' => $selectedQuestions,
                        'start_date' => $startDate,
                        'application_deadline' => $applicationDeadline,
                        'is_published' => $data['is_published'],
                        'publish_date' => $publishDate,
                        'is_featured' => $data['is_featured'],
                        'status' => $data['status'],
                        'created_by' => $company->id,
                    ]);
                } catch (\Exception $e) {
                    $this->command->error('Failed to create job posting: ' . $data['title'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('JobPosting seeder completed successfully!');
    }
}
