import React, { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';

interface Faq {
  id: number;
  question: string;
  answer: string;
}

interface FaqSectionProps {
  brandColor?: string;
  faqs: Faq[];
  settings?: any;
  sectionData?: {
    title?: string;
    subtitle?: string;
    cta_text?: string;
    button_text?: string;
    default_faqs?: Array<{
      question: string;
      answer: string;
    }>;
  };
}

export default function FaqSection({ faqs, settings, sectionData, brandColor = '#3b82f6' }: FaqSectionProps) {
  const [openFaq, setOpenFaq] = useState<number | null>(null);

  // Default FAQs if none provided
  const defaultFaqs = [
    {
      id: 1,
      question: 'How does HRM work?',
      answer: 'HRM is an all-in-one HR platform that helps you manage employees, payroll, attendance, recruitment, and performance efficiently.'
    },
    {
      id: 2,
      question: 'Can I automate payroll and leave tracking?',
      answer: 'Yes! HRM allows you to automate payroll calculations, generate payslips, and track employee leaves and attendance seamlessly.'
    },
    {
      id: 3,
      question: 'Is my employee data secure?',
      answer: 'Absolutely. HRM uses enterprise-grade security measures to keep all sensitive HR data safe and confidential.'
    },
    {
      id: 4,
      question: 'Can I manage recruitment and onboarding?',
      answer: 'Yes, HRM provides applicant tracking, interview management, and digital onboarding tools to simplify hiring.'
    },
    {
      id: 5,
      question: 'Does HRM support performance evaluations?',
      answer: 'Yes, you can set goals, track KPIs, and run performance reviews directly within the platform.'
    },
    {
      id: 6,
      question: 'Can HRM generate HR reports?',
      answer: 'HRM offers advanced analytics and reporting features to give insights on attendance, payroll, and workforce performance.'
    },
    {
      id: 7,
      question: 'What plans are available and can I upgrade anytime?',
      answer: 'We offer flexible plans for different team sizes. You can start with the free plan and upgrade as your organization grows.'
    },

  ];

  const backendFaqs = sectionData?.faqs?.map((faq, index) => ({
    id: index + 1,
    ...faq
  })) || defaultFaqs;

  const displayFaqs = faqs.length > 0 ? faqs : backendFaqs;

  const toggleFaq = (id: number) => {
    setOpenFaq(openFaq === id ? null : id);
  };

  return (
    <section className="py-12 sm:py-16 lg:py-20 bg-white">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8 sm:mb-12 lg:mb-16">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {sectionData?.title || 'Frequently Asked Questions'}
          </h2>
          <p className="text-lg text-gray-600 leading-relaxed font-medium">
            {sectionData?.subtitle || 'Got questions? We\'ve got answers. If you can\'t find what you\'re looking for, feel free to contact our support team.'}
          </p>
        </div>

        <div className="space-y-2 sm:space-y-3">
          {displayFaqs.map((faq) => (
            <div
              key={faq.id}
              className="bg-gray-50 border border-gray-200 rounded-lg"
            >
              <button
                onClick={() => toggleFaq(faq.id)}
                className="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-100 transition-colors"
                aria-expanded={openFaq === faq.id}
                aria-controls={`faq-answer-${faq.id}`}
                aria-describedby={`faq-question-${faq.id}`}
              >
                <h3 className="text-lg font-semibold text-gray-900 pr-4" id={`faq-question-${faq.id}`}>
                  {faq.question}
                </h3>
                {openFaq === faq.id ? (
                  <ChevronUp className="w-5 h-5 text-gray-600 flex-shrink-0" aria-hidden="true" />
                ) : (
                  <ChevronDown className="w-5 h-5 text-gray-600 flex-shrink-0" aria-hidden="true" />
                )}
              </button>

              {openFaq === faq.id && (
                <div className="px-6 pb-4 border-t border-gray-200" id={`faq-answer-${faq.id}`} role="region" aria-labelledby={`faq-question-${faq.id}`}>
                  <p className="text-gray-600 leading-relaxed pt-4">
                    {faq.answer}
                  </p>
                </div>
              )}
            </div>
          ))}
        </div>

        {(sectionData?.cta_text || sectionData?.button_text) && (
          <div className="text-center mt-8 sm:mt-12">
            <p className="text-gray-600 mb-4">
              {sectionData?.cta_text || 'Still have questions?'}
            </p>
            <a
              href="#contact"
              className="inline-flex items-center gap-2 text-white px-6 py-3 rounded-lg transition-colors font-semibold"
              style={{ backgroundColor: brandColor }}
            >
              {sectionData?.button_text || 'Contact Support'}
            </a>
          </div>
        )}
      </div>
    </section>
  );
}