import { usePage } from '@inertiajs/react';

/**
 * Read the authenticated user's permissions shared from the backend
 * and check them. Items without a permission are always allowed.
 */
export function usePermissions() {
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];

    const can = (permission?: string): boolean =>
        !permission || permissions.includes(permission);

    return { permissions, can };
}
