import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function UserIndex() {
    return (
        <AppLayout>
            <Head title="Users" />
            <div className="p-6">
                <h1 className="text-2xl font-semibold">User Management</h1>
                <p className="mt-4">This is where you manage your users.</p>
            </div>
        </AppLayout>
    );
}