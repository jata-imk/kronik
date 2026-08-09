export const parseDateOnly = (value) => {
    if (!value) return null;
    if (value instanceof Date) return value;

    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!match) return new Date(value);

    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
};

export const formatDateOnly = (value) => {
    if (!value) return null;

    const date = value instanceof Date ? value : parseDateOnly(value);
    if (!date || Number.isNaN(date.getTime())) return value;

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};
