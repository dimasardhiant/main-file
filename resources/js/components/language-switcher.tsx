import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import ReactCountryFlag from 'react-country-flag';
import { Button } from '@/components/ui/button';
import { CreateLanguageModal } from './create-language-modal';
import { useLayout } from '@/contexts/LayoutContext';
import { isDemoMode, setCookie, isSaaS, getCookie } from '@/utils/helpers';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Globe, Plus, Settings } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import { hasRole } from '@/utils/authorization';


interface Language {
    code: string;
    name: string;
    countryCode: string;
    enabled?: boolean;
}

export function LanguageSwitcher() {
    const { i18n, t } = useTranslation();
    const { auth, globalSettings, userLanguage } = usePage().props as any;
    const { setPosition } = useLayout();
    const { updatePosition } = useLayout();
    // Initialize with user's language immediately

    const [currentLanguage, setCurrentLanguage] = useState<Language | null>(() => {
        const languages = (globalSettings?.availableLanguages || []).filter((l: any) => l.enabled !== false);
        if (userLanguage) {
            const lang = languages.find((l: Language) => l.code === userLanguage);
            if (lang) {
                i18n.changeLanguage(userLanguage);
                return lang;
            }
        }

        return languages[0] || null;
    });
    const [showCreateModal, setShowCreateModal] = useState(false);

    const availableLanguages = globalSettings?.availableLanguages || [];
    const isSaas = globalSettings?.is_saas || false;
    const isAuthenticated = !!auth?.user;
    const userRoles = auth?.user?.roles?.map((role: any) => role.name) || [];
    const isSuperAdmin = isAuthenticated && hasRole('superadmin', userRoles);
    const isCompany = isAuthenticated && hasRole('company', userRoles);


    // Determine if user can manage languages based on SaaS mode
    const canManageLanguages = isSaas ? isSuperAdmin : isCompany;
    useEffect(() => {
        const languages = (availableLanguages || []).filter((l: any) => l.enabled !== false);

        if (userLanguage && i18n.language !== userLanguage) {
            i18n.changeLanguage(userLanguage, () => {
                const lang = languages.find((l: Language) => l.code === userLanguage) || languages[0];
                setCurrentLanguage(lang);
            });
        } else {
            const lang = languages.find((l: Language) => l.code === i18n.language) || languages[0];
            setCurrentLanguage(lang);
        }
    }, [availableLanguages, userLanguage]);

    // Separate effect to update current language when i18n changes
    useEffect(() => {
        const languages = (availableLanguages || []).filter((l: any) => l.enabled !== false);
        const lang = languages.find((l: Language) => l.code === i18n.language) || languages[0];
        setCurrentLanguage(lang);
    }, [i18n.language, availableLanguages]);

    // const handleLanguageChange = (languageCode: string) => {
    //     const lang = availableLanguages.find((l: Language) => l.code === languageCode);
    //     if (lang) {
    //         setCurrentLanguage(lang);
    //         i18n.changeLanguage(languageCode);

    //         // RTL language detection
    //         const rtlLanguages = ['ar', 'he'];
    //         const isRtl = rtlLanguages.includes(languageCode);
    //         const newDirection = isRtl ? 'right' : 'left';

    //         // Always keep document direction as LTR to prevent sidebar issues
    //         document.documentElement.dir = 'ltr';
    //         document.documentElement.setAttribute('dir', 'ltr');

    //         // Update layout context
    //         setPosition(newDirection);

    //         // Save to backend or cookies
    //         if (isAuthenticated && !isDemoMode()) {
    //             // Save language change for authenticated non-demo users
    //             router.post(route('languages.change'), {
    //                 language: languageCode
    //             }, {
    //                 preserveScroll: true,
    //                 onSuccess: () => {
    //                     // After language is saved, update layoutDirection if RTL
    //                     if (isRtl || (!isRtl && globalSettings?.layoutDirection === 'right')) {
    //                         router.post(route('settings.brand.update'), {
    //                             settings: {
    //                                 layoutDirection: newDirection
    //                             }
    //                         }, {
    //                             preserveScroll: true,
    //                             onError: (errors) => {
    //                                 console.error('Failed to update layout direction:', errors);
    //                             }
    //                         });
    //                     }
    //                 },
    //                 onError: (errors) => {
    //                     console.error('Failed to change language:', errors);
    //                 }
    //             });
    //         } else {
    //             // For demo mode or non-authenticated users, save to cookies
    //             setCookie('app_language', languageCode);
    //             setCookie('layoutDirection', newDirection);
    //         }
    //     }
    // };


    // RTL languages list


    const rtlLanguages = ['ar', 'he'];

    const handleLanguageChange = async (languageCode: string) => {
        const lang = availableLanguages.find((l: Language) => l.code === languageCode);
        if (lang) {
            setCurrentLanguage(lang);
            try {
                await i18n.changeLanguage(languageCode);

                const isRtl = rtlLanguages.includes(languageCode);
                let newDirection;

                if (isDemoMode()) {
                    // In demo mode, set direction to right for RTL languages, otherwise preserve current
                    newDirection = isRtl ? 'right' : (getCookie('layoutPosition') || 'left');
                } else {
                    // Get from database via globalSettings
                    newDirection = isRtl ? 'right' : (globalSettings?.layoutDirection || 'left');
                }

                document.documentElement.dir = 'ltr';
                document.documentElement.setAttribute('dir', 'ltr');

                updatePosition(newDirection as 'left' | 'right');

                // Save layoutDirection to database/cookies when RTL language is selected
                if (isAuthenticated && !isDemoMode()) {
                    // Save language change for authenticated non-demo users
                    router.post(route('languages.change'), {
                        language: languageCode
                    }, {
                        preserveScroll: true,
                        onSuccess: () => {
                            // After language is saved, update layoutDirection if RTL
                            if (isRtl || (!isRtl && globalSettings?.layoutDirection === 'right')) {
                                router.post(route('settings.brand.update'), {
                                    settings: {
                                        layoutDirection: newDirection
                                    }
                                }, {
                                    preserveScroll: true,
                                    onError: (errors) => {
                                        console.error('Failed to update layout direction:', errors);
                                    }
                                });
                            }
                        },
                        onError: (errors) => {
                            console.error('Failed to change language:', errors);
                        }
                    });
                } else {
                    // For demo mode or non-authenticated users, save to cookies
                    // setCookie('layoutDirection', newDirection);
                    setCookie('app_language', languageCode);
                    setCookie('layoutPosition', newDirection);
                }

                window.dispatchEvent(new CustomEvent('languageChanged', {
                    detail: { language: languageCode, direction: newDirection }
                }));

                window.dispatchEvent(new Event('resize'));
            } catch (error) {
                console.error('Error changing language:', error);
            }
        }
    };
    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="flex items-center gap-2 rounded-md border shadow-sm bg-white">
                        <Globe className="h-4 w-4" />
                        {currentLanguage && (
                            <>
                                <span className="text-sm font-medium hidden md:inline-block">
                                    {currentLanguage.name}
                                </span>
                                <ReactCountryFlag
                                    countryCode={currentLanguage.countryCode}
                                    svg
                                    style={{ width: '1.2em', height: '1.2em' }}
                                />
                            </>
                        )}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent className="w-56" align="end" forceMount>
                    <DropdownMenuGroup>
                        <div className="max-h-48 overflow-y-auto">
                            {(availableLanguages || []).filter((language: any) => language.enabled !== false).map((language: Language) => (
                                <DropdownMenuItem
                                    key={language.code}
                                    onClick={() => handleLanguageChange(language.code)}
                                    className={`flex items-center gap-2 ${currentLanguage?.code === language.code ? 'bg-accent' : ''}`}
                                >
                                    <ReactCountryFlag
                                        countryCode={language.countryCode}
                                        svg
                                        style={{ width: '1.2em', height: '1.2em' }}
                                    />
                                    <span>{language.name}</span>
                                </DropdownMenuItem>
                            ))}
                        </div>
                    </DropdownMenuGroup>
                    {canManageLanguages && (
                        <>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => setShowCreateModal(true)}
                                className="justify-center text-primary font-semibold cursor-pointer"
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                {t('Create Language')}
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild className="justify-center text-primary font-semibold cursor-pointer">
                                <a href={route('manage-language')} rel="noopener noreferrer">
                                    <Settings className="h-4 w-4 mr-2" />
                                    {t('Manage Language')}
                                </a>
                            </DropdownMenuItem>
                        </>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
            <CreateLanguageModal
                open={showCreateModal}
                onOpenChange={setShowCreateModal}
                onSuccess={() => setShowCreateModal(false)}
            />
        </>
    );
} 