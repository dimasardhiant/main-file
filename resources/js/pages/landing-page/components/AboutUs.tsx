import React from 'react';
import { Target, Heart, Award, Lightbulb, Star, Shield, Users, Zap } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useScrollAnimation } from '../../../hooks/useScrollAnimation';

interface AboutUsProps {
  brandColor?: string;
  settings: any;
  sectionData: {
    title?: string;
    description?: string;
    story_title?: string;
    story_content?: string;
    stats?: Array<{
      value: string;
      label: string;
      color: string;
    }>;
    values?: Array<{
      title: string;
      description: string;
      icon: string;
    }>;
    image_title?: string;
    image_subtitle?: string;
    image_icon?: string;
  };
}

// Icon mapping for dynamic icons
const iconMap: Record<string, React.ComponentType<any>> = {
  'target': Target,
  'heart': Heart,
  'award': Award,
  'lightbulb': Lightbulb,
  'star': Star,
  'shield': Shield,
  'users': Users,
  'zap': Zap
};

export default function AboutUs({ settings, sectionData, brandColor = '#3b82f6' }: AboutUsProps) {
  const { t } = useTranslation();
  const { ref, isVisible } = useScrollAnimation();

  // Helper to get full URL for images
  const getImageUrl = (path: string) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    return `${window.appSettings.imageUrl}${path}`;
  };

  const sectionImage = getImageUrl(sectionData.image);
  const backgroundColor = sectionData.background_color || '#f9fafb';
  // Default data if none provided
  const defaultValues = [
    {
      icon: 'target',
      title: t('Our Mission'),
      description: t('To empower businesses with smart HR solutions that simplify employee management, payroll, attendance, and performance tracking.')
    },
    {
      icon: 'heart',
      title: t('Our Values'),
      description: t('We prioritize innovation, efficiency, and creating a workplace ecosystem that nurtures growth and collaboration.')
    },
    {
      icon: 'award',
      title: t('Our Commitment'),
      description: t('Providing reliable, intuitive HR tools backed by exceptional support to help organizations optimize their workforce.')
    },
    {
      icon: 'lightbulb',
      title: t('Our Vision'),
      description: t('A future where HR management is fully automated, transparent, and enables organizations to focus on people, not paperwork.')
    }

  ];

  const defaultStats = [
    { value: t('3+ Years'), label: t('Experience'), color: 'blue' },
    { value: '500+', label: t('Companies Served'), color: 'green' },
    { value: '20K+', label: t('Employees Managed'), color: 'purple' },

  ];

  const values = sectionData.values && sectionData.values.length > 0
    ? sectionData.values
    : defaultValues;

  const stats = sectionData.stats && sectionData.stats.length > 0
    ? sectionData.stats
    : defaultStats;

  return (
    <section id="about" className="py-12 sm:py-16 lg:py-20" style={{ backgroundColor }} ref={ref}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className={`text-center mb-8 sm:mb-12 lg:mb-16 transition-all duration-700 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {sectionData.title || t('About HRM SaaS')}
          </h2>
          <p className="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed font-medium">
            {sectionData.description || t('We are passionate about simplifying HR management for businesses of all sizes.')}
          </p>
        </div>

        <div className="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center mb-8 sm:mb-12 lg:mb-16">
          {/* Left Content */}
          <div>
            <h3 className="text-2xl font-bold text-gray-900 mb-6">
              {sectionData.story_title || t('We are passionate about simplifying HR management for businesses of all sizes.')}
            </h3>
            <div className="text-gray-600 mb-8 leading-relaxed" dangerouslySetInnerHTML={{
              __html: (sectionData.story_content || t('Founded by HR and tech enthusiasts, HRMGo was created to replace cumbersome spreadsheets and manual processes with a modern, all-in-one HR platform.')).replace(/\n/g, '</p><p className="mb-6">')
            }} />

            {stats.length > 0 && (
              <div className="flex items-center gap-8">
                {stats.map((stat, index) => (
                  <div key={index} className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{stat.value}</div>
                    <div className="text-sm text-gray-600">{stat.label}</div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Right Content - Image or Visual */}
          <div className="bg-white rounded-xl p-8 border border-gray-200 h-96 flex items-center justify-center">
            {sectionImage ? (
              <img src={sectionImage} alt="About Us" className="max-w-full max-h-full object-contain rounded-lg" />
            ) : (
              <div className="text-center">
                <div className="w-24 h-24 bg-gray-100 rounded-full mx-auto mb-6 flex items-center justify-center">
                  <span className="text-3xl">{sectionData.image_icon || '🚀'}</span>
                </div>
                <h4 className="text-xl font-semibold text-gray-900 mb-2">
                  {sectionData.image_title || t('Innovation Driven')}
                </h4>
                <p className="text-gray-600">
                  {sectionData.image_subtitle || t('Building the future of networking')}
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Values Grid */}
        <div className={`grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8 transition-all duration-700 delay-500 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          {values.map((value, index) => {
            const IconComponent = iconMap[value.icon] || Target;
            return (
              <div key={index} className="text-center bg-white p-6 rounded-xl border border-gray-200">
                <div className="w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-4" style={{ backgroundColor: `${brandColor}15` }}>
                  <IconComponent className="w-6 h-6" style={{ color: brandColor }} />
                </div>
                <h3 className="text-lg font-semibold text-gray-900 mb-3">
                  {value.title}
                </h3>
                <p className="text-gray-600 text-sm leading-relaxed">
                  {value.description}
                </p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}