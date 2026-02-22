import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Play } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { getImagePath } from '@/utils/helpers';

interface HeroSectionProps {
  brandColor?: string;
  settings: any;
  sectionData: {
    title?: string;
    subtitle?: string;
    announcement_text?: string;
    primary_button_text?: string;
    secondary_button_text?: string;
    image?: string;
    stats?: Array<{ value: string; label: string }>;
    card?: {
      name: string;
      title: string;
      company: string;
      initials: string;
    };
  };
}

export default function HeroSection({ settings, sectionData, brandColor = '#3b82f6' }: HeroSectionProps) {
  const { t } = useTranslation();
  const { globalSettings } = usePage().props as any;
  const isSaas = globalSettings?.is_saas;

  // Helper to get full URL for images
  const getImageUrl = (path: string) => {
    if (!path) return null;
    if (path.startsWith('/screenshots/')) return `${window.appSettings.imageUrl}${path}`;
    return getImagePath(path);
  };

  const heroImage = getImageUrl(sectionData.image);

  return (
    <section id="hero" className="pt-16 bg-gray-50 min-h-screen flex items-center">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
        <div className="grid lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16 items-center">
          {/* Left Content */}
          <div className="text-center lg:text-left space-y-6 sm:space-y-8">
            {sectionData.announcement_text && (
              <div className="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium border">
                {sectionData.announcement_text}
              </div>
            )}

            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight" role="banner" aria-label="Main heading">
              {sectionData.title || t('Create Your Digital Business Card')}
            </h1>

            <p className="text-lg md:text-xl text-gray-600 leading-relaxed max-w-2xl font-medium">
              {sectionData.subtitle || t('Manage employees, payroll, attendance, and more in one powerful platform.')}
            </p>

            <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center lg:justify-start">
              {isSaas && (
                <Link
                  href={route('register')}
                  className="text-white px-8 py-4 rounded-lg transition-colors font-semibold text-base flex items-center justify-center gap-2 hover:opacity-90"
                  style={{ backgroundColor: brandColor }}
                  aria-label="Start free trial - Register for HRM"
                >
                  {sectionData.primary_button_text || t('Start Free Trial')}
                  <ArrowRight size={18} />
                </Link>
              )}
              <Link
                href={route('login')}
                className="border px-8 py-4 rounded-lg transition-colors font-semibold text-base flex items-center justify-center gap-2 hover:bg-gray-50"
                style={{ borderColor: brandColor, color: brandColor }}
                aria-label="Login to existing HRM account"
              >
                <Play size={18} />
                {sectionData.secondary_button_text || t('Login')}
              </Link>
            </div>

            {sectionData.stats && sectionData.stats.length > 0 && (
              <div className="grid grid-cols-3 gap-4 sm:gap-6 lg:gap-8 pt-8 sm:pt-12">
                {sectionData.stats.map((stat, index) => (
                  <div key={index} className="text-center">
                    <div className="text-3xl md:text-4xl font-bold text-gray-900">
                      {stat.value}
                    </div>
                    <div className="text-gray-600 font-medium text-sm">{stat.label}</div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Right Content - Hero Image or Card */}
          <div className="relative">

            <div className="relative">
              <img
                src={heroImage || getImageUrl(globalSettings?.is_saas ? '/screenshots/saas/hero-default.png' : '/screenshots/non-saas/hero-default.png')}
                alt="Hero"
                className="w-full h-auto rounded-2xl shadow-xl"
              />
            </div>


            {/* Simple Decorative Elements */}
            <div className="absolute -top-4 -right-4 w-16 h-16 bg-gray-200 rounded-full opacity-50"></div>
            <div className="absolute -bottom-4 -left-4 w-12 h-12 bg-gray-300 rounded-full opacity-40"></div>
          </div>
        </div>
      </div>
    </section>
  );
}