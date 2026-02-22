import { Head } from '@inertiajs/react';
import { CreditCard, Users, Smartphone, QrCode } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useBrand } from '@/contexts/BrandContext';
import { useAppearance, THEME_COLORS } from '@/hooks/use-appearance';
import { useFavicon } from '@/hooks/use-favicon';
import CookieConsentBanner from '@/components/CookieConsentBanner';
import { getImagePath } from '@/utils/helpers';
import { Head, usePage } from '@inertiajs/react';
import React from 'react';

interface AuthLayoutProps {
    children: ReactNode;
    title: string;
    description?: string;
    icon?: ReactNode;
    status?: string;
    statusType?: 'success' | 'error';
}
function hexToAdjustedRgba(hex, opacity = 1, adjust = 0) {
    hex = hex.replace("#", "");
    let r = parseInt(hex.slice(0, 2), 16);
    let g = parseInt(hex.slice(2, 4), 16);
    let b = parseInt(hex.slice(4, 6), 16);
    const clamp = (v) => Math.max(-1, Math.min(1, v));
    const getF = (ch) =>
        typeof adjust === "number" ? clamp(adjust) : clamp(adjust[ch] ?? 0);
    const adj = (c, f) =>
        f < 0 ? Math.floor(c * (1 + f)) : Math.floor(c + (255 - c) * f);
    const rr = adj(r, getF("r"));
    const gg = adj(g, getF("g"));
    const bb = adj(b, getF("b"));
    return opacity === 1
        ? `#${rr.toString(16).padStart(2, "0")}${gg
            .toString(16)
            .padStart(2, "0")}${bb.toString(16).padStart(2, "0")}`.toUpperCase()
        : `rgba(${rr}, ${gg}, ${bb}, ${opacity})`;
}

export default function AuthLayout({
    children,
    title,
    description,
    icon,
    status,
    statusType = 'success',
}: AuthLayoutProps) {
    useFavicon();
    const { t, i18n } = useTranslation();
    const [mounted, setMounted] = useState(false);
    const { logoLight, logoDark, themeColor, customColor } = useBrand();
    const { appearance } = useAppearance();

    const currentLogo = appearance === 'dark' ? logoLight : logoDark;
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const globalSettings = (window as any).page?.props?.globalSettings;
    console.log('globalSettings',globalSettings);
    
    const isDemo = globalSettings?.is_demo || false;
    // const userLanguage = globalSettings?.defaultLanguage || 'en';
    const userLanguage = (usePage().props as any).userLanguage;
    // alert(userLanguage);

    useEffect(() => {
        setMounted(true);

        // Set default language from global settings
        if (userLanguage && i18n.language !== userLanguage) {
            i18n.changeLanguage(userLanguage);
        }
    }, [userLanguage]);

    // RTL Support for auth pages
    // useEffect(() => {
    //     const globalSettings = (window as any).page?.props?.globalSettings;
    //     const isDemo = globalSettings?.is_demo || false;
    //     let storedPosition = 'left';

    //     if (isDemo) {
    //         // In demo mode, use cookies
    //         const getCookie = (name: string): string | null => {
    //             if (typeof document === 'undefined') return null;
    //             const value = `; ${document.cookie}`;
    //             const parts = value.split(`; ${name}=`);
    //             if (parts.length === 2) {
    //                 const cookieValue = parts.pop()?.split(';').shift();
    //                 return cookieValue ? decodeURIComponent(cookieValue) : null;
    //             }
    //             return null;
    //         };
    //         const stored = getCookie('layoutPosition');
    //         if (stored === 'left' || stored === 'right') {
    //             storedPosition = stored;
    //         }
    //     } else {
    //         // In normal mode, get from database via globalSettings
    //         const stored = globalSettings?.layoutDirection;
    //         if (stored === 'left' || stored === 'right') {
    //             storedPosition = stored;
    //         }
    //     }

    //     const dir = storedPosition === 'right' ? 'rtl' : 'ltr';
    //     document.documentElement.dir = dir;
    //     document.documentElement.setAttribute('dir', dir);

    //     // Check if it was actually set
    //     setTimeout(() => {
    //         const actualDir = document.documentElement.getAttribute('dir');
    //         if (actualDir !== dir) {
    //             document.documentElement.dir = dir;
    //             document.documentElement.setAttribute('dir', dir);
    //         }
    //     }, 1);
    // }, []);

    // RTL Support for auth pages - Apply immediately and persist
    const applyRTLDirection = React.useCallback(() => {
        const isDemo = globalSettings?.is_demo || false;
        const currentLang = userLanguage || globalSettings?.defaultLanguage;
        const isRTLLanguage = ['ar', 'he'].includes(currentLang);
        let dir = 'ltr';

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

        // Check RTL setting from cookies/globalSettings
        const layoutDirection = isDemo ? getCookie('layoutPosition') : globalSettings?.layoutDirection;
        const isRTLSetting = layoutDirection === 'right';

        // Apply RTL if: 1) Language is ar/he OR 2) RTL setting is enabled
        if (isRTLLanguage || isRTLSetting) {
            dir = 'rtl';
        }

        // Apply direction immediately
        document.documentElement.dir = dir;
        document.documentElement.setAttribute('dir', dir);
        document.body.dir = dir;

        return dir;
    }, [userLanguage, globalSettings?.defaultLanguage, globalSettings?.is_demo, globalSettings?.layoutDirection]);

    // Apply RTL on mount and when dependencies change
    React.useLayoutEffect(() => {
        const direction = applyRTLDirection();

        // Ensure direction persists after any DOM changes
        const observer = new MutationObserver(() => {
            if (document.documentElement.dir !== direction) {
                document.documentElement.dir = direction;
                document.documentElement.setAttribute('dir', direction);
            }
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['dir']
        });

        // return () => observer.disconnect();
        return () => {
            observer.disconnect();
            // Reset to LTR when leaving auth layout            
            document.documentElement.dir = 'ltr';
            document.documentElement.setAttribute('dir', 'ltr');
            document.body.dir = 'ltr';
        };
    }, [applyRTLDirection]);

    return (
        <div className="min-h-screen bg-gray-50 relative overflow-hidden">
            <Head title={title} />

            {/* Enhanced Background Design */}
            <div className="absolute inset-0">
                {/* Base Gradient */}
                <div className="absolute inset-0 light:bg-gradient-to-br from-slate-50 via-gray-50 to-stone-100"></div>
                
                {/* Elegant Pattern Overlay */}
                <div className="absolute inset-0 opacity-70" style={{
                    backgroundImage: `radial-gradient(circle at 30% 70%, ${primaryColor} 1px, transparent 1px)`,
                    backgroundSize: '80px 80px'
                }}></div>
            </div>
            
            {/* Language Switcher */}
            <div className="absolute top-6 right-6 z-10 md:block hidden">
                <LanguageSwitcher />
            </div>

            <div className="flex items-center justify-center min-h-screen p-6">
                <div className="w-full max-w-md">
                    {/* Logo */}
                    <div className="text-center mb-8">
                        <div className="relative lg:inline-block pb-2 lg:px-6">
                            {currentLogo ? (
                                <img src={getImagePath(currentLogo)} alt="Logo" className="w-auto mx-auto" />
                            ) : (
                                <CreditCard className="h-8 w-8 mx-auto" style={{ color: primaryColor }} />
                            )}
                        </div>
                    </div>

                    {/* Main Card */}
                    <div className="relative">
                        {/* Corner accents */}
                        <div className="absolute -top-3 -left-3 w-6 h-6 border-l-2 border-t-2 rounded-tl-md" style={{ borderColor: primaryColor }}></div>
                        <div className="absolute -bottom-3 -right-3 w-6 h-6 border-r-2 border-b-2 rounded-br-md" style={{ borderColor: primaryColor }}></div>

                        <div className="bg-white border border-gray-200 rounded-lg lg:p-8 p-4 lg:pt-5 shadow-sm">
                            {/* Header */}
                            <div className="text-center mb-4">
                                {icon && (
                                    <div
                                        className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full"
                                        style={{ backgroundColor: `${primaryColor}20` }}
                                    >
                                        {icon}
                                    </div>
                                )}
                                <h1 className="text-2xl font-semibold text-gray-900 mb-1.5 tracking-wide">{title}</h1>
                                <div className="w-12 h-px mx-auto mb-2.5" style={{ backgroundColor: primaryColor }}></div>
                                {description && (
                                    <p className="text-gray-700 text-sm">{description}</p>
                                )}
                            </div>

                            {status && (
                                <div className={`mb-6 text-center text-sm font-medium ${statusType === 'success'
                                    ? 'text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800/30'
                                    : 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800/30'
                                    } p-3 rounded-lg border`}>
                                    {status}
                                </div>
                            )}

                            {children}
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="text-center mt-6">
                            <div className="inline-flex items-center space-x-2 bg-white backdrop-blur-sm rounded-md px-4 py-2 border border-gray-200">
                                <p className="text-sm text-gray-500">{globalSettings?.footerText || '© 2026 HRM SaaS'}</p>
                            </div>
                    </div>
                </div>
            </div>
            <CookieConsentBanner />
        </div>           

    );
}