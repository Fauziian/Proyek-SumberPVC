export interface User {
    id: number;
    nama: string;
    username: string;
    role: string;
    roles?: string[];
    is_active?: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
