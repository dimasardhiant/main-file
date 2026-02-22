import { useForm } from '@inertiajs/react';
import { Lock, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/auth-layout';
import AuthButton from '@/components/auth/auth-button';
import { useBrand } from '@/contexts/BrandContext';
import { THEME_COLORS } from '@/hooks/use-appearance';

interface ResetPasswordProps {
    token: string;
    email: string;
}

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { t } = useTranslation();
    const { themeColor, customColor } = useBrand();
    const primaryColor = themeColor === 'custom' ? customColor : THEME_COLORS[themeColor as keyof typeof THEME_COLORS];
    const { data, setData, post, processing, errors, reset } = useForm<Required<ResetPasswordForm>>({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title={t("Reset your password")}
            description={t("Please enter your new password below")}
            icon={<Lock className="h-7 w-7" style={{ color: primaryColor }} />}
        >
            <form onSubmit={submit} className="space-y-5">
                <div className="mb-4">
                    <Label htmlFor="email" className="block text-sm font-medium text-gray-900">{t("Email")}</Label>
                    <Input
                        id="email"
                        type="email"
                        readOnly
                        value={data.email}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-gray-50 mt-2"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="mb-4">
                    <Label htmlFor="password" className="block text-sm font-medium text-gray-900">{t("New Password")}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Enter new password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 mt-2"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="mb-4">
                    <Label htmlFor="password_confirmation" className="block text-sm font-medium text-gray-900">{t("Confirm Password")}</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabIndex={2}
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="Confirm new password"
                        className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 mt-2"
                        onFocus={(e) => e.target.style.borderColor = primaryColor}
                        onBlur={(e) => e.target.style.borderColor = 'rgb(209 213 219)'}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    tabIndex={3}
                    className="w-full text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02] disabled:opacity-50"
                    style={{ backgroundColor: primaryColor }}
                >
                    {processing ? t("Resetting...") : t("RESET PASSWORD")}
                </button>
            </form>
        </AuthLayout>
    );
}