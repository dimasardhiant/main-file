export const defaultLandingPageSections = {
  sections: [
    {
      key: 'header',
      transparent: false,
      background_color: '#ffffff',
      text_color: '#1f2937',
      button_style: 'gradient'
    },
    {
      key: 'hero',
      title: 'Simplify HR Management Effortlessly',
      subtitle: 'Manage employees, payroll, attendance, and more in one powerful platform.',
      announcement_text: '� New: Smart Leave & Attendance Tracking Launched!',
      primary_button_text: 'Start Free Trial',
      secondary_button_text: 'Login',
      image: '',
      background_color: '#f8fafc',
      text_color: '#1f2937',
      layout: 'image-right',
      height: 600,
      stats: [
        { value: '10K+', label: 'Active Users' },
        { value: '50+', label: 'Countries' },
        { value: '99%', label: 'Satisfaction' }
      ],
      card: {
        name: 'John Doe',
        title: 'Senior Developer',
        company: 'Tech Solutions Inc.',
        initials: 'JD'
      }
    },

    {
      key: 'features',
      title: 'Empowering Businesses with Smart HR Solutions',
      description: 'All-in-one platform to manage employees, payroll, attendance, and performance with ease.',
      background_color: '#ffffff',
      layout: 'grid',
      columns: 3,
      image: '',
      show_icons: true,
      features_list: [
        {
          title: 'Employee Management',
          description: 'Centralized profiles with personal, job, and document details.',
          icon: 'users'
        },
        {
          title: 'Payroll Automation',
          description: 'Generate accurate payslips with tax, allowances, and deductions.',
          icon: 'dollar-sign'
        },
        {
          title: 'Leave & Attendance',
          description: 'Smart tracking of leaves, shifts, and attendance logs.',
          icon: 'clock'
        },
        {
          icon: 'user-plus',
          title: 'Recruitment & Onboarding',
          description: 'Streamline hiring with applicant tracking and digital onboarding.'
        },

        {
          icon: 'award',
          title: 'Performance Management',
          description: 'Set goals, run evaluations, and track employee growth.'
        },

        {
          icon: 'bar-chart-2',
          title: 'Reports & Analytics',
          description: 'Get actionable insights on workforce productivity and HR metrics.'
        },
      ]
    },

    {
      key: 'screenshots',
      title: 'See HRM Saas in Action',
      subtitle: 'Discover how our modern HRM SaaS platform helps you manage employees, payroll, attendance, and performance — all in one place.',
      screenshots_list: [
        {
          src: '/screenshots/dashboard.png',
          alt: 'HRMGo Dashboard Overview',
          title: 'Dashboard Overview',
          description: 'Get a complete overview of employee data, payroll, and HR activities in one unified dashboard.'
        },
        {
          src: '/screenshots/employee-management.png',
          alt: 'Employee Management Module',
          title: 'Employee Management',
          description: 'Centralized employee profiles with personal details, documents, and job history.'
        },
        {
          src: '/screenshots/payroll-payslip.png',
          alt: 'Payroll Automation',
          title: 'Payroll & Payslips',
          description: 'Automated payroll processing with tax calculations, allowances, and downloadable payslips.'
        },
        {
          src: '/screenshots/leave.png',
          alt: 'Leave Management',
          title: 'Leave Management',
          description: 'Easily apply, approve, and track employee leave requests with proper workflows and policies.'
        },
        {
          src: '/screenshots/attendance.png',
          alt: 'Attendance Tracking',
          title: 'Attendance Tracking',
          description: 'Monitor employee check-ins, check-outs, and shifts with automated attendance logs.'
        },
        {
          src: '/screenshots/recruitment.png',
          alt: 'Recruitment & Onboarding',
          title: 'Recruitment & Onboarding',
          description: 'Streamline hiring with applicant tracking and digital onboarding.'
        },
      ]
    },

    {
      key: 'why_choose_us',
      title: 'Why Choose HRM SaaS ? ',
      subtitle: 'Smart, simple, and powerful HR solutions for every business.',
      reasons: [
        { title: 'All-in-One HR Solution', description: 'Manage employees, payroll, attendance, recruitment, and performance from a single platform.', icon: 'layers' },
        { title: 'Time-Saving Automation', description: 'Automate repetitive HR tasks to focus on strategic decision-making.', icon: 'clock' },
        {
          icon: 'bar-chart',
          title: 'Data-Driven Insights',
          description: 'Make informed decisions with advanced analytics and reports.'
        },
        {
          icon: 'shield',
          title: 'Secure & Reliable',
          description: 'Keep sensitive HR data safe with enterprise-grade security.'
        }
      ],
      stats: [
        { value: '500+', label: 'Companies Using HRM', color: 'blue' },
        { value: '20K+', label: 'Employees Managed', color: 'green' },
        { value: '98%', label: 'Customer Satisfaction', color: 'orange' },

      ]
    },
    // {
    //   key: 'templates',
    //   title: 'Explore Our Templates',
    //   subtitle: 'Choose from our professionally designed templates to create your perfect digital business card.',
    //   background_color: '#f8fafc',
    //   layout: 'grid',
    //   columns: 3,
    //   templates_list: [
    //     { name: 'freelancer', category: 'professional' },
    //     { name: 'doctor', category: 'medical' },
    //     { name: 'restaurant', category: 'food' },
    //     { name: 'realestate', category: 'business' },
    //     { name: 'fitness', category: 'health' },
    //     { name: 'photography', category: 'creative' },
    //     { name: 'lawfirm', category: 'professional' },
    //     { name: 'cafe', category: 'food' },
    //     { name: 'salon', category: 'beauty' },
    //     { name: 'construction', category: 'business' },
    //     { name: 'eventplanner', category: 'services' },
    //     { name: 'tech-startup', category: 'technology' }
    //   ],
    //   cta_text: 'View All Templates',
    //   cta_link: '#'
    // },
    {
      key: 'about',
      title: 'About HRM SaaS',
      description: 'We are passionate about simplifying HR management for businesses of all sizes.',
      story_title: 'We are passionate about simplifying HR management for businesses of all sizes.',
      story_content: 'Founded by HR and tech enthusiasts, HRMGo was created to replace cumbersome spreadsheets and manual processes with a modern, all-in-one HR platform.',
      image: '',
      background_color: '#f9fafb',
      layout: 'image-right',
      stats: [
        { value: '3+ Years', label: 'Experience', color: 'blue' },
        { value: '500+', label: 'Companies Served', color: 'green' },
        { value: '20K+', label: 'Employees Managed', color: 'purple' },
      ]
    },
    {
      key: 'team',
      title: 'Meet Our Team',
      subtitle: 'We\'re a dedicated team of HR and technology experts.',
      cta_title: 'Want to Join Our Team?',
      cta_description: 'We\'re always looking for talented individuals to shape the future of HR management.',
      cta_button_text: 'View Open Positions',
      members: [
        {
          name: 'John Doe',
          role: 'CEO & Founder',
          bio: 'Experienced HR tech entrepreneur passionate about building intuitive HR solutions.',
          image: '',
          linkedin: '#',
          email: 'john@example.com'
        },
        {
          name: 'Jane Smith',
          role: 'CTO',
          bio: 'Leads the tech team to create scalable and secure HR platforms.',
          image: '',
          linkedin: '#',
          email: 'jane@example.com'
        },
        {
          name: 'Michael Lee',
          role: 'Head of Product',
          bio: 'Designs user-centric features to simplify HR processes.',
          image: '',
          linkedin: '#',
          email: 'michael@example.com'
        },
        {
          name: 'Emily Davis',
          role: 'HR Manager',
          bio: 'Oversees employee engagement, recruitment, and HR operations.',
          image: '',
          linkedin: '#',
          email: 'emily@example.com'
        },


      ]
    },
    {
      key: 'testimonials',
      title: 'What Our Clients Say',
      subtitle: 'Hear from HR leaders who trust our platform.',
      trust_title: 'Trusted by HR Professionals Worldwide',
      trust_stats: [
        { value: '4.9/5', label: 'Average Rating', color: 'blue' },
        { value: '500+', label: 'Companies Served', color: 'green' }
      ],
      testimonials: [
        {
          name: 'Alice Johnson',
          role: 'HR Manager',
          company: 'GlobalTech Ltd.',
          content: 'HRMGo has made managing employee records and attendance effortless. Our HR team saves hours every week!',
          rating: 5
        },
        {
          name: 'Robert Smith',
          role: 'Operations Head',
          company: 'Innovate Solutions',
          content: 'The payroll automation is incredibly accurate and easy to use. No more manual calculations or errors!',
          rating: 5
        },
        {
          name: 'Maria Davis',
          role: 'CEO',
          company: 'BrightFuture Corp.',
          content: 'From recruitment to performance management, HRMGo covers everything we need in one platform.',
          rating: 5
        },
        {
          name: 'David Lee',
          role: 'Talent Acquisition Lead',
          company: 'NextGen Enterprises',
          content: 'Recruitment and onboarding have never been smoother. HRMGo’s platform is intuitive and efficient.',
          rating: 5
        },
        {
          name: 'Samantha Green',
          role: 'Payroll Specialist',
          company: 'BrightSolutions Inc.',
          content: 'Payroll processing is now quick and error-free thanks to HRMGo. It has transformed our monthly workflow.',
          rating: 5
        },
        {
          name: 'Michael Brown',
          role: 'HR Coordinator',
          company: 'TechWave Ltd.',
          content: 'The performance management module helps us track employee goals and progress effortlessly.',
          rating: 5
        },

      ]
    },
    {
      key: 'plans',
      title: 'Choose Your HRM SaaS Plan',
      subtitle: 'Start with our free plan and upgrade as your team grows.',
      faq_text: 'Have questions about our plans? Reach out to our sales team for guidance.'
    },
    {
      key: 'faq',
      title: 'Frequently Asked Questions',
      subtitle: 'Got questions? We\'ve got answers.',
      cta_text: 'Still have questions?',
      button_text: 'Contact Support',
      faqs: [
        {
          question: 'How does HRM work?',
          answer: 'HRM SaaS is an all-in-one HR platform that helps you manage employees, payroll, attendance, recruitment, and performance efficiently.'
        },
        {
          question: 'Can I automate payroll and leave tracking?',
          answer: 'Yes! HRM SaaS allows you to automate payroll calculations, generate payslips, and track employee leaves and attendance seamlessly.'
        },
        {
          question: 'Is my employee data secure?',
          answer: 'Absolutely. HRM SaaS uses enterprise-grade security measures to keep all sensitive HR data safe and confidential.'
        },
        {
          question: 'Can I manage recruitment and onboarding?',
          answer: 'Yes, HRM SaaS provides applicant tracking, interview management, and digital onboarding tools to simplify hiring.'
        },
        {
          question: 'Does HRM SaaS support performance evaluations?',
          answer: 'Yes, you can set goals, track KPIs, and run performance reviews directly within the platform.'
        },
        {
          question: 'Can HRM SaaS generate HR reports?',
          answer: 'HRM offers advanced analytics and reporting features to give insights on attendance, payroll, and workforce performance.'
        },
        {
          question: 'What plans are available and can I upgrade anytime?',
          answer: 'We offer flexible plans for different team sizes. You can start with the free plan and upgrade as your organization grows.'
        },

      ]
    },
    {
      key: 'newsletter',
      title: 'Stay Updated with HRM SaaS',
      subtitle: 'Get the latest updates, HR tips, and feature announcements.',
      privacy_text: 'No spam, unsubscribe at any time.',
      benefits: [
        {
          icon: '📧',
          title: 'Weekly Updates',
          description: 'Stay informed about the latest HRM SaaS features and improvements.'
        },
        {
          icon: '💡',
          title: 'HR Insights',
          description: 'Get tips and best practices to optimize your HR operations.'
        },
        {
          icon: '📊',
          title: 'Reports & Trends',
          description: 'Receive analytics insights and industry trends directly to your inbox.'
        },
      ]
    },
    {
      key: 'contact',
      title: 'Get in Touch',
      subtitle: 'Have questions about HRM SaaS? We\'d love to hear from you..',
      form_title: 'Send us a Message',
      info_title: 'Contact Information',
      info_description: 'We\'re here to help and answer any questions you might have about managing your HR processes efficiently.',
      layout: 'split',
      background_color: '#f9fafb'
    },
    {
      key: 'footer',
      description: 'Simplifying HR management with an all-in-one modern platform.',
      newsletter_title: 'Stay Updated',
      newsletter_subtitle: 'Join our newsletter for HR tips and product updates',
      links: {
        product: [{ name: 'Features', href: '#features' }, { name: 'Pricing', href: '#pricing' }],
        company: [{ name: 'About Us', href: '#about' }, { name: 'Contact', href: '#contact' }]
      },
      social_links: [
        {
          name: 'Facebook',
          icon: 'Facebook',
          href: '#'
        },
        {
          name: 'Twitter',
          icon: 'Twitter',
          href: '#'
        },
        {
          name: 'LinkedIn',
          icon: 'LinkedIn',
          href: '#'
        },
      ],
      section_titles: {
        product: 'Product',
        company: 'Company'
      }
    }
  ],
  theme: {
    primary_color: '#10b77f',
    secondary_color: '#ffffff',
    accent_color: '#f7f7f7',
    logo_light: '',
    logo_dark: '',
    favicon: ''
  },
  seo: {
    meta_title: 'HRM - All-in-One HR Management Software',
    meta_description: 'Simplify employee management, payroll, attendance, recruitment, and performance with HRM, a modern HR SaaS platform.',
    meta_keywords: 'HR software, HRM, employee management, payroll, attendance tracking, recruitment, performance management'
  },
  custom_css: '',
  custom_js: '',
  section_order: ['header', 'hero', 'features', 'screenshots', 'why_choose_us', 'about', 'team', 'testimonials', 'plans', 'faq', 'newsletter', 'contact', 'footer'],
  section_visibility: {
    header: true,
    hero: true,
    features: true,
    screenshots: true,
    why_choose_us: true,
    // templates: true,
    about: true,
    team: true,
    testimonials: true,
    plans: true,
    faq: true,
    newsletter: true,
    contact: true,
    footer: true
  }
};