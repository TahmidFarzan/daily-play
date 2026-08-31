export const itemListFilterParameters = (filterForm = {}) => {
    return Object.fromEntries(
        Object.entries(filterForm)
            .filter(([key, val]) =>
                key !== 'page' &&
                val !== null &&
                val !== undefined &&
                !(typeof val === 'string' && val.trim() === '')
            )
    )
}
