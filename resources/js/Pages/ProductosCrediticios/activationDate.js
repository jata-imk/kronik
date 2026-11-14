export const dateInTimeZone = (date, timeZone) => {
    const parts = new Intl.DateTimeFormat("en-US", {
        timeZone,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).formatToParts(date);
    const part = (type) => parts.find((item) => item.type === type)?.value;

    return `${part("year")}-${part("month")}-${part("day")}`;
};

// Calendar selections are civil dates, independent of UTC serialization.
export const calendarDate = (value) => {
    if (typeof value === "string") return value;
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) return "";
    return [value.getFullYear(), String(value.getMonth() + 1).padStart(2, "0"), String(value.getDate()).padStart(2, "0")].join("-");
};
