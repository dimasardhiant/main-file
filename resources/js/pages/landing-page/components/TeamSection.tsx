import React from 'react';
import { Linkedin, Twitter, Mail } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface TeamSectionProps {
  brandColor?: string;
  settings?: any;
  sectionData?: {
    title?: string;
    subtitle?: string;
    members?: Array<{
      name: string;
      role: string;
      bio: string;
      image?: string;
      linkedin?: string;
      twitter?: string;
      email?: string;
    }>;
    cta_title?: string;
    cta_description?: string;
    cta_button_text?: string;
  };
}

export default function TeamSection({ settings, sectionData, brandColor = '#3b82f6' }: TeamSectionProps) {
  const { t } = useTranslation();
  const defaultMembers = [
    {
      name: 'John Doe',
      role: t('CEO & Founder'),
      bio: t('Experienced HR tech entrepreneur passionate about building intuitive HR solutions.'),
      image: '',
      linkedin: '#',
      email: 'john@example.com'
    },
    {
      name: 'Jane Smith',
      role: t('CTO'),
      bio: t('Leads the tech team to create scalable and secure HR platforms.'),
      image: '',
      linkedin: '#',
      email: 'jane@example.com'
    },
    {
      name: 'Michael Lee',
      role: t('Head of Product'),
      bio: t('Designs user-centric features to simplify HR processes.'),
      image: '',
      linkedin: '#',
      email: 'michael@example.com'
    },
    {
      name: 'Emily Davis',
      role: t('HR Manager'),
      bio: t('Oversees employee engagement, recruitment, and HR operations.'),
      image: '',
      linkedin: '#',
      email: 'emily@example.com'
    },


  ];

  const teamMembers = sectionData?.members && sectionData.members.length > 0
    ? sectionData.members
    : defaultMembers;

  return (
    <section className="py-12 sm:py-16 lg:py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8 sm:mb-12 lg:mb-16">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            {sectionData?.title || t('Meet Our Team')}
          </h2>
          <p className="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed font-medium">
            {sectionData?.subtitle || t('We\'re a dedicated team of HR and technology experts.')}
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
          {teamMembers.map((member, index) => (
            <div key={index} className="bg-gray-50 rounded-xl p-6 border border-gray-200 hover:border-gray-300 transition-colors">
              {/* Profile Image */}
              <div className="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center" style={{ backgroundColor: brandColor }}>
                <span className="text-white text-lg font-bold">
                  {member.name.split(' ').map(n => n[0]).join('')}
                </span>
              </div>

              {/* Member Info */}
              <div className="text-center">
                <h3 className="text-lg font-semibold text-gray-900 mb-1">
                  {member.name}
                </h3>
                <p className="text-gray-700 font-medium mb-3">
                  {member.role}
                </p>
                <p className="text-gray-600 text-sm leading-relaxed mb-4">
                  {member.bio}
                </p>

                {/* Social Links */}
                <div className="flex justify-center gap-2">
                  {member.linkedin && (
                    <a
                      href={member.linkedin}
                      className="w-8 h-8 bg-white rounded-full flex items-center justify-center border border-gray-200 hover:border-gray-300 transition-colors"
                    >
                      <Linkedin className="w-4 h-4 text-gray-600" />
                    </a>
                  )}
                  {member.twitter && (
                    <a
                      href={member.twitter}
                      className="w-8 h-8 bg-white rounded-full flex items-center justify-center border border-gray-200 hover:border-gray-300 transition-colors"
                    >
                      <Twitter className="w-4 h-4 text-gray-600" />
                    </a>
                  )}
                  {member.email && (
                    <a
                      href={`mailto:${member.email}`}
                      className="w-8 h-8 bg-white rounded-full flex items-center justify-center border border-gray-200 hover:border-gray-300 transition-colors"
                    >
                      <Mail className="w-4 h-4 text-gray-600" />
                    </a>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Join Team CTA */}
        {(sectionData?.cta_title || sectionData?.cta_description || sectionData?.cta_button_text) && (
          <div className="text-center mt-8 sm:mt-12 lg:mt-16">
            <div className="bg-gray-50 rounded-xl p-8 border border-gray-200 max-w-2xl mx-auto">
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                {sectionData?.cta_title || t('Want to Join Our Team?')}
              </h3>
              <p className="text-gray-600 mb-6">
                {sectionData?.cta_description || t('We\'re always looking for talented individuals to shape the future of HR management.')}
              </p>
              <button className="text-white px-8 py-3 rounded-lg transition-colors font-semibold" style={{ backgroundColor: brandColor }}>
                {sectionData?.cta_button_text || t('View Open Positions')}
              </button>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}