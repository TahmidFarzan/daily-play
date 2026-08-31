
export const extractModelName = (fullClassName) => {
    if (!fullClassName) return '';

    return fullClassName.split(/\\+/).pop();
};
