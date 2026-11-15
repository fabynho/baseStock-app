// Tipos dos props enviados pelos API Resources do BaseStock.
// Decimais (m²) chegam como string para não perder precisão; formate com lib/format.ts.

/** Coleção paginada: FloorResource::collection($paginator) (sem ->resolve()). */
export interface Paginated<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
}

export interface Seller {
    id: number;
    name: string;
    is_active: boolean;
}

export interface Deliverer {
    id: number;
    name: string;
    vehicle_info: string | null;
    is_active: boolean;
}

export interface Floor {
    id: number;
    code: string;
    reference: string | null;
    description: string;
    brand: string | null;
    m2_per_box: string | null;
    physical_stock: string;
    committed_stock: string;
    available_stock: string;
    min_stock_alert: string | null;
    below_minimum: boolean;
    is_active: boolean;
}
