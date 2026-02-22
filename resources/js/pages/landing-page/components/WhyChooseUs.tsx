import React from 'react';
import { CheckCircle, Clock, Users, Zap, Star, Shield, Heart, Award, Layers, BarChart } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useScrollAnimation } from '../../../hooks/useScrollAnimation';

interface WhyChooseUsProps {
  brandColor?: string;
  settings: any;
  sectionData: {
    title?: string;
    subtitle?: string;
    reasons?: Array<{
      title: string;
      description: string;
      icon: string;
    }>;
    stats?: Array<{
      value: string;
      label: string;
      color: string;
    }>;
    stats_title?: string;
    stats_subtitle?: string;
    cta_title?: string;
    cta_subtitle?: string;
  };
}

// Icon mapping for dynamic icons
const iconMap: Record<string, React.ComponentType<any>> = {
  'clock': Clock,
  'users': Users,
  'zap': Zap,
  'check-circle': CheckCircle,
  'star': Star,
  'shield': Shield,
  'heart': Heart,
  'award': Award,
  'layers': Layers,
  'bar-chart': BarChart,
};

export default function WhyChooseUs({ settings, sectionData, brandColor = '#3b82f6' }: WhyChooseUsProps) {
  const { t } = useTranslation();
  const { ref, isVisible } = useScrollAnimation();
  // Default data if none provided
  const defaultReasons = [
    {
      icon: 'layers',
      title: t('All-in-One HR Solution'),
      description: t('Manage employees, payroll, attendance, recruitment, and performance from a single platform. No technical skills required.')
    },
    {
      icon: 'clock',
      title: t('Time-Saving Automation'),
      description: t('Automate repetitive HR tasks to focus on strategic decision-making.')
    },
    {
      icon: 'bar-chart',
      title: t('Data-Driven Insights'),
      description: t('Make informed decisions with advanced analytics and reports.')
    },
    {
      icon: 'shield',
      title: t('Secure & Reliable'),
      description: t('Keep sensitive HR data safe with enterprise-grade security.')
    }
  ];

  const defaultStats = [
    { value: '500+', label: t('Companies Using HRM'), color: 'blue' },
    { value: '20K+', label: t('Employees Managed'), color: 'green' },
    { value: '98%', label: t('Customer Satisfaction'), color: 'orange' },

  ];

  const reasons = sectionData.reasons && sectionData.reasons.length > 0
    ? sectionData.reasons
    : defaultReasons;

  const stats = sectionData.stats && sectionData.stats.length > 0
    ? sectionData.stats
    : defaultStats;

  return (
    <section className="py-12 sm:py-16 lg:py-20 bg-white" ref={ref}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center">
          {/* Left Content */}
          <div className={`transition-all duration-700 ${isVisible ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-8'}`}>
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
              {sectionData.title || t('Why Choose HRM SaaS ?')}
            </h2>
            <p className="text-lg text-gray-600 mb-8 leading-relaxed font-medium">
              {sectionData.subtitle || t('Smart, simple, and powerful HR solutions for every business. We\'re your partner in building meaningful professional connections that drive business growth.')}
            </p>

            <div className="space-y-4 sm:space-y-6">
              {reasons.map((reason, index) => {
                const IconComponent = iconMap[reason.icon] || Clock;
                return (
                  <div key={index} className="flex items-start gap-4">
                    <div className="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style={{ backgroundColor: `${brandColor}15` }}>
                      <IconComponent className="w-5 h-5" style={{ color: brandColor }} />
                    </div>
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        {reason.title}
                      </h3>
                      <p className="text-gray-600">
                        {reason.description}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Right Content - Stats/Visual */}
          <div className={`bg-gray-50 rounded-xl p-8 border border-gray-200 transition-all duration-700 delay-300 ${isVisible ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'}`}>
            <div className="text-center mb-8">
              <h3 className="text-2xl font-bold text-gray-900 mb-2">
                {sectionData.stats_title || t('Trusted by Industry Leaders')}
              </h3>
              <p className="text-gray-600">
                {sectionData.stats_subtitle || t('Join the growing community of professionals')}
              </p>
            </div>

            <div className="grid grid-cols-2 gap-4 sm:gap-6">
              {stats.map((stat, index) => (
                <div key={index} className="text-center p-6 bg-white rounded-lg border border-gray-200">
                  <div className="text-3xl font-bold text-gray-900 mb-2">{stat.value}</div>
                  <div className="text-gray-600 font-medium">{stat.label}</div>
                </div>
              ))}
            </div>

            {(sectionData.cta_title || sectionData.cta_subtitle) && (
              <div className="mt-8 p-6 rounded-lg text-white text-center" style={{ backgroundColor: brandColor }}>
                <div className="text-xl font-bold mb-2">{sectionData.cta_title || t('Ready to get started?')}</div>
                <div className="text-gray-300">{sectionData.cta_subtitle || t('Join thousands of satisfied users today')}</div>
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}