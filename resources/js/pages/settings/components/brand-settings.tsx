import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { ThemePreview } from '@/components/theme-preview';
import { useAppearance, type Appearance, type ThemeColor } from '@/hooks/use-appearance';
import { useLayout, type LayoutPosition } from '@/contexts/LayoutContext';
import { useSidebarSettings } from '@/contexts/SidebarContext';
import { useBrand } from '@/contexts/BrandContext';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import { Palette, Save, Upload, Check, Layout, Moon, FileText, Sidebar as SidebarIcon } from 'lucide-react';
import { SettingsSection } from '@/components/settings-section';
import { SidebarPreview } from '@/components/sidebar-preview';
import MediaPicker from '@/components/MediaPicker';
import { useTranslation } from 'react-i18next';
import { usePage, router } from '@inertiajs/react';
import { getImagePath } from '@/utils/helpers';

// Cookie utility functions
const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') return;
  const maxAge = days * 24 * 60 * 60;
  document.cookie = `${name}=${encodeURIComponent(value)};path=/;max-age=${maxAge};SameSite=Lax`;
};

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

// Define the brand settings interface
export interface BrandSettings {
  logoDark: string;
  logoLight: string;
  favicon: string;
  titleText: string;
  footerText: string;
  companyMobile?: string;
  themeColor: ThemeColor;
  customColor: string;
  sidebarVariant: string;
  sidebarStyle: string;
  layoutDirection: LayoutPosition;
  themeMode: Appearance;
}

// Default brand settings
export const DEFAULT_BRAND_SETTINGS: BrandSettings = {
  logoDark: 'logo/logo-dark.png',
  logoLight: 'logo/logo-light.png',
  favicon: 'logo/favicon.png',
  titleText: 'WorkDo',
  footerText: '© 2024 WorkDo. All rights reserved.',
  companyMobile: '',
  themeColor: 'green',
  customColor: '#3b82f6',
  sidebarVariant: 'inset',
  sidebarStyle: 'plain',
  layoutDirection: 'left',
  themeMode: 'light',
};

// Get brand settings from props or cookies/localStorage as fallback
export const getBrandSettings = (userSettings?: Record<string, string>, globalSettings?: any): BrandSettings => {
  const isDemo = globalSettings?.is_demo || false;

  // In demo mode, prioritize cookies over backend settings
  if (isDemo) {
    try {
      const themeSettings = getCookie('themeSettings');
      const sidebarSettings = getCookie('sidebarSettings');
      const layoutPosition = getCookie('layoutPosition');
      const brandSettings = getCookie('brandSettings');

      const parsedTheme = themeSettings ? JSON.parse(themeSettings) : {};
      const parsedSidebar = sidebarSettings ? JSON.parse(sidebarSettings) : {};
      const parsedBrand = brandSettings ? JSON.parse(brandSettings) : {};

      return {
        logoDark: parsedBrand.logoDark || userSettings?.logoDark || DEFAULT_BRAND_SETTINGS.logoDark,
        logoLight: parsedBrand.logoLight || userSettings?.logoLight || DEFAULT_BRAND_SETTINGS.logoLight,
        favicon: parsedBrand.favicon || userSettings?.favicon || DEFAULT_BRAND_SETTINGS.favicon,
        titleText: parsedBrand.titleText || userSettings?.titleText || DEFAULT_BRAND_SETTINGS.titleText,
        footerText: parsedBrand.footerText || userSettings?.footerText || DEFAULT_BRAND_SETTINGS.footerText,
        companyMobile: parsedBrand.companyMobile || userSettings?.companyMobile || DEFAULT_BRAND_SETTINGS.companyMobile,
        themeColor: parsedTheme.themeColor || DEFAULT_BRAND_SETTINGS.themeColor,
        customColor: parsedTheme.customColor || DEFAULT_BRAND_SETTINGS.customColor,
        sidebarVariant: parsedSidebar.variant || DEFAULT_BRAND_SETTINGS.sidebarVariant,
        sidebarStyle: parsedSidebar.style || DEFAULT_BRAND_SETTINGS.sidebarStyle,
        layoutDirection: layoutPosition || DEFAULT_BRAND_SETTINGS.layoutDirection,
        themeMode: parsedTheme.appearance || DEFAULT_BRAND_SETTINGS.themeMode,
      };
    } catch (error) {
      // Fall through to normal logic if cookie parsing fails
    }
  }

  // If we have settings from the backend, use those (non-demo mode)
  if (userSettings) {
    return {
      logoDark: userSettings.logoDark || DEFAULT_BRAND_SETTINGS.logoDark,
      logoLight: userSettings.logoLight || DEFAULT_BRAND_SETTINGS.logoLight,
      favicon: userSettings.favicon || DEFAULT_BRAND_SETTINGS.favicon,
      titleText: userSettings.titleText || DEFAULT_BRAND_SETTINGS.titleText,
      footerText: userSettings.footerText || DEFAULT_BRAND_SETTINGS.footerText,
      companyMobile: userSettings.companyMobile || DEFAULT_BRAND_SETTINGS.companyMobile,
      themeColor: (userSettings.themeColor as ThemeColor) || DEFAULT_BRAND_SETTINGS.themeColor,
      customColor: userSettings.customColor || DEFAULT_BRAND_SETTINGS.customColor,
      sidebarVariant: userSettings.sidebarVariant || DEFAULT_BRAND_SETTINGS.sidebarVariant,
      sidebarStyle: userSettings.sidebarStyle || DEFAULT_BRAND_SETTINGS.sidebarStyle,
      layoutDirection: (userSettings.layoutDirection as LayoutPosition) || DEFAULT_BRAND_SETTINGS.layoutDirection,
      themeMode: (userSettings.themeMode as Appearance) || DEFAULT_BRAND_SETTINGS.themeMode,
    };
  }

  // Fallback to defaults
  return DEFAULT_BRAND_SETTINGS;
};

interface BrandSettingsProps {
  settings?: Record<string, string>;
}

export default function BrandSettings({ settings }: BrandSettingsProps) {
  const { t } = useTranslation();
  const { props } = usePage();
  const currentGlobalSettings = (props as any).globalSettings;
  const auth = (props as any).auth;
  const userRole = auth?.user?.type || auth?.user?.role;
  const [brandSettings, setBrandSettings] = useState<BrandSettings>(() => getBrandSettings(currentGlobalSettings || settings, currentGlobalSettings));
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [activeSection, setActiveSection] = useState<'logos' | 'text' | 'theme'>('logos');

  // Get theme hooks
  const {
    updateAppearance,
    updateThemeColor,
    updateCustomColor,
    saveThemeSettings
  } = useAppearance();

  const { updatePosition, saveLayoutPosition } = useLayout();
  const { updateVariant, updateStyle, saveSidebarSettings } = useSidebarSettings();

  // Load settings when globalSettings change (but not while saving)
  useEffect(() => {
    if (isSaving) return; // Don't reset form while saving

    const newBrandSettings = getBrandSettings(currentGlobalSettings || settings, currentGlobalSettings);
    setBrandSettings(newBrandSettings);

    // Also sync sidebar settings from cookies or localStorage
    try {
      const isDemo = currentGlobalSettings?.is_demo || false;
      let sidebarSettings = null;
      
      if (isDemo) {
        // In demo mode, get from cookies
        sidebarSettings = getCookie('sidebarSettings');
      }
      // In non-demo mode, sidebar settings come from database via props
      
      if (sidebarSettings) {
        const parsedSettings = JSON.parse(sidebarSettings);
        setBrandSettings(prev => ({
          ...prev,
          sidebarVariant: parsedSettings.variant || prev.sidebarVariant,
          sidebarStyle: parsedSettings.style || prev.sidebarStyle
        }));
      }
    } catch (error) {
      console.error('Error loading sidebar settings', error);
    }
  }, [currentGlobalSettings, settings, isSaving]);

  // Handle input changes
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setBrandSettings(prev => ({ ...prev, [name]: value }));

    // Update brand context if the input is for a logo
    if (['logoLight', 'logoDark', 'favicon'].includes(name)) {
      updateBrandSettings({ [name]: value });
    }
  };

  // Handle media picker selection
  const handleMediaSelect = (name: string, url: string) => {
    setBrandSettings(prev => ({ ...prev, [name]: url }));
    updateBrandSettings({ [name]: url });
  };

  // Import useBrand hook
  const { updateBrandSettings } = useBrand();

  // Handle theme color change
  const handleThemeColorChange = (color: ThemeColor) => {
    setBrandSettings(prev => ({ ...prev, themeColor: color }));
    updateThemeColor(color);
  };

  // Handle custom color change
  const handleCustomColorChange = (color: string) => {
    setBrandSettings(prev => ({ ...prev, customColor: color }));
    // Set as active custom color when user is editing it
    updateCustomColor(color, true);
  };

  // Handle sidebar variant change
  const handleSidebarVariantChange = (variant: string) => {
    setBrandSettings(prev => ({ ...prev, sidebarVariant: variant }));
    updateVariant(variant as any);
  };

  // Handle sidebar style change
  const handleSidebarStyleChange = (style: string) => {
    setBrandSettings(prev => ({ ...prev, sidebarStyle: style }));
    updateStyle(style);
  };

  // Handle layout direction change
  const handleLayoutDirectionChange = (direction: LayoutPosition) => {
    setBrandSettings(prev => ({ ...prev, layoutDirection: direction }));
    updatePosition(direction);
  };

  // Handle theme mode change
  const handleThemeModeChange = (mode: Appearance) => {
    setBrandSettings(prev => ({ ...prev, themeMode: mode }));
    // Only update appearance, don't let it reset the theme color
    updateAppearance(mode);
    // Immediately reapply the current theme color to prevent it from changing
    setTimeout(() => {
      updateThemeColor(brandSettings.themeColor);
      if (brandSettings.themeColor === 'custom') {
        updateCustomColor(brandSettings.customColor);
      }
    }, 0);
  };

  // Save settings
  const saveSettings = () => {
    setIsLoading(true);
    setIsSaving(true);

    // Update theme settings
    updateThemeColor(brandSettings.themeColor);
    if (brandSettings.themeColor === 'custom') {
      updateCustomColor(brandSettings.customColor);
    }
    updateAppearance(brandSettings.themeMode);
    updatePosition(brandSettings.layoutDirection);

    // Update sidebar settings
    updateVariant(brandSettings.sidebarVariant as any);
    updateStyle(brandSettings.sidebarStyle);
    
    // Save all settings to cookies in demo mode
    saveThemeSettings();
    saveSidebarSettings();
    saveLayoutPosition();

    // Update brand context
    updateBrandSettings({
      logoLight: brandSettings.logoLight,
      logoDark: brandSettings.logoDark,
      favicon: brandSettings.favicon
    });

    // Individual update functions already handled storage (cookies in demo mode, localStorage in normal mode)
    // Only save to database in normal mode

    // Save to database using Inertia
    router.post(route('settings.brand.update'), {
      settings: brandSettings
    }, {
      preserveScroll: true,
      onSuccess: (page) => {
        setIsLoading(false);
        const successMessage = page.props.flash?.success;
        const errorMessage = page.props.flash?.error;

        if (successMessage) {
          toast.success(successMessage);
          // Reset saving state after success
          setTimeout(() => setIsSaving(false), 500);
        } else if (errorMessage) {
          toast.error(errorMessage);
        }
      },
      onError: (errors) => {
        setIsLoading(false);
        setIsSaving(false);
        const errorMessage = errors.error || Object.values(errors).join(', ') || t('Failed to save brand settings');
        toast.error(errorMessage);
      }
    });
  };

  return (
    <SettingsSection
      title={t("Brand Settings")}
      description={t("Customize your application's branding and appearance")}
      action={
        <Button onClick={saveSettings} disabled={isLoading} size="sm">
          <Save className="h-4 w-4 mr-2" />
          {isLoading ? t('Saving...') : t('Save Changes')}
        </Button>
      }
    >
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <div className="flex space-x-2 mb-6">
            <Button
              variant={activeSection === 'logos' ? "default" : "outline"}
              size="sm"
              onClick={() => setActiveSection('logos')}
              className="flex-1"
            >
              <Upload className="h-4 w-4 mr-2" />
              {t("Logos")}
            </Button>
            <Button
              variant={activeSection === 'text' ? "default" : "outline"}
              size="sm"
              onClick={() => setActiveSection('text')}
              className="flex-1"
            >
              <FileText className="h-4 w-4 mr-2" />
              {t("Text")}
            </Button>
            <Button
              variant={activeSection === 'theme' ? "default" : "outline"}
              size="sm"
              onClick={() => setActiveSection('theme')}
              className="flex-1"
            >
              <Palette className="h-4 w-4 mr-2" />
              {t("Theme")}
            </Button>
          </div>

          {/* Logos Section */}
          {activeSection === 'logos' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-3">
                  <Label>{t("Logo Dark")}</Label>
                  <div className="flex flex-col gap-3">
                    <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 dark:bg-white h-32">
                      {brandSettings.logoDark ? (
                        <img
                          src={getImagePath(brandSettings.logoDark)}
                          alt="Dark Logo"
                          className="max-h-full max-w-full object-contain"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src = 'images/default/image-not-found.jpg';
                          }}
                        />
                      ) : (
                        <div className="text-muted-foreground flex flex-col items-center gap-2">
                          <div className="h-12 w-24 bg-muted flex items-center justify-center rounded border border-dashed">
                            <span className="font-semibold text-muted-foreground">{t("Logo")}</span>
                          </div>
                          <span className="text-xs">No logo selected</span>
                        </div>
                      )}
                    </div>
                    <MediaPicker
                      label=""
                      value={brandSettings.logoDark}
                      onChange={(url) => handleMediaSelect('logoDark', url)}
                      placeholder="Select dark mode logo..."
                      showPreview={false}
                    />
                  </div>
                </div>

                <div className="space-y-3">
                  <Label>{t("Logo Light")}</Label>
                  <div className="flex flex-col gap-3">
                    <div className="border rounded-md p-4 flex items-center justify-center bg-black h-32">
                      {brandSettings.logoLight ? (
                        <img
                          src={getImagePath(brandSettings.logoLight)}
                          alt="Light Logo"
                          className="max-h-full max-w-full object-contain"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src = 'images/default/image-not-found.jpg';
                          }}
                        />
                      ) : (
                        <div className="text-muted-foreground flex flex-col items-center gap-2">
                          <div className="h-12 w-24 bg-muted flex items-center justify-center rounded border border-dashed">
                            <span className="font-semibold text-muted-foreground">{t("Logo")}</span>
                          </div>
                          <span className="text-xs">No logo selected</span>
                        </div>
                      )}
                    </div>
                    <MediaPicker
                      label=""
                      value={brandSettings.logoLight}
                      onChange={(url) => handleMediaSelect('logoLight', url)}
                      placeholder="Select light mode logo..."
                      showPreview={false}
                    />
                  </div>
                </div>

                <div className="space-y-3">
                  <Label>{t("Favicon")}</Label>
                  <div className="flex flex-col gap-3">
                    <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-20">
                      {brandSettings.favicon ? (
                        <img
                          src={getImagePath(brandSettings.favicon)}
                          alt="Favicon"
                          className="h-16 w-16 object-contain"
                          onError={(e) => {
                            (e.currentTarget as HTMLImageElement).src = 'images/default/image-not-found.jpg';
                          }}
                        />
                      ) : (
                        <div className="text-muted-foreground flex flex-col items-center gap-1">
                          <div className="h-10 w-10 bg-muted flex items-center justify-center rounded border border-dashed">
                            <span className="font-semibold text-xs text-muted-foreground">{t("Icon")}</span>
                          </div>
                          <span className="text-xs">No favicon selected</span>
                        </div>
                      )}
                    </div>
                    <MediaPicker
                      label=""
                      value={brandSettings.favicon}
                      onChange={(url) => handleMediaSelect('favicon', url)}
                      placeholder="Select favicon..."
                      showPreview={false}
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Text Section */}
          {activeSection === 'text' && (
            <div className="space-y-6">
              <div className="grid grid-cols-1 gap-6">
                <div className="space-y-3">
                  <Label htmlFor="titleText">{t("Title Text")}</Label>
                  <Input
                    id="titleText"
                    name="titleText"
                    value={brandSettings.titleText}
                    onChange={handleInputChange}
                    placeholder="WorkDo"
                  />
                  <p className="text-xs text-muted-foreground">
                    {t("Application title displayed in the browser tab")}
                  </p>
                </div>

                <div className="space-y-3">
                  <Label htmlFor="footerText">{t("Footer Text")}</Label>
                  <Input
                    id="footerText"
                    name="footerText"
                    value={brandSettings.footerText}
                    onChange={handleInputChange}
                    placeholder="© 2024 WorkDo. All rights reserved."
                  />
                  <p className="text-xs text-muted-foreground">
                    {t("Text displayed in the footer")}
                  </p>
                </div>

                {userRole === 'company' && (
                  <div className="space-y-3">
                    <Label htmlFor="companyMobile">{t("Company Mobile Number")}</Label>
                    <Input
                      id="companyMobile"
                      name="companyMobile"
                      value={brandSettings.companyMobile || ''}
                      onChange={handleInputChange}
                      placeholder="+1 234 567 8900"
                    />
                    <p className="text-xs text-muted-foreground">
                      {t("Company contact mobile number")}
                    </p>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Theme Section */}
          {activeSection === 'theme' && (
            <div className="space-y-6">
              <div className="flex flex-col space-y-8">
                {/* Theme Color Section */}
                <div className="space-y-4">
                  <div className="flex items-center">
                    <Palette className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Theme Color")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="grid grid-cols-6 gap-2">
                    {Object.entries({ blue: '#3b82f6', green: '#10b77f', purple: '#8b5cf6', orange: '#f97316', red: '#ef4444' }).map(([color, hex]) => (
                      <Button
                        key={color}
                        type="button"
                        variant={brandSettings.themeColor === color ? "default" : "outline"}
                        className="h-8 w-full p-0 relative"
                        style={{ backgroundColor: brandSettings.themeColor === color ? hex : 'transparent' }}
                        onClick={() => handleThemeColorChange(color as ThemeColor)}
                      >
                        <span
                          className="absolute inset-1 rounded-sm"
                          style={{ backgroundColor: hex }}
                        />
                      </Button>
                    ))}
                    <Button
                      type="button"
                      variant={brandSettings.themeColor === 'custom' ? "default" : "outline"}
                      className="h-8 w-full p-0 relative"
                      style={{ backgroundColor: brandSettings.themeColor === 'custom' ? brandSettings.customColor : 'transparent' }}
                      onClick={() => handleThemeColorChange('custom')}
                    >
                      <span
                        className="absolute inset-1 rounded-sm"
                        style={{ backgroundColor: settings.customColor }}
                      />
                    </Button>
                  </div>

                  {brandSettings.themeColor === 'custom' && (
                    <div className="space-y-2 mt-4">
                      <Label htmlFor="customColor">{t("Custom Color")}</Label>
                      <div className="flex gap-2">
                        <div className="relative">
                          <Input
                            id="colorPicker"
                            type="color"
                            value={brandSettings.customColor}
                            onChange={(e) => handleCustomColorChange(e.target.value)}
                            className="absolute inset-0 opacity-0 cursor-pointer"
                          />
                          <div
                            className="w-10 h-10 rounded border cursor-pointer"
                            style={{ backgroundColor: brandSettings.customColor }}
                          />
                        </div>
                        <Input
                          id="customColor"
                          name="customColor"
                          type="text"
                          value={brandSettings.customColor}
                          onChange={(e) => handleCustomColorChange(e.target.value)}
                          placeholder="#3b82f6"
                        />
                      </div>
                    </div>
                  )}
                </div>

                {/* Sidebar Section */}
                <div className="space-y-4">
                  <div className="flex items-center">
                    <SidebarIcon className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Sidebar")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="space-y-6">
                    <div>
                      <Label className="mb-2 block">{t("Sidebar Variant")}</Label>
                      <div className="grid grid-cols-3 gap-3">
                        {['inset', 'floating', 'minimal'].map((variant) => (
                          <Button
                            key={variant}
                            type="button"
                            variant={brandSettings.sidebarVariant === variant ? "default" : "outline"}
                            className="h-10 justify-start"
                            style={{
                              backgroundColor: brandSettings.sidebarVariant === variant ?
                                (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                                'transparent'
                            }}
                            onClick={() => handleSidebarVariantChange(variant)}
                          >
                            {variant.charAt(0).toUpperCase() + variant.slice(1)}
                            {brandSettings.sidebarVariant === variant && (
                              <Check className="h-4 w-4 ml-2" />
                            )}
                          </Button>
                        ))}
                      </div>
                    </div>

                    <div>
                      <Label className="mb-2 block">{t("Sidebar Style")}</Label>
                      <div className="grid grid-cols-3 gap-3">
                        {[
                          { id: 'plain', name: 'Plain' },
                          { id: 'colored', name: 'Colored' },
                          { id: 'gradient', name: 'Gradient' }
                        ].map((style) => (
                          <Button
                            key={style.id}
                            type="button"
                            variant={brandSettings.sidebarStyle === style.id ? "default" : "outline"}
                            className="h-10 justify-start"
                            style={{
                              backgroundColor: brandSettings.sidebarStyle === style.id ?
                                (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                                'transparent'
                            }}
                            onClick={() => handleSidebarStyleChange(style.id)}
                          >
                            {style.name}
                            {brandSettings.sidebarStyle === style.id && (
                              <Check className="h-4 w-4 ml-2" />
                            )}
                          </Button>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                {/* Layout Section */}
                <div className="space-y-4">
                  <div className="flex items-center">
                    <Layout className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Layout")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="space-y-2">
                    <Label className="mb-2 block">{t("Layout Direction")}</Label>
                    <div className="grid grid-cols-2 gap-2">
                      <Button
                        type="button"
                        variant={brandSettings.layoutDirection === "left" ? "default" : "outline"}
                        className="h-10 justify-start"
                        style={{
                          backgroundColor: brandSettings.layoutDirection === "left" ?
                            (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                            'transparent'
                        }}
                        onClick={() => handleLayoutDirectionChange("left")}
                      >
                        {t("Left-to-Right")}
                        {brandSettings.layoutDirection === "left" && (
                          <Check className="h-4 w-4 ml-2" />
                        )}
                      </Button>
                      <Button
                        type="button"
                        variant={brandSettings.layoutDirection === "right" ? "default" : "outline"}
                        className="h-10 justify-start"
                        style={{
                          backgroundColor: brandSettings.layoutDirection === "right" ?
                            (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                            'transparent'
                        }}
                        onClick={() => handleLayoutDirectionChange("right")}
                      >
                        {t("Right-to-Left")}
                        {brandSettings.layoutDirection === "right" && (
                          <Check className="h-4 w-4 ml-2" />
                        )}
                      </Button>
                    </div>
                  </div>
                </div>

                {/* Mode Section */}
                <div className="space-y-4">
                  <div className="flex items-center">
                    <Moon className="h-5 w-5 mr-2 text-muted-foreground" />
                    <h3 className="text-base font-medium">{t("Theme Mode")}</h3>
                  </div>
                  <Separator className="my-2" />

                  <div className="space-y-2">
                    <div className="grid grid-cols-3 gap-2">
                      <Button
                        type="button"
                        variant={brandSettings.themeMode === "light" ? "default" : "outline"}
                        className="h-10 justify-start"
                        style={{
                          backgroundColor: brandSettings.themeMode === "light" ?
                            (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                            'transparent'
                        }}
                        onClick={() => handleThemeModeChange("light")}
                      >
                        {t("Light")}
                        {brandSettings.themeMode === "light" && (
                          <Check className="h-4 w-4 ml-2" />
                        )}
                      </Button>
                      <Button
                        type="button"
                        variant={brandSettings.themeMode === "dark" ? "default" : "outline"}
                        className="h-10 justify-start"
                        style={{
                          backgroundColor: brandSettings.themeMode === "dark" ?
                            (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                            'transparent'
                        }}
                        onClick={() => handleThemeModeChange("dark")}
                      >
                        {t("Dark")}
                        {brandSettings.themeMode === "dark" && (
                          <Check className="h-4 w-4 ml-2" />
                        )}
                      </Button>
                      <Button
                        type="button"
                        variant={brandSettings.themeMode === "system" ? "default" : "outline"}
                        className="h-10 justify-start"
                        style={{
                          backgroundColor: brandSettings.themeMode === "system" ?
                            (brandSettings.themeColor === 'custom' ? brandSettings.customColor : null) :
                            'transparent'
                        }}
                        onClick={() => handleThemeModeChange("system")}
                      >
                        {t("System")}
                        {brandSettings.themeMode === "system" && (
                          <Check className="h-4 w-4 ml-2" />
                        )}
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Preview Column */}
        <div className="lg:col-span-1">
          <div className="sticky top-20 space-y-6">
            <div className="border rounded-md p-4">
              <div className="flex items-center gap-2 mb-4">
                <Palette className="h-4 w-4" />
                <h3 className="font-medium">{t("Live Preview")}</h3>
              </div>

              {/* Comprehensive Theme Preview */}
              <ThemePreview />

              {/* Text Preview */}
              <div className="mt-4 pt-4 border-t">
                <div className="text-xs text-muted-foreground">{t("Footer:")} <span className="font-medium text-foreground">{brandSettings.footerText}</span></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </SettingsSection>
  );
}