// resources/js/Hooks/usePermission.js
import { usePage } from '@inertiajs/react';

export function usePermission() {
    const { auth } = usePage().props;

    const hasPermission = (permission) => auth.user.permissions.includes(permission);
    const hasRole = (role) => auth.user.roles.includes(role);

    return { hasPermission, hasRole };
}