export const getApiErrorMessage = (
    error: any,
    fallback = "Error inesperado"
): string => {
    if (!error) return fallback;

    const data = error.response?.data;

    if (!data) return fallback;

    // Caso string directo
    if (typeof data === "string") return data;

    // Casos comunes en Symfony / APIs
    return (
        data.message ||
        data.error ||
        data.detail ||
        fallback
    );
};
