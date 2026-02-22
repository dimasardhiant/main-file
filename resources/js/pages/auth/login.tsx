import { useForm, router, usePage } from '@inertiajs/react';
import { Mail, Lock } from 'lucide-react';
import { FormEventHandler, useState, useEffect } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';
import AuthButton from '@/components/auth/auth-button';
import Recaptcha from '@/components/recaptcha';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';
import { Button } from '@/components/ui/button';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
    recaptcha_token?: string;
};

interface Business {
    id: number;
    name: string;
    slug: string;
    business_type: string;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    demoBusinesses?: Business[];
}

export default function Login({ status, canResetPassword, demoBusinesses = [] }: LoginProps) {
    const { t } = useTranslation();
    const [recaptchaToken, setRecaptchaToken] = useState<string>('');
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const { props } = usePage();
    const isSaas = (props as any).globalSettings?.is_saas;
    const isDemo = (props as any).globalSettings?.is_demo;

    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        // Set default credentials if in demo mode
        if (isDemo) {
            setData({
                email: isSaas ? 'company@example.com' : 'company@example.com',
                password: 'password',
                remember: false
            });
        }
    }, [isDemo]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const formData = { ...data, recaptcha_token: recaptchaToken };
        post(route('login'), formData, {
            onFinish: () => reset('password'),
        });
    };

    // No longer needed as we're using router.post directly in the button handlers

    const openBusinessInNewTab = (businessId: number, slug: string, e: React.MouseEvent) => {
        // Prevent the default form submission
        e.preventDefault();
        e.stopPropagation();

        // Use the same URL structure as in vcard-builder/index.tsx
        const url = route('public.vcard.show.direct', slug);
        window.open(url, '_blank');
    };

    return (
        <AuthLayout
            title={t("Log in to your account")}
            description={t("Enter your credentials to access your account")}
            status={status}
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="mb-4">
                    <Label htmlFor="email" className="block text-sm font-medium text-gray-900">{t("Email address")}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="Enter your email"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 mt-2"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="mb-4">
                    <div className="flex justify-between items-center mb-2">
                        <Label htmlFor="password" className="block text-sm font-medium text-gray-900">{t("Password")}</Label>
                        {canResetPassword && (
                            <TextLink
                                href={route('password.request')}
                                className="text-sm no-underline hover:underline hover:underline-primary"
                                style={{ color: primaryColor }}
                                tabIndex={5}
                            >
                                {t("Forgot password?")}
                            </TextLink>
                        )}
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabIndex={2}
                        autoComplete="current-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Enter your password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="flex items-center !mt-4 !mb-5">
                    <Checkbox
                        id="remember"
                        name="remember"
                        checked={data.remember}
                        onClick={() => setData('remember', !data.remember)}
                        tabIndex={3}
                        className="w-[14px] h-[14px] border border-gray-300 rounded"
                    />
                    <Label htmlFor="remember" className="ml-2 text-sm text-gray-600">{t("Remember me")}</Label>
                </div>

                <Recaptcha
                    onVerify={setRecaptchaToken}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                <button
                    type="submit"
                    disabled={processing}
                    tabIndex={4}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02] disabled:opacity-50"
                    style={{ backgroundColor: primaryColor }}
                >
                    {processing ? t("Signing in...") : t("Login")}
                </button>

                {isSaas && (
                    <div className="text-center">
                        <p className="text-sm text-gray-500">
                            {t("Don't have an account?")}{' '}
                            <TextLink
                                href={route('register')}
                                className="font-medium hover:underline"
                                style={{ color: primaryColor }}
                                tabIndex={6}
                            >
                                {t("Sign up")}
                            </TextLink>
                        </p>
                    </div>
                )}

                {isDemo && (
                    <>
                        {/* Divider */}
                        <div className="my-5">
                            <div className="flex items-center">
                                <div className="flex-1 h-px bg-gray-200"></div>
                                <div className="w-2 h-2 rotate-45 mx-4" style={{ backgroundColor: primaryColor }}></div>
                                <div className="flex-1 h-px bg-gray-200"></div>
                            </div>
                        </div>

                        <div>
                            <h3 className="text-sm font-medium text-gray-900 tracking-wider mb-4 text-center">{t("Quick Access")}</h3>
                            <div className="grid grid-cols-2 gap-3">
                                {isSaas ? (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'superadmin@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login as Super Admin")}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'company@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login as Company")}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'maggie93@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login As HR")}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'qwaters@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login As Employee")}
                                        </button>
                                    </>
                                ) : (
                                    <>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'company@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login As Company")}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'hr@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login As HR")}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                router.post(route('login'), {
                                                    email: 'employee@example.com',
                                                    password: 'password',
                                                    remember: false,
                                                    recaptcha_token: recaptchaToken
                                                });
                                            }}
                                            className="col-span-2 py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02]"
                                            style={{ backgroundColor: primaryColor, borderColor: primaryColor }}
                                        >
                                            {t("Login As Employee")}
                                        </button>
                                    </>
                                )}
                            </div>
                        </div>
                    </>
                )}

                
            </form>
        </AuthLayout>
    );
}