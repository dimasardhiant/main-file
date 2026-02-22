import { createContext, ReactNode, useContext, useEffect, useState } from 'react';
import { getSidebarSettings, SidebarSettings } from '@/components/sidebar-style-settings';

type SidebarContextType = {
  variant: SidebarSettings['variant'];
  collapsible: SidebarSettings['collapsible'];
  style: string;
  updateVariant: (variant: SidebarSettings['variant']) => void;
  updateCollapsible: (collapsible: SidebarSettings['collapsible']) => void;
  updateStyle: (style: string) => void;
};

const SidebarContext = createContext<SidebarContextType | undefined>(undefined);

// Extended sidebar settings with style
interface ExtendedSidebarSettings extends SidebarSettings {
  style: string;
}

// Default sidebar settings with style
const DEFAULT_EXTENDED_SETTINGS: ExtendedSidebarSettings = {
  variant: 'inset',
  collapsible: 'icon',
  style: 'plain'
};

// Cookie utility for sidebar
const getCookie = (name: string): string | null => {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    const cookieValue = parts.pop()?.split(';').shift();
    return cookieValue ? decodeURIComponent(cookieValue) : null;
  }
  return null;
};

const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') return;
  const maxAge = days * 24 * 60 * 60;
  document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
};

// Get extended sidebar settings from cookies or database
const getExtendedSidebarSettings = (): ExtendedSidebarSettings => {
  const isDemo = (window as any).page?.props?.globalSettings?.is_demo || false;
  
  if (isDemo) {
    // In demo mode, use cookies
    try {
      const savedSettings = getCookie('sidebarSettings');
      return savedSettings ? JSON.parse(savedSettings) : DEFAULT_EXTENDED_SETTINGS;
    } catch (error) {
      return DEFAULT_EXTENDED_SETTINGS;
    }
  } else {
    // In normal mode, get from database via globalSettings
    const globalSettings = (window as any).page?.props?.globalSettings;
    if (globalSettings) {
      return {
        variant: globalSettings.sidebarVariant || DEFAULT_EXTENDED_SETTINGS.variant,
        collapsible: DEFAULT_EXTENDED_SETTINGS.collapsible,
        style: globalSettings.sidebarStyle || DEFAULT_EXTENDED_SETTINGS.style
      };
    }
    return DEFAULT_EXTENDED_SETTINGS;
  }
};

export const SidebarProvider = ({ children }: { children: ReactNode }) => {
  const [settings, setSettings] = useState<ExtendedSidebarSettings>(() => getExtendedSidebarSettings());
  
  // Update settings when page props change (for non-demo mode)
  useEffect(() => {
    const isDemo = (window as any).page?.props?.globalSettings?.is_demo || false;
    if (!isDemo) {
      const newSettings = getExtendedSidebarSettings();
      setSettings(newSettings);
    }
  }, [(window as any).page?.props?.globalSettings]);

  // Update variant
  const updateVariant = (variant: SidebarSettings['variant']) => {
    setSettings(prev => ({ ...prev, variant }));
  };

  // Update collapsible
  const updateCollapsible = (collapsible: SidebarSettings['collapsible']) => {
    setSettings(prev => ({ ...prev, collapsible }));
  };

  // Update style
  const updateStyle = (style: string) => {
    setSettings(prev => ({ ...prev, style }));
  };

  // Save sidebar settings to cookies (demo mode only)
  const saveSidebarSettings = () => {
    const isDemo = (window as any).page?.props?.globalSettings?.is_demo || false;
    
    if (isDemo) {
      setCookie('sidebarSettings', JSON.stringify(settings));
    }
  };

  useEffect(() => {
    // Listen for storage events to update settings when changed from another tab
    const handleStorageChange = (event: StorageEvent) => {
      if (event.key === 'sidebarSettings') {
        try {
          const newSettings = JSON.parse(event.newValue || '');
          setSettings(newSettings);
        } catch (error) {
          console.error('Failed to parse sidebar settings', error);
        }
      }
    };

    window.addEventListener('storage', handleStorageChange);
    return () => window.removeEventListener('storage', handleStorageChange);
  }, []);

  return (
    <SidebarContext.Provider value={{ 
      variant: settings.variant, 
      collapsible: settings.collapsible,
      style: settings.style,
      updateVariant,
      updateCollapsible,
      updateStyle,
      saveSidebarSettings
    }}>
      {children}
    </SidebarContext.Provider>
  );
};

export const useSidebarSettings = () => {
  const context = useContext(SidebarContext);
  if (!context) throw new Error('useSidebarSettings must be used within SidebarProvider');
  return context;
};