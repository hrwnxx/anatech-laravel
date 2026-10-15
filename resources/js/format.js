// Small display helpers used on many pages.

export function peso(amount) {
    return '₱' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function shortDate(value) {
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

// "2026-07" -> "Jul 2026"
export function monthLabel(value) {
    const [year, month] = value.split('-');
    return new Date(year, month - 1).toLocaleDateString('en-PH', { year: 'numeric', month: 'short' });
}

// Today's date as YYYY-MM-DD, the format <input type="date"> uses.
export function today() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 10);
}
