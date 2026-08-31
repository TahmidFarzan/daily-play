export const titleFormat = (text) => {
    if (!text) return "";

    return String(text)
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/[_\-]+/g, " ")
        .trim()
        .toLowerCase()
        .replace(/^\w/, (c) => c.toUpperCase());
};

export const replaceAllOccurrences = (text, search, replace) => {
    if (!text || !search) return text;
    const escapedSearch = search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(escapedSearch, 'g');

    return text.replace(regex, replace);
};

export const capitalize = (str) => {
    if (!str) return 'N/A'
    return str.charAt(0).toUpperCase() + str.slice(1)
}


export const loweriseText = (value) => {
    return String(value ?? '')
        .trim()
        .toLowerCase()
}
