<?php

namespace Database\Seeders;

use App\Models\LandingPageCustomPage;
use Illuminate\Database\Seeder;

class LandingPageCustomPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = isSaas() ? $this->getSaasPages() : $this->getNonSaasPages();

        foreach ($pages as $pageData) {
            LandingPageCustomPage::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        $this->command->info('Landing page custom pages seeded successfully!');
    }

    private function getSaasPages()
    {
        return [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => "About Our HRM SaaS Solution: Transforming workforce management with <b>smart HR software</b>.<br>We are dedicated to helping businesses streamline HR operations and empower employees.<br>Our HRM SaaS platform simplifies human resource management for companies of all sizes, from recruitment to payroll, performance tracking to compliance, ensuring efficiency, accuracy, and employee satisfaction.<br>Built with a vision to make HR effortless, we automate repetitive tasks, centralize data, and provide actionable insights. Effective HR management is the backbone of organizational success.<br><b>Stats:</b> &bull; 5+ Years Industry Experience &bull; 15K+ Active Users &bull; 60+ Countries Served<br><b>Our Mission:</b> Redefine HR management by providing simple, scalable, and intelligent solutions that save time and boost productivity.<br><b>Our Values:</b> Innovation, integrity, and inclusivity ensure businesses and employees thrive together.<br><b>Our Commitment:</b> Deliver secure, scalable, and user-friendly HR solutions with outstanding customer support.<br><b>Our Vision:</b> A future where businesses focus on growth while HR runs seamlessly in the background with automation and intelligence.",
                'meta_title' => 'About Us - HRM SaaS Platform',
                'meta_description' => 'Discover our HRM SaaS solution – a smart platform that simplifies payroll, attendance, recruitment, and performance management for businesses worldwide.',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "Your privacy is important to us. This Privacy Policy explains how our HRM SaaS software collects, uses, and safeguards your data.<br><b>Information We Collect:</b> &bull; Employee information such as name, contact details, and work profile &bull; Payroll and compensation details &bull; Attendance, leave records, and shift schedules &bull; Performance reviews, training data, and appraisals &bull; System usage analytics and activity logs<br><b>How We Use Your Information:</b> &bull; Provide, maintain, and improve HRM SaaS services &bull; Automate payroll processing, tax calculations, and compliance reports &bull; Track attendance, leaves, and performance efficiently &bull; Communicate important updates, policies, and notifications &bull; Ensure data accuracy, security, and compliance with labor laws<br><b>Information Sharing:</b> We do not sell or trade personal employee data. Information may be shared with: &bull; Authorized company administrators and HR managers &bull; Third-party service providers for operational purposes &bull; Legal authorities if required by law<br><b>Data Security:</b> We use encryption, access control, and audits to protect HR data from unauthorized access, misuse, or disclosure.<br><b>Data Retention:</b> Data is retained as long as required to fulfill policy purposes or as mandated by law; can be deleted or anonymized when no longer needed.<br><b>Your Rights:</b> You may access, update, or request deletion of personal data. Contact your employer or our support team for platform concerns.",
                'meta_title' => 'Privacy Policy - HRM SaaS',
                'meta_description' => 'Read the privacy policy of our HRM SaaS platform to understand how employee and HR data is collected, used, and protected.',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => "Please read these terms carefully before using our HRM SaaS platform. By accessing or using our services, you agree to these terms.<br><br><b>Acceptance of Terms:</b> By creating an account or using our HRM SaaS product, you confirm that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree, you may not use the platform.<br><br><b>Service Description:</b> Our platform provides businesses with Human Resource Management solutions, including but not limited to:<br>&bull; Employee records and profile management<br>&bull; Attendance and leave tracking<br>&bull; Payroll and compensation management<br>&bull; Performance evaluation tools<br>&bull; Reports, analytics, and integrations<br><br><b>User Responsibilities:</b> As a user of our HRM SaaS, you agree to:<br>&bull; Provide accurate and updated information when creating an account<br>&bull; Maintain confidentiality of your login credentials<br>&bull; Ensure that all uploaded content complies with applicable laws<br>&bull; Use the platform only for lawful HR management purposes<br><br><b>Subscription & Payments:</b> You agree to pay all fees associated with your chosen plan in accordance with the billing terms. Failure to pay may result in suspension or termination of your account.<br><br><b>Termination of Service:</b> We reserve the right to suspend or terminate your access if you violate these Terms or engage in harmful activities.<br><br><b>Data & Privacy:</b> Your data will be handled per our Privacy Policy. You are responsible for safeguarding your account access.<br><br><b>Limitation of Liability:</b> Our company shall not be held liable for any indirect, incidental, or consequential damages arising from your use of the HRM SaaS platform.",
                'meta_title' => 'Terms of Service - HRM SaaS',
                'meta_description' => 'Read our terms of service to understand the rules and responsibilities for using our HRM SaaS platform.',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => "Have questions about <b>HRM SaaS </b>? Our team is here to guide you every step of the way.<br><br><b>Send us a Message:</b> Fill out the form with your Full Name, Email Address, Subject, and Message. Our team will respond promptly.<br><br><b>Contact Information:</b><br>&bull; <b>Email Us:</b> support@hrm.com (Our team typically responds within 24 hours)<br>&bull; <b>Call Us:</b> +1 (555) 123-4567 (Available Monday – Friday, 9am – 6pm EST)<br>&bull; <b>Visit Us:</b> 123 Business Ave, Suite 100, San Francisco, CA 94105<br><br><b>Business Hours:</b><br>&bull; Monday - Friday: 9:00 AM - 6:00 PM EST<br>&bull; Saturday: 10:00 AM - 4:00 PM EST<br>&bull; Sunday: Closed",
                'meta_title' => 'Contact Us - HRM Support',
                'meta_description' => 'Get in touch with the HRM team for product support, questions, or partnership opportunities.',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => "Get quick answers to the most <b>common queries</b> about using our HRM SaaS platform.<br><br><b>Getting Started:</b><br><b>What is HRM SaaS?</b> Our HRM SaaS is an all-in-one platform to manage your employees, payroll, attendance, performance, and leave. It simplifies HR operations and helps businesses run efficiently.<br><b>How do I get started?</b> Follow these steps to set up your account:<br>&bull; Sign up for an HRM SaaS account<br>&bull; Set up your company profile and departments<br>&bull; Add employees and assign roles<br>&bull; Configure payroll, attendance, and leave policies<br>&bull; Start managing HR processes efficiently<br><br><b>Features & Plans:</b><br><b>Which plans are available?</b> We offer multiple subscription plans including Basic, Professional, and Enterprise, each with features tailored for businesses of all sizes.<br><b>Can I customize HR workflows?</b> Yes, you can customize workflows for attendance, leave approvals, performance reviews, and payroll according to your company policies.<br><br><b>Analytics & Support:</b><br><b>How can I monitor HR metrics?</b> Our analytics dashboard provides real-time insights on employee attendance, payroll reports, performance trends, and leave balances, helping you make informed HR decisions.",
                'meta_title' => 'FAQ - HRM Help Center',
                'meta_description' => 'Find answers to frequently asked questions about HRM SaaS, including features, plans, employee management, and analytics.',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'title' => 'Refund Policy',
                'slug' => 'refund-policy',
                'content' => "We are committed to your satisfaction. Below is our refund policy, including eligibility, process, and exceptions.<br><br><b>30-Day Money Back Guarantee:</b> We offer a 30-day money-back guarantee for all premium plans. If you are not completely satisfied with HRM SaaS, we will refund your payment in full within 30 days of purchase.<br><br><b>Eligible Refunds:</b><br>- Monthly and annual subscription plans<br>- One-time premium features or add-ons<br>- Unused portions of prepaid services<br><br><b>Refund Process:</b><br>1. Contact our support team within 30 days of purchase<br>2. Provide your account details and the reason for the refund<br>3. We will review and process your request within 3–5 business days<br>4. Refunds will be issued to your original payment method<br><br><b>Non-Refundable Items:</b><br>- Custom development work or integrations<br>- Third-party services and add-ons<br>- Domain registration fees<br>- Services used after the 30-day guarantee period<br><br>If you have any questions regarding our refund policy, please contact our support team. We are happy to assist!",
                'meta_title' => 'Refund Policy - HRM',
                'meta_description' => 'Learn about our refund policy and money-back guarantee for HRM services.',
                'is_active' => true,
                'sort_order' => 6
            ]
        ];
    }

    private function getNonSaasPages()
    {
        return [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => "About Our HRM Solution: Transforming workforce management with <b>smart HR software</b>.<br>We are dedicated to helping businesses streamline HR operations and empower employees.<br>Our HRM platform simplifies human resource management for companies of all sizes, from recruitment to payroll, performance tracking to compliance, ensuring efficiency, accuracy, and employee satisfaction.<br>Built with a vision to make HR effortless, we automate repetitive tasks, centralize data, and provide actionable insights. Effective HR management is the backbone of organizational success.<br><b>Stats:</b> &bull; 5+ Years Industry Experience &bull; 15K+ Active Users &bull; 60+ Countries Served<br><b>Our Mission:</b> Redefine HR management by providing simple, scalable, and intelligent solutions that save time and boost productivity.<br><b>Our Values:</b> Innovation, integrity, and inclusivity ensure businesses and employees thrive together.<br><b>Our Commitment:</b> Deliver secure, scalable, and user-friendly HR solutions with outstanding customer support.<br><b>Our Vision:</b> A future where businesses focus on growth while HR runs seamlessly in the background with automation and intelligence.",
                'meta_title' => 'About Us - HRM Platform',
                'meta_description' => 'Discover our HRM solution – a smart platform that simplifies payroll, attendance, recruitment, and performance management for businesses worldwide.',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "Your privacy is important to us. This Privacy Policy explains how our HRM software collects, uses, and safeguards your data.<br><b>Information We Collect:</b> &bull; Employee information such as name, contact details, and work profile &bull; Payroll and compensation details &bull; Attendance, leave records, and shift schedules &bull; Performance reviews, training data, and appraisals &bull; System usage analytics and activity logs<br><b>How We Use Your Information:</b> &bull; Provide, maintain, and improve HRM services &bull; Automate payroll processing, tax calculations, and compliance reports &bull; Track attendance, leaves, and performance efficiently &bull; Communicate important updates, policies, and notifications &bull; Ensure data accuracy, security, and compliance with labor laws<br><b>Information Sharing:</b> We do not sell or trade personal employee data. Information may be shared with: &bull; Authorized company administrators and HR managers &bull; Third-party service providers for operational purposes &bull; Legal authorities if required by law<br><b>Data Security:</b> We use encryption, access control, and audits to protect HR data from unauthorized access, misuse, or disclosure.<br><b>Data Retention:</b> Data is retained as long as required to fulfill policy purposes or as mandated by law; can be deleted or anonymized when no longer needed.<br><b>Your Rights:</b> You may access, update, or request deletion of personal data. Contact your employer or our support team for platform concerns.",
                'meta_title' => 'Privacy Policy - HRM',
                'meta_description' => 'Read the privacy policy of our HRM platform to understand how employee and HR data is collected, used, and protected.',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => "Please read these terms carefully before using our HRM platform. By accessing or using our services, you agree to these terms.<br><br><b>Acceptance of Terms:</b> By creating an account or using our HRM product, you confirm that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree, you may not use the platform.<br><br><b>Service Description:</b> Our platform provides businesses with Human Resource Management solutions, including but not limited to:<br>&bull; Employee records and profile management<br>&bull; Attendance and leave tracking<br>&bull; Payroll and compensation management<br>&bull; Performance evaluation tools<br>&bull; Reports, analytics, and integrations<br><br><b>User Responsibilities:</b> As a user of our HRM, you agree to:<br>&bull; Provide accurate and updated information when creating an account<br>&bull; Maintain confidentiality of your login credentials<br>&bull; Ensure that all uploaded content complies with applicable laws<br>&bull; Use the platform only for lawful HR management purposes<br><br><b>Service & Payments:</b> You agree to pay all fees associated with your service in accordance with the billing terms. Failure to pay may result in suspension or termination of your account.<br><br><b>Termination of Service:</b> We reserve the right to suspend or terminate your access if you violate these Terms or engage in harmful activities.<br><br><b>Data & Privacy:</b> Your data will be handled per our Privacy Policy. You are responsible for safeguarding your account access.<br><br><b>Limitation of Liability:</b> Our company shall not be held liable for any indirect, incidental, or consequential damages arising from your use of the HRM platform.",
                'meta_title' => 'Terms of Service - HRM',
                'meta_description' => 'Read our terms of service to understand the rules and responsibilities for using our HRM platform.',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => "Have questions about <b>HRM</b>? Our team is here to guide you every step of the way.<br><br><b>Send us a Message:</b> Fill out the form with your Full Name, Email Address, Subject, and Message. Our team will respond promptly.<br><br><b>Contact Information:</b><br>&bull; <b>Email Us:</b> support@hrm.com (Our team typically responds within 24 hours)<br>&bull; <b>Call Us:</b> +1 (555) 123-4567 (Available Monday – Friday, 9am – 6pm EST)<br>&bull; <b>Visit Us:</b> 123 Business Ave, Suite 100, San Francisco, CA 94105<br><br><b>Business Hours:</b><br>&bull; Monday - Friday: 9:00 AM - 6:00 PM EST<br>&bull; Saturday: 10:00 AM - 4:00 PM EST<br>&bull; Sunday: Closed",
                'meta_title' => 'Contact Us - HRM Support',
                'meta_description' => 'Get in touch with the HRM team for product support, questions, or partnership opportunities.',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => "Get quick answers to the most <b>common queries</b> about using our HRM platform.<br><br><b>Getting Started:</b><br><b>What is HRM?</b> Our HRM is an all-in-one platform to manage your employees, payroll, attendance, performance, and leave. It simplifies HR operations and helps businesses run efficiently.<br><b>How do I get started?</b> Follow these steps to set up your account:<br>&bull; Contact us to set up your HRM account<br>&bull; Set up your company profile and departments<br>&bull; Add employees and assign roles<br>&bull; Configure payroll, attendance, and leave policies<br>&bull; Start managing HR processes efficiently<br><br><b>Features & Implementation:</b><br><b>What features are available?</b> We offer comprehensive HR management features including employee management, payroll processing, attendance tracking, performance reviews, and reporting.<br><b>Can I customize HR workflows?</b> Yes, you can customize workflows for attendance, leave approvals, performance reviews, and payroll according to your company policies.<br><br><b>Analytics & Support:</b><br><b>How can I monitor HR metrics?</b> Our analytics dashboard provides real-time insights on employee attendance, payroll reports, performance trends, and leave balances, helping you make informed HR decisions.",
                'meta_title' => 'FAQ - HRM Help Center',
                'meta_description' => 'Find answers to frequently asked questions about HRM, including features, implementation, employee management, and analytics.',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'title' => 'Refund Policy',
                'slug' => 'refund-policy',
                'content' => "We are committed to your satisfaction. Below is our refund policy, including eligibility, process, and exceptions.<br><br><b>30-Day Money Back Guarantee:</b> We offer a 30-day money-back guarantee for all services. If you are not completely satisfied with HRM, we will refund your payment in full within 30 days of purchase.<br><br><b>Eligible Refunds:</b><br>- Service fees and implementation costs<br>- One-time premium features or add-ons<br>- Unused portions of prepaid services<br><br><b>Refund Process:</b><br>1. Contact our support team within 30 days of purchase<br>2. Provide your account details and the reason for the refund<br>3. We will review and process your request within 3–5 business days<br>4. Refunds will be issued to your original payment method<br><br><b>Non-Refundable Items:</b><br>- Custom development work or integrations<br>- Third-party services and add-ons<br>- Domain registration fees<br>- Services used after the 30-day guarantee period<br><br>If you have any questions regarding our refund policy, please contact our support team. We are happy to assist!",
                'meta_title' => 'Refund Policy - HRM',
                'meta_description' => 'Learn about our refund policy and money-back guarantee for HRM services.',
                'is_active' => true,
                'sort_order' => 6
            ]
        ];
    }
}