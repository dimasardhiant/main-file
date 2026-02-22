import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface CustomPage {
  id: number;
  title: string;
  slug: string;
}

interface HeaderProps {
  brandColor?: string;
  settings: {
    company_name: string;
  };
  sectionData?: any;
  customPages?: CustomPage[];
}

export default function Header({ settings, sectionData, customPages = [], brandColor = '#3b82f6' }: HeaderProps) {
  const { t } = useTranslation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const { props } = usePage();
  const isSaas = (props as any).globalSettings?.is_saas;

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20);
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const menuItems = customPages.map(page => ({
    name: page.title,
    href: route('custom-page.show', page.slug)
  }));

  const isTransparent = sectionData?.transparent;
  const backgroundColor = sectionData?.background_color || '#ffffff';
  
  const getHeaderClasses = () => {
    if (isTransparent) {
      return isScrolled 
        ? 'bg-white/95 backdrop-blur-xl shadow-lg border-b border-gray-200/50'
        : 'bg-transparent';
    }
    return isScrolled 
      ? 'shadow-lg border-b border-gray-200/50'
      : '';
  };

  const getHeaderStyle = () => {
    if (isTransparent) return {};
    return { backgroundColor };
  };

  return (
    <header 
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${getHeaderClasses()}`}
      style={getHeaderStyle()}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <div className="flex-shrink-0">
            <Link 
              href={route("home")} 
              className="text-2xl font-bold text-gray-900 transition-colors"
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {settings.company_name}
            </Link>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center space-x-8" role="navigation" aria-label="Main navigation">
            <Link
              href={route('home')}
              className="text-gray-600 text-sm font-medium transition-colors relative group"
              style={{ '--hover-color': brandColor } as React.CSSProperties}
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {t('Home')}
              <span 
                className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full" 
                style={{ backgroundColor: brandColor }}
                aria-hidden="true"
              ></span>
            </Link>
            {menuItems.map((item) => (
              <Link
                key={item.name}
                href={item.href}
                className="text-gray-600 text-sm font-medium transition-colors relative group"
                style={{ '--hover-color': brandColor } as React.CSSProperties}
                onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                onMouseLeave={(e) => e.currentTarget.style.color = ''}
              >
                {item.name}
                <span 
                  className="absolute -bottom-1 left-0 w-0 h-0.5 transition-all group-hover:w-full" 
                  style={{ backgroundColor: brandColor }}
                  aria-hidden="true"
                ></span>
              </Link>
            ))}
          </nav>

          {/* Auth Buttons */}
          <div className="hidden md:flex items-center gap-4">
            <Link
              href={route('login')}
              className="text-gray-600 text-sm font-medium transition-colors"
              onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
              onMouseLeave={(e) => e.currentTarget.style.color = ''}
            >
              {t('Login')}
            </Link>
            {isSaas && (
              <Link
                href={route('register')}
                className="px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                style={{ 
                  backgroundColor: brandColor, 
                  color: 'white',
                  borderColor: brandColor
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = 'white';
                  e.currentTarget.style.color = brandColor;
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.backgroundColor = brandColor;
                  e.currentTarget.style.color = 'white';
                }}
              >
                {t('Get Started')}
              </Link>
            )}
          </div>

          {/* Mobile menu button */}
          <div className="md:hidden">
            <button
              onClick={() => setIsMenuOpen(!isMenuOpen)}
              className="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label={isMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
              aria-expanded={isMenuOpen}
              aria-controls="mobile-menu"
            >
              {isMenuOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
          </div>
        </div>

        {/* Mobile Navigation */}
        {isMenuOpen && (
          <div className="md:hidden border-t border-gray-200" id="mobile-menu">
            <div 
              className="px-4 py-6 space-y-4"
              style={isTransparent ? { backgroundColor: 'white' } : { backgroundColor }}
            >
              <Link
                href={route('home')}
                className="block text-gray-600 hover:text-gray-900 text-base font-medium transition-colors"
                onClick={() => setIsMenuOpen(false)}
              >
                {t('Home')}
              </Link>
              {menuItems.map((item) => (
                <Link
                  key={item.name}
                  href={item.href}
                  className="block text-gray-600 hover:text-gray-900 text-base font-medium transition-colors"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {item.name}
                </Link>
              ))}
              <div className="pt-4 space-y-3 border-t border-gray-200">
                <Link
                  href={route('login')}
                  className="block w-full text-center text-gray-600 py-2.5 text-sm font-medium transition-colors"
                  onMouseEnter={(e) => e.currentTarget.style.color = brandColor}
                  onMouseLeave={(e) => e.currentTarget.style.color = ''}
                >
                  {t('Login')}
                </Link>
                {isSaas && (
                  <Link
                    href={route('register')}
                    className="block w-full text-center py-2.5 rounded-lg text-sm font-semibold transition-colors border"
                    style={{ 
                      backgroundColor: brandColor, 
                      color: 'white',
                      borderColor: brandColor
                    }}
                    onMouseEnter={(e) => {
                      e.currentTarget.style.backgroundColor = 'white';
                      e.currentTarget.style.color = brandColor;
                    }}
                    onMouseLeave={(e) => {
                      e.currentTarget.style.backgroundColor = brandColor;
                      e.currentTarget.style.color = 'white';
                    }}
                  >
                    {t('Get Started')}
                  </Link>
                )}
              </div>
            </div>
          </div>
        )}
      </div>
    </header>
  );
}