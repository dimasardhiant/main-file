<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\CandidateSource;
use App\Models\User;
use App\Models\CustomQuestion;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
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
        
        // 30 candidates: 5 Offer, 5 New, 5 Offer, 5 Interview, 5 Screening, 5 Offer
        $candidates = [
            ['first_name' => 'Rajesh', 'last_name' => 'Kumar', 'email' => 'rajesh.kumar@email.com', 'phone' => '+91-9876543210', 'gender' => 'male', 'date_of_birth' => '1990-05-15', 'address' => '123 MG Road', 'city' => 'Bangalore', 'state' => 'Karnataka', 'zip_code' => '560001', 'country' => 'India', 'current_company' => 'Tech Solutions Pvt Ltd', 'current_position' => 'Senior Developer', 'experience_years' => 6, 'current_salary' => 95000, 'expected_salary' => 120000, 'notice_period' => '2 months', 'skills' => 'JavaScript, React, Node.js, Python, SQL, MongoDB', 'education' => 'B.Tech in Computer Science from IIT Delhi', 'portfolio_url' => 'https://rajeshkumar.dev', 'linkedin_url' => 'https://linkedin.com/in/rajeshkumar', 'coverletter_message' => 'I am excited to apply for this position and bring my 6 years of experience in software development.', 'status' => 'Offer', 'source' => 'LinkedIn'],
            ['first_name' => 'Priya', 'last_name' => 'Sharma', 'email' => 'priya.sharma@email.com', 'phone' => '+91-9876543211', 'gender' => 'female', 'date_of_birth' => '1992-08-22', 'address' => '456 Park Street', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'zip_code' => '400001', 'country' => 'India', 'current_company' => 'Digital Marketing Agency', 'current_position' => 'Marketing Manager', 'experience_years' => 4, 'current_salary' => 70000, 'expected_salary' => 85000, 'notice_period' => '1 month', 'skills' => 'Digital Marketing, SEO, SEM, Social Media, Analytics', 'education' => 'MBA in Marketing from Mumbai University', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/priyasharma', 'coverletter_message' => 'With 4 years of experience in digital marketing, I am confident I can contribute to your team.', 'status' => 'Offer', 'source' => 'Naukri.com'],
            ['first_name' => 'Amit', 'last_name' => 'Patel', 'email' => 'amit.patel@email.com', 'phone' => '+91-9876543212', 'current_company' => 'HR Consultancy Services', 'current_position' => 'HR Specialist', 'experience_years' => 3, 'current_salary' => 60000, 'expected_salary' => 75000, 'notice_period' => '1 month', 'skills' => 'Recruitment, Employee Relations, Performance Management', 'education' => 'Masters in Human Resources from Pune University', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/amitpatel', 'status' => 'Offer', 'source' => 'Employee Referral'],
            ['first_name' => 'Sneha', 'last_name' => 'Reddy', 'email' => 'sneha.reddy@email.com', 'phone' => '+91-9876543213', 'current_company' => 'Financial Services Ltd', 'current_position' => 'Financial Analyst', 'experience_years' => 2, 'current_salary' => 55000, 'expected_salary' => 70000, 'notice_period' => '2 months', 'skills' => 'Financial Analysis, Excel, SQL, Financial Modeling', 'education' => 'CA from ICAI, B.Com from Osmania University', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/snehareddy', 'status' => 'Offer', 'source' => 'Company Website'],
            ['first_name' => 'Vikram', 'last_name' => 'Singh', 'email' => 'vikram.singh@email.com', 'phone' => '+91-9876543214', 'current_company' => 'Operations Excellence Corp', 'current_position' => 'Operations Executive', 'experience_years' => 5, 'current_salary' => 80000, 'expected_salary' => 100000, 'notice_period' => '3 months', 'skills' => 'Operations Management, Process Improvement, Team Leadership', 'education' => 'MBA in Operations from Bangalore University', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/vikramsingh', 'status' => 'Offer', 'source' => 'Indeed'],
            ['first_name' => 'Kavya', 'last_name' => 'Nair', 'email' => 'kavya.nair@email.com', 'phone' => '+91-9876543215', 'current_company' => 'Customer Care Solutions', 'current_position' => 'Customer Support Lead', 'experience_years' => 3, 'current_salary' => 50000, 'expected_salary' => 65000, 'notice_period' => '1 month', 'skills' => 'Customer Service, CRM, Communication, Problem Solving', 'education' => 'BBA from Kerala University', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/kavyanair', 'status' => 'Offer', 'source' => 'Recruitment Agency'],
            ['first_name' => 'Arjun', 'last_name' => 'Gupta', 'email' => 'arjun.gupta@email.com', 'phone' => '+91-9876543216', 'current_company' => null, 'current_position' => 'Fresh Graduate', 'experience_years' => 0, 'current_salary' => null, 'expected_salary' => 35000, 'notice_period' => 'Immediate', 'skills' => 'Java, Python, Web Development, Database Management', 'education' => 'B.Tech in Computer Science from NIT Warangal', 'portfolio_url' => 'https://arjungupta.github.io', 'linkedin_url' => 'https://linkedin.com/in/arjungupta', 'status' => 'Offer', 'source' => 'Campus Recruitment'],
            ['first_name' => 'Meera', 'last_name' => 'Joshi', 'email' => 'meera.joshi@email.com', 'phone' => '+91-9876543217', 'current_company' => 'Design Studio', 'current_position' => 'UI Designer', 'experience_years' => 2, 'current_salary' => 45000, 'expected_salary' => 60000, 'notice_period' => '1 month', 'skills' => 'UI/UX Design, Figma, Adobe Creative Suite, Prototyping', 'education' => 'Bachelor of Design from NIFT Delhi', 'portfolio_url' => 'https://meerajoshi.design', 'linkedin_url' => 'https://linkedin.com/in/meerajoshi', 'status' => 'Offer', 'source' => 'Walk-in Interview'],
            ['first_name' => 'Rohit', 'last_name' => 'Agarwal', 'email' => 'rohit.agarwal@email.com', 'phone' => '+91-9876543218', 'current_company' => 'Software Solutions Inc', 'current_position' => 'Backend Developer', 'experience_years' => 4, 'current_salary' => 85000, 'expected_salary' => 105000, 'notice_period' => '2 months', 'skills' => 'Java, Spring Boot, Microservices, AWS', 'education' => 'B.Tech in Computer Science from VIT', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/rohitagarwal', 'status' => 'Offer', 'source' => 'LinkedIn'],
            ['first_name' => 'Anita', 'last_name' => 'Desai', 'email' => 'anita.desai@email.com', 'phone' => '+91-9876543219', 'current_company' => 'Marketing Pro', 'current_position' => 'Content Manager', 'experience_years' => 3, 'current_salary' => 65000, 'expected_salary' => 80000, 'notice_period' => '1 month', 'skills' => 'Content Marketing, Copywriting, Social Media', 'education' => 'Masters in Mass Communication', 'portfolio_url' => null, 'linkedin_url' => 'https://linkedin.com/in/anitadesai', 'status' => 'Offer', 'source' => 'Naukri.com'],
            ['first_name' => 'Karan', 'last_name' => 'Mehta', 'email' => 'karan.mehta@email.com', 'phone' => '+91-9876543220', 'gender' => 'male', 'date_of_birth' => '1989-03-10', 'address' => '789 Finance Street', 'city' => 'Delhi', 'state' => 'Delhi', 'zip_code' => '110001', 'country' => 'India', 'current_company' => 'Finance Corp', 'current_position' => 'Senior Analyst', 'experience_years' => 5, 'current_salary' => 90000, 'expected_salary' => 110000, 'notice_period' => '3 months', 'skills' => 'Financial Planning, Risk Analysis, Investment', 'education' => 'MBA in Finance from IIM', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Company Website'],
            ['first_name' => 'Deepika', 'last_name' => 'Rao', 'email' => 'deepika.rao@email.com', 'phone' => '+91-9876543221', 'gender' => 'female', 'date_of_birth' => '1991-06-18', 'address' => '321 HR Avenue', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'zip_code' => '600001', 'country' => 'India', 'current_company' => 'HR Solutions', 'current_position' => 'Talent Manager', 'experience_years' => 4, 'current_salary' => 75000, 'expected_salary' => 95000, 'notice_period' => '2 months', 'skills' => 'Talent Acquisition, HR Analytics, Employee Engagement', 'education' => 'Masters in HR Management', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Screening', 'source' => 'Employee Referral'],
            ['first_name' => 'Sanjay', 'last_name' => 'Verma', 'email' => 'sanjay.verma@email.com', 'phone' => '+91-9876543222', 'gender' => 'male', 'date_of_birth' => '1987-11-25', 'address' => '654 Operations Road', 'city' => 'Pune', 'state' => 'Maharashtra', 'zip_code' => '411001', 'country' => 'India', 'current_company' => 'Operations Hub', 'current_position' => 'Process Manager', 'experience_years' => 6, 'current_salary' => 100000, 'expected_salary' => 125000, 'notice_period' => '3 months', 'skills' => 'Process Optimization, Six Sigma, Lean Management', 'education' => 'MBA in Operations Management', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'New', 'source' => 'Indeed'],
            ['first_name' => 'Pooja', 'last_name' => 'Iyer', 'email' => 'pooja.iyer@email.com', 'phone' => '+91-9876543223', 'gender' => 'female', 'date_of_birth' => '1990-09-14', 'address' => '987 Service Lane', 'city' => 'Hyderabad', 'state' => 'Telangana', 'zip_code' => '500001', 'country' => 'India', 'current_company' => 'Customer First', 'current_position' => 'Service Manager', 'experience_years' => 4, 'current_salary' => 70000, 'expected_salary' => 85000, 'notice_period' => '2 months', 'skills' => 'Customer Relations, Service Excellence, Team Management', 'education' => 'MBA in Service Management', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Interview', 'source' => 'Recruitment Agency'],
            ['first_name' => 'Rahul', 'last_name' => 'Jain', 'email' => 'rahul.jain@email.com', 'phone' => '+91-9876543224', 'gender' => 'male', 'date_of_birth' => '1998-12-05', 'address' => '234 Campus Road', 'city' => 'Pilani', 'state' => 'Rajasthan', 'zip_code' => '333031', 'country' => 'India', 'current_company' => null, 'current_position' => 'Recent Graduate', 'experience_years' => 0, 'current_salary' => null, 'expected_salary' => 40000, 'notice_period' => 'Immediate', 'skills' => 'Python, Machine Learning, Data Analysis', 'education' => 'B.Tech in Computer Science from BITS', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'New', 'source' => 'Campus Recruitment'],
            ['first_name' => 'Neha', 'last_name' => 'Kapoor', 'email' => 'neha.kapoor@email.com', 'phone' => '+91-9876543225', 'gender' => 'female', 'date_of_birth' => '1993-04-20', 'address' => '567 Design Street', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'zip_code' => '302001', 'country' => 'India', 'current_company' => 'Creative Agency', 'current_position' => 'Graphic Designer', 'experience_years' => 3, 'current_salary' => 55000, 'expected_salary' => 70000, 'notice_period' => '1 month', 'skills' => 'Graphic Design, Branding, Adobe Creative Suite', 'education' => 'Bachelor of Fine Arts', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Screening', 'source' => 'Walk-in Interview'],
            ['first_name' => 'Arun', 'last_name' => 'Krishnan', 'email' => 'arun.krishnan@email.com', 'phone' => '+91-9876543226', 'gender' => 'male', 'date_of_birth' => '1989-07-30', 'address' => '890 Tech Park', 'city' => 'Kochi', 'state' => 'Kerala', 'zip_code' => '682001', 'country' => 'India', 'current_company' => 'Tech Innovations', 'current_position' => 'DevOps Engineer', 'experience_years' => 5, 'current_salary' => 95000, 'expected_salary' => 115000, 'notice_period' => '2 months', 'skills' => 'Docker, Kubernetes, CI/CD, AWS, Jenkins', 'education' => 'B.Tech in Information Technology', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Screening', 'source' => 'LinkedIn'],
            ['first_name' => 'Swati', 'last_name' => 'Bansal', 'email' => 'swati.bansal@email.com', 'phone' => '+91-9876543227', 'gender' => 'female', 'date_of_birth' => '1990-02-12', 'address' => '123 Product Lane', 'city' => 'Gurgaon', 'state' => 'Haryana', 'zip_code' => '122001', 'country' => 'India', 'current_company' => 'Digital Solutions', 'current_position' => 'Product Manager', 'experience_years' => 4, 'current_salary' => 110000, 'expected_salary' => 135000, 'notice_period' => '3 months', 'skills' => 'Product Strategy, Agile, User Research, Analytics', 'education' => 'MBA in Product Management', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Naukri.com'],
            ['first_name' => 'Manish', 'last_name' => 'Gupta', 'email' => 'manish.gupta@email.com', 'phone' => '+91-9876543228', 'gender' => 'male', 'date_of_birth' => '1992-10-08', 'address' => '456 Data Street', 'city' => 'Noida', 'state' => 'Uttar Pradesh', 'zip_code' => '201301', 'country' => 'India', 'current_company' => 'Data Analytics Co', 'current_position' => 'Data Scientist', 'experience_years' => 3, 'current_salary' => 85000, 'expected_salary' => 105000, 'notice_period' => '2 months', 'skills' => 'Machine Learning, Python, R, SQL, Statistics', 'education' => 'Masters in Data Science', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Interview', 'source' => 'Company Website'],
            ['first_name' => 'Ravi', 'last_name' => 'Tiwari', 'email' => 'ravi.tiwari@email.com', 'phone' => '+91-9876543229', 'gender' => 'male', 'date_of_birth' => '1991-01-28', 'address' => '789 Consulting Plaza', 'city' => 'Kolkata', 'state' => 'West Bengal', 'zip_code' => '700001', 'country' => 'India', 'current_company' => 'Consulting Firm', 'current_position' => 'Business Analyst', 'experience_years' => 3, 'current_salary' => 75000, 'expected_salary' => 90000, 'notice_period' => '2 months', 'skills' => 'Business Analysis, Requirements Gathering, Process Mapping', 'education' => 'MBA from ISB Hyderabad', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'New', 'source' => 'Indeed'],
            ['first_name' => 'Divya', 'last_name' => 'Menon', 'email' => 'divya.menon@email.com', 'phone' => '+91-9876543230', 'gender' => 'female', 'date_of_birth' => '1992-05-16', 'address' => '321 QA Road', 'city' => 'Bangalore', 'state' => 'Karnataka', 'zip_code' => '560002', 'country' => 'India', 'current_company' => 'Tech Corp', 'current_position' => 'QA Engineer', 'experience_years' => 3, 'current_salary' => 60000, 'expected_salary' => 75000, 'notice_period' => '1 month', 'skills' => 'Manual Testing, Automation, Selenium', 'education' => 'B.Tech in Computer Science', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'New', 'source' => 'LinkedIn'],
            ['first_name' => 'Suresh', 'last_name' => 'Nair', 'email' => 'suresh.nair@email.com', 'phone' => '+91-9876543231', 'gender' => 'male', 'date_of_birth' => '1988-08-22', 'address' => '654 Sales Avenue', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'zip_code' => '400002', 'country' => 'India', 'current_company' => 'Sales Pro', 'current_position' => 'Sales Manager', 'experience_years' => 5, 'current_salary' => 80000, 'expected_salary' => 100000, 'notice_period' => '2 months', 'skills' => 'Sales, Negotiation, CRM', 'education' => 'MBA in Sales', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Company Website'],
            ['first_name' => 'Lakshmi', 'last_name' => 'Pillai', 'email' => 'lakshmi.pillai@email.com', 'phone' => '+91-9876543232', 'gender' => 'female', 'date_of_birth' => '1990-11-30', 'address' => '987 Finance Hub', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'zip_code' => '600002', 'country' => 'India', 'current_company' => 'Finance Hub', 'current_position' => 'Accountant', 'experience_years' => 4, 'current_salary' => 65000, 'expected_salary' => 80000, 'notice_period' => '1 month', 'skills' => 'Accounting, Tally, GST', 'education' => 'B.Com, CA Inter', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Naukri.com'],
            ['first_name' => 'Vishal', 'last_name' => 'Yadav', 'email' => 'vishal.yadav@email.com', 'phone' => '+91-9876543233', 'gender' => 'male', 'date_of_birth' => '1989-03-18', 'address' => '234 IT Park', 'city' => 'Pune', 'state' => 'Maharashtra', 'zip_code' => '411002', 'country' => 'India', 'current_company' => 'IT Services', 'current_position' => 'System Admin', 'experience_years' => 4, 'current_salary' => 70000, 'expected_salary' => 85000, 'notice_period' => '2 months', 'skills' => 'Linux, Windows Server, Networking', 'education' => 'B.Tech in IT', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Indeed'],
            ['first_name' => 'Anjali', 'last_name' => 'Saxena', 'email' => 'anjali.saxena@email.com', 'phone' => '+91-9876543234', 'gender' => 'female', 'date_of_birth' => '1994-07-25', 'address' => '567 Media Street', 'city' => 'Delhi', 'state' => 'Delhi', 'zip_code' => '110002', 'country' => 'India', 'current_company' => 'Media House', 'current_position' => 'Content Writer', 'experience_years' => 2, 'current_salary' => 40000, 'expected_salary' => 55000, 'notice_period' => '1 month', 'skills' => 'Content Writing, SEO, Blogging', 'education' => 'MA in English', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Interview', 'source' => 'Walk-in Interview'],
            ['first_name' => 'Harish', 'last_name' => 'Kumar', 'email' => 'harish.kumar@email.com', 'phone' => '+91-9876543235', 'gender' => 'male', 'date_of_birth' => '1987-12-10', 'address' => '890 Logistics Road', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'zip_code' => '380001', 'country' => 'India', 'current_company' => 'Logistics Co', 'current_position' => 'Operations Manager', 'experience_years' => 6, 'current_salary' => 95000, 'expected_salary' => 115000, 'notice_period' => '3 months', 'skills' => 'Supply Chain, Logistics, Operations', 'education' => 'MBA in Operations', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Interview', 'source' => 'Employee Referral'],
            ['first_name' => 'Nisha', 'last_name' => 'Agarwal', 'email' => 'nisha.agarwal@email.com', 'phone' => '+91-9876543236', 'gender' => 'female', 'date_of_birth' => '1992-04-14', 'address' => '123 Design Hub', 'city' => 'Jaipur', 'state' => 'Rajasthan', 'zip_code' => '302002', 'country' => 'India', 'current_company' => 'Design Co', 'current_position' => 'Web Designer', 'experience_years' => 3, 'current_salary' => 50000, 'expected_salary' => 65000, 'notice_period' => '1 month', 'skills' => 'HTML, CSS, JavaScript, Photoshop', 'education' => 'Bachelor in Design', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Interview', 'source' => 'LinkedIn'],
            ['first_name' => 'Ramesh', 'last_name' => 'Babu', 'email' => 'ramesh.babu@email.com', 'phone' => '+91-9876543237', 'gender' => 'male', 'date_of_birth' => '1988-09-05', 'address' => '456 Retail Street', 'city' => 'Coimbatore', 'state' => 'Tamil Nadu', 'zip_code' => '641001', 'country' => 'India', 'current_company' => 'Retail Chain', 'current_position' => 'Store Manager', 'experience_years' => 5, 'current_salary' => 60000, 'expected_salary' => 75000, 'notice_period' => '2 months', 'skills' => 'Retail Management, Inventory, Sales', 'education' => 'MBA in Retail', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Screening', 'source' => 'Company Website'],
            ['first_name' => 'Shweta', 'last_name' => 'Mishra', 'email' => 'shweta.mishra@email.com', 'phone' => '+91-9876543238', 'gender' => 'female', 'date_of_birth' => '1993-06-20', 'address' => '789 Pharma Lane', 'city' => 'Lucknow', 'state' => 'Uttar Pradesh', 'zip_code' => '226001', 'country' => 'India', 'current_company' => 'Pharma Ltd', 'current_position' => 'Medical Rep', 'experience_years' => 2, 'current_salary' => 45000, 'expected_salary' => 60000, 'notice_period' => '1 month', 'skills' => 'Sales, Medical Knowledge, Communication', 'education' => 'B.Pharm', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Screening', 'source' => 'Recruitment Agency'],
            ['first_name' => 'Tarun', 'last_name' => 'Malhotra', 'email' => 'tarun.malhotra@email.com', 'phone' => '+91-9876543239', 'gender' => 'male', 'date_of_birth' => '1990-10-15', 'address' => '321 Consulting Tower', 'city' => 'Bangalore', 'state' => 'Karnataka', 'zip_code' => '560003', 'country' => 'India', 'current_company' => 'Consulting Group', 'current_position' => 'Consultant', 'experience_years' => 4, 'current_salary' => 85000, 'expected_salary' => 105000, 'notice_period' => '2 months', 'skills' => 'Strategy, Analysis, Presentation', 'education' => 'MBA from IIM', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Naukri.com'],
            ['first_name' => 'Geeta', 'last_name' => 'Devi', 'email' => 'geeta.devi@email.com', 'phone' => '+91-9876543240', 'gender' => 'female', 'date_of_birth' => '1991-02-28', 'address' => '654 Education Road', 'city' => 'Patna', 'state' => 'Bihar', 'zip_code' => '800001', 'country' => 'India', 'current_company' => 'Education Hub', 'current_position' => 'Teacher', 'experience_years' => 3, 'current_salary' => 35000, 'expected_salary' => 45000, 'notice_period' => '1 month', 'skills' => 'Teaching, Communication, Curriculum', 'education' => 'B.Ed, MA', 'portfolio_url' => null, 'linkedin_url' => null, 'status' => 'Offer', 'source' => 'Walk-in Interview']
        ];
        
        foreach ($companies as $company) {
            // Get job postings for this company
            $jobPostings = JobPosting::where('created_by', $company->id)->get();
            
            if ($jobPostings->isEmpty()) {
                $this->command->warn('No job postings found for company: ' . $company->name . '. Please run JobPostingSeeder first.');
                continue;
            }
            
            // Get candidate sources for this company
            $candidateSources = CandidateSource::where('created_by', $company->id)->get();
            
            if ($candidateSources->isEmpty()) {
                $this->command->warn('No candidate sources found for company: ' . $company->name . '. Please run CandidateSourceSeeder first.');
                continue;
            }
            
            // Get employees for referrals
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();
            
            // Get custom questions
            $customQuestions = CustomQuestion::where('created_by', $company->id)->pluck('id')->toArray();
            
            // Create 30 candidates
            foreach ($candidates as $index => $candidateData) {
                // Cycle through job postings
                $jobPosting = $jobPostings[$index % $jobPostings->count()];
                
                // Find matching source
                $source = $candidateSources->where('name', $candidateData['source'])->first();
                if (!$source) $source = $candidateSources->first();
                
                // Set referral employee for employee referral source
                $referralEmployee = null;
                if ($candidateData['source'] === 'Employee Referral' && $employees->isNotEmpty()) {
                    $referralEmployee = $employees->first();
                }
                
                // Check if candidate already exists for this company
                if (Candidate::where('email', $candidateData['email'])->where('created_by', $company->id)->exists()) {
                    continue;
                }
                
                $applicationDate = date('Y-m-d', strtotime('-' . ($index + 1) . ' days'));
                
                // Generate custom question answers
                $customQuestionAnswers = null;
                if (!empty($jobPosting->custom_question)) {
                    $customQuestionAnswers = [];
                    $jobCustomQuestions = CustomQuestion::whereIn('id', $jobPosting->custom_question)->get();
                    foreach ($jobCustomQuestions as $question) {
                        $customQuestionAnswers[$question->question] = 'Sample answer for: ' . $question->question;
                    }
                }
                
                try {
                    Candidate::create([
                        'job_id' => $jobPosting->id,
                        'source_id' => $source->id,
                        'branch_id' => $jobPosting->branch_id,
                        'department_id' => $jobPosting->department_id,
                        'first_name' => $candidateData['first_name'],
                        'last_name' => $candidateData['last_name'],
                        'email' => $candidateData['email'],
                        'phone' => $candidateData['phone'],
                        'gender' => $candidateData['gender'] ?? null,
                        'date_of_birth' => $candidateData['date_of_birth'] ?? null,
                        'address' => $candidateData['address'] ?? null,
                        'city' => $candidateData['city'] ?? null,
                        'state' => $candidateData['state'] ?? null,
                        'zip_code' => $candidateData['zip_code'] ?? null,
                        'country' => $candidateData['country'] ?? null,
                        'current_company' => $candidateData['current_company'],
                        'current_position' => $candidateData['current_position'],
                        'experience_years' => $candidateData['experience_years'],
                        'current_salary' => $candidateData['current_salary'],
                        'expected_salary' => $candidateData['expected_salary'],
                        'final_salary' => null,
                        'notice_period' => $candidateData['notice_period'],
                        'resume_path' => 'resumes/' . strtolower($candidateData['first_name']) . '_' . strtolower($candidateData['last_name']) . '_resume.pdf',
                        'cover_letter_path' => null,
                        'coverletter_message' => $candidateData['coverletter_message'] ?? null,
                        'skills' => $candidateData['skills'],
                        'education' => $candidateData['education'],
                        'portfolio_url' => null,
                        'linkedin_url' => null,
                        'referral_employee_id' => $referralEmployee?->id,
                        'rating' => $candidateData['rating'] ?? null,
                        'is_archive' => false,
                        'is_employee' => false,
                        'custom_question' => $customQuestionAnswers,
                        'terms_condition_check' => 'on',
                        'status' => $candidateData['status'],
                        'application_date' => $applicationDate,
                        'created_by' => $company->id,
                    ]);
                    
                } catch (\Exception $e) {
                    $this->command->error('Failed to create candidate: ' . $candidateData['first_name'] . ' ' . $candidateData['last_name'] . ' for company: ' . $company->name);
                    continue;
                }
            }
        }
        
        $this->command->info('Candidate seeder completed successfully!');
    }
}