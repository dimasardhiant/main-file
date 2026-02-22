import React from 'react';
import { QrCode, Smartphone, Share2, BarChart3, Globe, Shield, Star, Zap, Users, Lock, Wifi, Heart , DollarSign , Clock , UserPlus, Award, BarChart2} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { useScrollAnimation } from '../../../hooks/useScrollAnimation';

interface Feature {
  title: string;
  description: string;
  icon: string;
}

interface FeaturesSectionProps {
  brandColor?: string;
  settings: any;
  sectionData: {
    title?: string;
    description?: string;
    features_list?: Feature[];
  };
}

// Icon mapping for dynamic icons
const iconMap: Record<string, React.ComponentType<any>> = {
  'qr-code': QrCode,
  'smartphone': Smartphone,
  'share': Share2,
  'bar-chart': BarChart3,
  'globe': Globe,
  'shield': Shield,
  'star': Star,
  'zap': Zap,
  'users': Users,
  'lock': Lock,
  'wifi': Wifi,
  'heart': Heart,
  'dollar-sign' : DollarSign,
  'clock' : Clock,
  'user-plus' : UserPlus,
  'award' : Award,
  'bar-chart-2' : BarChart2,
};

export default function FeaturesSection({ settings, sectionData, brandColor = '#3b82f6' }: FeaturesSectionProps) {
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
  const columns = sectionData.columns || 3;
  // Default features if none provided
  const defaultFeatures = [
    {
      icon: 'users',
      title: t('Employee Management'),
      description: t('Centralized profiles with personal, job, and document details. Perfect for business cards, flyers, and networking events.')
    },
    {
      icon: 'dollar-sign',
      title: t('Payroll Automation'),
      description: t('Generate accurate payslips with tax, allowances, and deductions. Modern networking made simple and professional.')
    },
    {
      icon: 'share',
      title: t('Easy Sharing'),
      description: t('Share your digital card via email, SMS, social media, or direct links. Multiple sharing options available.')
    },
    {
      icon: 'clock',
      title: t('Leave & Attendance'),
      description: t('Smart tracking of leaves, shifts, and attendance logs. Understand how your network interacts with your card.')
    },
    {
      icon: 'user-plus',
      title: t('Recruitment & Onboarding'),
      description: t('Streamline hiring with applicant tracking and digital onboarding.')
    },

    {
      icon: 'award',
      title: t('Performance Management'),
      description: t('Set goals, run evaluations, and track employee growth.')
    },

    {
      icon: 'bar-chart-2',
      title: t('Reports & Analytics'),
      description: t('Get actionable insights on workforce productivity and HR metrics.')
    },
  ];

  const features = sectionData.features_list && sectionData.features_list.length > 0
    ? sectionData.features_list
    : defaultFeatures;

  return (
    <section id="features" className="py-12 sm:py-16 lg:py-20" style={{ backgroundColor }} ref={ref}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className={`text-center mb-8 sm:mb-12 lg:mb-16 transition-all duration-700 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {sectionData.title || t('Empowering Businesses with Smart HR Solutions')}
          </h2>
          <p className="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed font-medium">
            {sectionData.description || t('All-in-one platform to manage employees, payroll, attendance, and performance with ease. Built for professionals who value efficiency and innovation.')}
          </p>
        </div>

        {sectionImage && (
          <div className="mb-8 sm:mb-12 text-center">
            <img src={sectionImage} alt="Features" className="max-w-full h-auto rounded-xl shadow-lg mx-auto" />
          </div>
        )}

        <div className={`grid grid-cols-1 ${columns >= 2 ? 'sm:grid-cols-2' : ''} ${columns >= 3 ? 'lg:grid-cols-3' : ''} ${columns >= 4 ? 'xl:grid-cols-4' : ''} gap-6 sm:gap-8 transition-all duration-700 delay-200 ${isVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'}`}>
          {features.map((feature, index) => {
            const IconComponent = iconMap[feature.icon] || QrCode;
            return (
              <div
                key={index}
                className="bg-white p-8 rounded-xl border border-gray-200 hover:border-gray-300 hover:shadow-lg transition-all duration-200"
                role="article"
                aria-labelledby={`feature-${index}-title`}
              >
                <div className="w-12 h-12 rounded-lg flex items-center justify-center mb-6" style={{ backgroundColor: `${brandColor}15` }} role="img" aria-label={`${feature.title} icon`}>
                  <IconComponent className="w-6 h-6" style={{ color: brandColor }} aria-hidden="true" />
                </div>
                <h3 className="text-xl font-bold text-gray-900 mb-4" id={`feature-${index}-title`}>
                  {feature.title}
                </h3>
                <p className="text-gray-600 leading-relaxed">
                  {feature.description}
                </p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}