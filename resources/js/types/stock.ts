// Tipos dos props enviados pelos API Resources do BaseStock.
// Decimais (m²) chegam como string para não perder precisão; formate na exibição.

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