export type { Paginator, StatusOption } from '@/types/employee';

export type FeatureOption = { key: string; label: string };

export type TenantSubscription = {
    tier: string;
    status: string;
};

export type TenantRow = {
    id: number;
    name: string;
    slug: string;
    status: string;
    employees_count: number;
    created_at: string;
    subscription: TenantSubscription | null;
};

export type TenantFilters = {
    search?: string;
    status?: string;
    sort?: string;
    dir?: 'asc' | 'desc';
    per_page?: number | string;
};

export type TenantFullSubscription = {
    tier: string;
    status: string;
    starts_at: string | null;
    ends_at: string | null;
};

export type TenantFull = {
    id: number;
    name: string;
    slug: string;
    locale: string;
    timezone: string;
    currency: string;
    status: string;
    created_at: string;
    employees_count?: number;
    subscription: TenantFullSubscription | null;
};
