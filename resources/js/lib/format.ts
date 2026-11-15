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

/** "2026-11-10" (data pura) → "10/11/2026", sem conversão de fuso. */
export function formatDate(date: string | null | undefined): string {
    if (!date) {
        return '—';
    }

    const [year, month, day] = date.slice(0, 10).split('-');

    return `${day}/${month}/${year}`;
}

/** ISO 8601 → "10/11/26 14:30". */
export function formatDateTime(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

/** Interpreta o que o operador digitou: "12,5" e "1.234,5" (pt-BR) ou "12.5". Retorna 0 se inválido. */
export function parseDecimal(value: string | number | null | undefined): number {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    let text = String(value).trim();

    if (text.includes(',')) {
        text = text.replace(/\./g, '').replace(',', '.');
    }

    const number = Number(text);

    return Number.isFinite(number) ? number : 0;
}

/** Arredonda para 4 casas (precisão do banco) e devolve como texto com ponto decimal. */
export function toDecimalInput(value: number): string {
    return String(Math.round(value * 10000) / 10000);
}
