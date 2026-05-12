// Respuesta paginada de Laravel Resource Collection
export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

// Respuesta de un solo recurso
export interface ResourceResponse<T> {
    data: T;
}

// Respuesta simple de mensaje
export interface MessageResponse {
    message: string;
}
