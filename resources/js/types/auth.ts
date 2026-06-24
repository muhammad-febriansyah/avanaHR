export type User = {
    id: number;
    tenant_id: number | null;
    employee_id: number | null;
    name: string;
    email: string;
    avatar?: string;
    avatar_url: string;
    status?: string;
    is_super_admin?: boolean;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: string[];
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
