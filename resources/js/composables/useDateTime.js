import { format, parse, parseISO } from 'date-fns'

const appDateFormat = import.meta.env.VITE_DATE_FORMAT || "d-M-Y";
const appTimeFormat = import.meta.env.VITE_TIME_FORMAT || "hh:mm:ss a";
const appDateTimeFormat = import.meta.env.VITE_DATE_TIME_FORMAT || "d-M-Y h:i:s a";

function convertLaravelToDateFns(phpFormat) {
    const map = {
        Y: "yyyy",
        y: "yy",
        F: "MMMM",
        M: "MMM",
        m: "MM",
        n: "M",
        d: "dd",
        j: "d",
        H: "HH",
        h: "hh",
        g: "h",
        G: "H",
        i: "mm",
        s: "ss",
        a: "aa",
        A: "aa",
    };

    return phpFormat.replace(/Y|y|F|M|m|n|d|j|H|h|g|G|i|s|a|A/g, match => map[match] || match);
}

function convertLaravelTimeToDateFns(phpTimeFormat) {
    const map = {
        g: "h",
        G: "H",
        h: "hh",
        H: "HH",
        i: "mm",
        s: "ss",
        a: "aa",
        A: "aa",
    };

    return phpTimeFormat.replace(/g|G|h|H|i|s|a|A/g, match => map[match] || match);
}

function parseDateTime(datetime) {
    if (!datetime) {
        return new Date()
    }

    if (datetime instanceof Date) {
        return datetime
    }

    if (typeof datetime === 'string') {
        const normalized = datetime.trim().replace(' ', 'T')

        // Time only: HH:mm or HH:mm:ss
        if (/^\d{2}:\d{2}(:\d{2})?$/.test(normalized)) {
            return parse(
                normalized,
                normalized.length === 5 ? 'HH:mm' : 'HH:mm:ss',
                new Date()
            )
        }

        // ISO datetime/date
        if (/^\d{4}-\d{2}-\d{2}/.test(normalized)) {
            return parseISO(normalized)
        }

        // Fallback
        const parsed = new Date(normalized)

        if (!isNaN(parsed.getTime())) {
            return parsed
        }
    }

    return new Date(datetime)
}

export const formatDate = (date, formatStr = null) => {
    try {
        formatStr = formatStr || appDateFormat;
        const jsFormat = convertLaravelToDateFns(formatStr);
        const dateToFormat = parseDateTime(date);
        return format(dateToFormat, jsFormat);
    } catch (e) {
        console.warn("Invalid date:", date, e);
        return "Invalid date";
    }
};

export const formatTime = (time, formatStr = null) => {
    try {
        formatStr = formatStr || appTimeFormat

        const jsFormat = convertLaravelTimeToDateFns(formatStr)

        const timeToFormat = parseDateTime(time)

        return format(timeToFormat, jsFormat)
    } catch (e) {
        console.warn('Invalid time:', time, e)
        return 'Invalid time'
    }
}

export const formatDateTime = (datetime, formatStr = null) => {
    try {
        formatStr = formatStr || appDateTimeFormat;
        const jsFormat = convertLaravelToDateFns(formatStr);
        const dateToFormat = parseDateTime(datetime);
        return format(dateToFormat, jsFormat);
    } catch (e) {
        console.warn("Invalid datetime:", datetime, e);
        return "Invalid datetime";
    }
};

export const formatUnixTime = (timestamp) => {
    if (!timestamp) return 'N/A'
    return new Date(timestamp * 1000).toLocaleString()
}

export const formatUnixDateTimeWithHumanReadable = (timestamp) => {
    const now = Math.floor(Date.now() / 1000)
    const diff = now - timestamp

    if (diff > 12 * 3600) {
        return formatDateTime(new Date(timestamp * 1000))
    }

    const hours = Math.floor(diff / 3600)
    const minutes = Math.floor((diff % 3600) / 60)
    const seconds = diff % 60

    let parts = []
    if (hours > 0) parts.push(`${hours}h`)
    if (minutes > 0) parts.push(`${minutes}m`)
    if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`)

    return parts.join(' ') + ' ago'
}

export const formatDateForInput = (date) => {
    try {
        if (!date) return ''

        const parsedDate = parseDateTime(date)

        if (isNaN(parsedDate.getTime())) return ''

        return format(parsedDate, 'yyyy-MM-dd')
    } catch (e) {
        console.warn('Invalid input date:', date, e)
        return ''
    }
}

export const formatDateTimeForInput = (datetime) => {
    try {
        if (!datetime) return ''

        const date = new Date(datetime)

        if (isNaN(date.getTime())) return ''

        return format(date, "yyyy-MM-dd'T'HH:mm")
    } catch (e) {
        console.warn("Invalid datetime:", datetime, e)
        return ''
    }
}
