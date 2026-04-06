export interface Flash {
    success: string | null;
    error: string | null;
    warning: string | null;
}

export interface AppInfo {
    name: string;
    environment: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

export interface Filters {
    search?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    [key: string]: string | undefined;
}
