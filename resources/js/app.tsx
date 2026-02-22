import '../css/app.css';
import '../css/dark-mode.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { lazy, Suspense } from 'react';
import { LayoutProvider } from './contexts/LayoutContext';
import { SidebarProvider } from './contexts/SidebarContext';
import { BrandProvider } from './contexts/BrandContext';
import { ModalStackProvider } from './contexts/ModalStackContext';
import { initializeTheme } from './hooks/use-appearance';
import { CustomToast } from './components/custom-toast';
import { initializeGlobalSettings } from './utils/globalSettings';
import { initPerformanceMonitoring, lazyLoadImages } from './utils/performance';
import './i18n'; // Import i18n configuration
import './utils/axios-config'; // Import axios configuration
import i18n from './i18n'; // Import i18n configuration

// Initialize performance monitoring
initPerformanceMonitoring();

// Initialize lazy loading of images when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    lazyLoadImages();
});

// Add event listener for theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    // Re-apply theme when system preference changes
    const savedTheme = localStorage.getItem('themeSettings');
    if (savedTheme) {
        const themeSettings = JSON.parse(savedTheme);
        if (themeSettings.appearance === 'system') {
            initializeTheme();
        }
    }
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Make page data globally available for axios interceptor
        try {
            (window as any).page = props.initialPage;
        } catch (e) {
            console.warn('Could not set global page data:', e);
        }

        // Set demo mode globally
        try {
            (window as any).isDemo = props.initialPage.props?.is_demo || false;
        } catch (e) {
            // Ignore errors
        }

        // Sync language from database when not in demo mode
        const syncLanguage = (pageProps: any) => {
            const userLanguage = pageProps?.props?.userLanguage;
            const layoutDirection = pageProps?.props?.globalSettings?.layoutDirection;
            if (userLanguage) {
                // Always keep dir=ltr for proper sidebar functionality
                // RTL content will be handled by CSS and layoutDirection setting
                document.documentElement.dir = 'ltr';
                document.documentElement.setAttribute('dir', 'ltr');

                if (i18n.language !== userLanguage) {
                    i18n.changeLanguage(userLanguage);
                }
            }
        };

        // Initial language sync
        syncLanguage(props.initialPage);

        // Initialize global settings from shared data
        const globalSettings = props.initialPage.props.globalSettings || {};
        if (Object.keys(globalSettings).length > 0) {
            initializeGlobalSettings(globalSettings);
        }

        // Create a memoized render function to prevent unnecessary re-renders
        const renderApp = (appProps: any) => {
            const currentGlobalSettings = appProps.initialPage.props.globalSettings || {};
            const user = appProps.initialPage.props.auth?.user;

            return (
                <ModalStackProvider>
                    <LayoutProvider>
                        <SidebarProvider>
                            <BrandProvider globalSettings={currentGlobalSettings} user={user}>
                                <Suspense fallback={<div className="flex h-screen w-full items-center justify-center">Loading...</div>}>
                                    <App {...appProps} />
                                </Suspense>
                                <CustomToast />
                            </BrandProvider>
                        </SidebarProvider>
                    </LayoutProvider>
                </ModalStackProvider>
            );
        };

        // Initial render
        root.render(renderApp(props));

        // Update global page data on navigation and re-render with new settings
        router.on('navigate', (event) => {
            try {
                (window as any).page = event.detail.page;
                // Re-render with updated props including globalSettings
                root.render(renderApp({ initialPage: event.detail.page }));

                // Force dark mode check on navigation
                const savedTheme = localStorage.getItem('themeSettings');
                if (savedTheme) {
                    const themeSettings = JSON.parse(savedTheme);
                    const isDark = themeSettings.appearance === 'dark' ||
                        (themeSettings.appearance === 'system' &&
                            window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.classList.toggle('dark', isDark);
                    document.body.classList.toggle('dark', isDark);
                }
            } catch (e) {
                console.error('Navigation error:', e);
            }
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Direction initialization is now handled by LayoutProvider and landing page
