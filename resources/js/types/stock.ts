// Tipos dos props enviados pelos API Resources do BaseStock.
// Decimais (m²) chegam como string para não perder precisão;

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

export interface PurchaseItem {
    id: number;
    floor_id: number;
    quantity_m2: string;
    floor?: Floor;
}

export interface Purchase {
    id: number;
    document_number: string;
    supplier_name: string;
    document_date: string | null; // YYYY-MM-DD
    received_at: string; // ISO 8601
    notes: string | null;
    creator?: { id: number; name: string };
    items?: PurchaseItem[];
}

export interface EnumOption {
    value: string;
    label: string;
}

export interface SaleItem {
    id: number;
    floor_id: number;
    quantity_m2: string;
    floor?: Floor;
}

export interface Sale {
    id: number;
    document_number: string;
    customer_name: string;
    delivery_type: EnumOption;
    status: EnumOption; // pending_delivery | delivered | canceled
    sold_at: string; // ISO 8601
    delivered_at: string | null;
    notes: string | null;
    seller?: Seller;
    deliverer?: Deliverer | null;
    creator?: { id: number; name: string };
    items?: SaleItem[];
    can_confirm: boolean;
    can_cancel: boolean;
    can_return: boolean;
}

export interface StockMovement {
    id: number;
    type: EnumOption;
    quantity_m2: string; // com sinal apenas em manual_adjustment
    physical_balance_after: string;
    committed_balance_after: string;
    document_number: string | null;
    sale_id: number | null;
    purchase_id: number | null;
    reason: string | null;
    created_at: string; // ISO 8601
    floor?: { id: number; code: string; description: string; reference: string | null; };
    user?: { id: number; name: string };
}

export interface FloorOption {
    id: number;
    code: string;
    reference: string | null;
    description: string;
    brand: string | null;
}

export interface ManagedUser {
    id: number;
    name: string;
    email: string;
    role: EnumOption; // admin | user
    is_active: boolean;
    is_self: boolean;
}

export interface ReturnableItem {
    floor: { id: number; code: string; description: string; reference: string | null };
    sold: string;
    returned: string;
    returnable: string;
}

export interface ReturnableSale {
    id: number;
    document_number: string;
    customer_name: string;
    delivered_at: string | null;
    items: ReturnableItem[];
}

export interface CustomerReturnItem {
    id: number;
    floor_id: number;
    quantity_m2: string;
    floor: { id: number; code: string; description: string; reference: string | null } | null;
}

export interface CustomerReturn {
    id: number;
    document_number: string;
    sale_id: number | null;
    customer_name: string | null;
    reason: string;
    returned_at: string; // ISO 8601
    creator?: { id: number; name: string };
    items?: CustomerReturnItem[];
}

