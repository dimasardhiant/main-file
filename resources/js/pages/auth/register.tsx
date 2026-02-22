import { useForm, usePage } from '@inertiajs/react';
import { Mail, Lock, User } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

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

type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    terms: boolean;
    recaptcha_token?: string;
    plan_id?: string;
    referral_code?: string;
};

export default function Register({ referralCode, planId }: { referralCode?: string; planId?: string }) {
    const { t } = useTranslation();
    const { globalSettings } = usePage().props as any;
    const termsConditionsUrl = globalSettings?.termsConditionsUrl;
    const [recaptchaToken, setRecaptchaToken] = useState<string>('');
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        terms: false,
        plan_id: planId,
        referral_code: referralCode,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            data: { ...data, recaptcha_token: recaptchaToken },
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={t("Create your account")}
            description={t("Enter your details below to get started")}
        >
            <form className="space-y-5" onSubmit={submit}>
                <div className="mb-4">
                    <Label htmlFor="name" className="block text-sm font-medium text-gray-900">{t("Full name")}</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder={t("Enter your full name")}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 mt-2"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="mb-4">
                    <Label htmlFor="email" className="block text-sm font-medium text-gray-900">{t("Email address")}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        tabIndex={2}
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
                    <Label htmlFor="password" className="block text-sm font-medium text-gray-900">{t("Password")}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabIndex={3}
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Enter your password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 mt-2"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="mb-4">
                    <Label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-900">{t("Confirm password")}</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabIndex={4}
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="Confirm your password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 mt-2"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div className="flex items-center !mt-4 !mb-5">
                    <Checkbox
                        id="terms"
                        name="terms"
                        checked={data.terms}
                        onClick={() => setData('terms', !data.terms)}
                        tabIndex={5}
                        className="border border-gray-300 rounded"
                    />
                    <Label htmlFor="terms" className="ml-2 text-sm text-gray-600">
                        {t("I agree to the")}{' '}
                        <a 
                            href={termsConditionsUrl || route('home')} 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            style={{ color: primaryColor }} 
                            className="hover:underline"
                        >
                            {t("Terms and Conditions")}
                        </a>
                    </Label>
                </div>
                <InputError message={errors.terms} />

                <Recaptcha 
                    onVerify={setRecaptchaToken}
                    onExpired={() => setRecaptchaToken('')}
                    onError={() => setRecaptchaToken('')}
                />

                <button
                    type="submit"
                    disabled={processing}
                    tabIndex={6}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02] disabled:opacity-50"
                    style={{ backgroundColor: primaryColor }}
                >
                    {processing ? t("Creating account...") : t("Create Account")}
                </button>

                <div className="text-center">
                    <p className="text-sm text-gray-500">
                        {t("Already have an account?")}{' '}
                        <TextLink 
                            href={route('login')} 
                            className="font-medium hover:underline" 
                            style={{ color: primaryColor }}
                            tabIndex={7}
                        >
                            {t("Sign in")}
                        </TextLink>
                    </p>
                </div>
            </form>
        </AuthLayout>
    );
}