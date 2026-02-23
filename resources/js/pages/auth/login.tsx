import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: LoginProps) {
    return (
        <AuthLayout
            title="Welcome Back"
            description="Sign in to your account here."
            className="font-playfair" // Add Playfair Display font class
        >
            <Head title="Sign In - Lemif International Hotel and Resort" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label 
                                    htmlFor="email" 
                                    className="text-sm font-medium text-[#15361c] uppercase tracking-wider"
                                >
                                    Email Address
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="youremail@lemifinternationalhotel.com"
                                    className="border-[#307051]/30 focus:border-[#307051] focus:ring-[#307051] placeholder:text-[#307051]/50"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label 
                                        htmlFor="password" 
                                        className="text-sm font-medium text-[#15361c] uppercase tracking-wider"
                                    >
                                        Password
                                    </Label>
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm text-[#307051] hover:text-[#15361c] transition-colors duration-200"
                                            tabIndex={5}
                                        >
                                            Forgot password?
                                        </TextLink>
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="••••••••"
                                    className="border-[#307051]/30 focus:border-[#307051] focus:ring-[#307051]"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                        className="border-[#307051] text-[#307051] focus:ring-[#307051]"
                                    />
                                    <Label 
                                        htmlFor="remember" 
                                        className="text-sm text-[#15361c]"
                                    >
                                        Remember me
                                    </Label>
                                </div>
                            </div>

                            <Button
                                type="submit"
                                className="w-full bg-[#307051] hover:bg-[#15361c] text-white font-medium py-6 text-lg transition-all duration-300 transform hover:scale-[1.02]"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner className="mr-2" />}
                                Sign In
                            </Button>

                            {/* Decorative element */}
                            <div className="relative">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-[#307051]/20"></div>
                                </div>
                                <div className="relative flex justify-center text-xs uppercase">
                                    <span className="bg-white px-2 text-[#307051] font-playfair">
                                        Lemif International Hotel and Resort
                                    </span>
                                </div>
                            </div>

                       
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-4 text-center text-sm font-medium text-[#307051] bg-[#307051]/10 py-2 px-4 rounded-lg">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}