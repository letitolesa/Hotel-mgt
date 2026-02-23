import { update } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    return (
        <AuthLayout
            title="Reset password"
            description="Please enter your new password below"
        >
            <Head title="Reset password" />

            <Form
                {...update.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email" className="text-[#15361c]">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={email}
                                className="mt-1 block w-full border-[#307051]"
                                readOnly
                            />
                            <InputError
                                message={errors.email}
                                className="mt-2 text-red-600"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password" className="text-[#15361c]">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full border-[#307051]"
                                autoFocus
                                placeholder="Password"
                            />
                            <InputError message={errors.password} className="mt-2 text-red-600" />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation" className="text-[#15361c]">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full border-[#307051]"
                                placeholder="Confirm password"
                            />
                            <InputError
                                message={errors.password_confirmation}
                                className="mt-2 text-red-600"
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full bg-[#307051] text-white hover:bg-[#15361c]"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            Reset password
                        </Button>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}