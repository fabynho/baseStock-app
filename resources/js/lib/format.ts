/** Formata m² vindo do backend como string decimal ("1234.5000") → "1.234,5". */
export function formatM2(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return Number(value).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    });
}
