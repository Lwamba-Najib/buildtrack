/**
 * Capitalizes the first letter of every word in a string.
 * @param {string} str - The input string.
 * @returns {string} - The transformed string.
 */
const toUcwords = (str) => {
    if (!str) return "No description available";
    return str
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

/**
 * Converts a string to uppercase.
 * @param {string} str - The input string.
 * @returns {string} - The uppercase string.
 */
const toUppercase = (str) => {
    return str ? str.toUpperCase() : "";
};

/**
 * Converts a string to lowercase.
 * @param {string} str - The input string.
 * @returns {string} - The lowercase string.
 */
const toLowercase = (str) => {
    return str ? str.toLowerCase() : "";
};

/**
 * Trims whitespace and normalizes spaces in a string.
 * @param {string} str - The input string.
 * @returns {string} - The trimmed and normalized string.
 */
const normalizeWhitespace = (str) => {
    return str ? str.trim().replace(/\s+/g, " ") : "";
};

// Export all methods at once
export { toUcwords, toUppercase, toLowercase, normalizeWhitespace };
