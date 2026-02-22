import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { getBrandSettings, type BrandSettings } from '@/pages/settings/components/brand-settings';

interface BrandContextType extends BrandSettings {
  updateBrandSettings: (settings: Partial<BrandSettings>) => void;
}

const BrandContext = createContext<BrandContextType | undefined>(undefined);


export function BrandProvider({ children, globalSettings, user }: { children: ReactNode; globalSettings?: any; user?: any }) {
  // Determine which settings to use based on user role and route
  const getEffectiveSettings = () => {
    const isDemo = globalSettings?.is_demo || false;

    // In demo mode, prioritize cookies over database settings
    if (isDemo) {
      return null; // This will force getBrandSettings to use cookies
    }

    const isPublicRoute = window.location.pathname.includes('/public/') ||
      window.location.pathname === '/' ||
      window.location.pathname.includes('/auth/');

    // For public routes (landing page, auth pages), always use superadmin settings
    if (isPublicRoute) {
      return globalSettings;
    }

    // For authenticated routes, use user's own settings if company role
    if (user?.role === 'company' && user?.globalSettings) {
      return user.globalSettings;
    }

    // Default to global settings (superadmin)
    return globalSettings;
  };

  const [brandSettings, setBrandSettings] = useState<BrandSettings>(() =>
    getBrandSettings(getEffectiveSettings(), globalSettings)
  );

  // Listen for changes in settings
  useEffect(() => {
    const effectiveSettings = getEffectiveSettings();
    const updatedSettings = getBrandSettings(effectiveSettings, globalSettings);
    setBrandSettings(updatedSettings);

    // Apply theme settings immediately for landing page (both demo and non-demo modes)
    if (updatedSettings) {
      // Apply theme color globally
      const color = updatedSettings.themeColor === 'custom' ? updatedSettings.customColor : {
        blue: '#3b82f6',
        green: '#10b77f',
        purple: '#8b5cf6',
        orange: '#f97316',
        red: '#ef4444'
      }[updatedSettings.themeColor] || '#3b82f6';

      document.documentElement.style.setProperty('--theme-color', color);
      document.documentElement.style.setProperty('--primary', color);

      // Apply theme mode
      const isDark = updatedSettings.themeMode === 'dark' ||
        (updatedSettings.themeMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      document.documentElement.classList.toggle('dark', isDark);
      document.body.classList.toggle('dark', isDark);
      
      // Apply layout direction (RTL/LTR)
      document.documentElement.dir = updatedSettings.layoutDirection;
      document.documentElement.setAttribute('dir', updatedSettings.layoutDirection);
    }
  }, [globalSettings, user]);

  const updateBrandSettings = (newSettings: Partial<BrandSettings>) => {
    setBrandSettings(prev => ({ ...prev, ...newSettings }));
  };

  return (
    <BrandContext.Provider value={{ ...brandSettings, updateBrandSettings }}>
      {children}
    </BrandContext.Provider>
  );
}

export function useBrand() {
  const context = useContext(BrandContext);
  if (context === undefined) {
    throw new Error('useBrand must be used within a BrandProvider');
  }
  return context;
}