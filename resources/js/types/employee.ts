export type StatusOption = {
    value: string;
    label: string;
};

export type NamedRef = {
    id: number;
    name: string;
} | null;

export type EmployeeRow = {
    id: number;
    employee_no: string;
    first_name: string;
    last_name: string | null;
    email: string | null;
    status: string;
    join_date: string | null;
    current_employment: {
        position: NamedRef;
        department: NamedRef;
    } | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type EmployeeFilters = {
    search?: string;
    status?: string;
    sort?: string;
    dir?: 'asc' | 'desc';
    per_page?: number | string;
};

export type BankAccount = {
    id: number;
    bank_code: string;
    account_no: string;
    account_name: string;
    is_primary: boolean;
};

export type Dependent = {
    id: number;
    name: string;
    relationship: string;
    birth_date: string | null;
};

export type EmployeeFull = {
    id: number;
    employee_no: string;
    first_name: string;
    last_name: string | null;
    gender: string | null;
    birth_date: string | null;
    birth_place: string | null;
    religion: string | null;
    marital_status: string | null;
    nik_ktp: string | null;
    npwp: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    status: string;
    join_date: string | null;
    current_employment: {
        position: NamedRef;
        department: NamedRef;
        branch: NamedRef;
        job_grade: NamedRef;
    } | null;
    bank_accounts: BankAccount[];
    dependents: Dependent[];
};
