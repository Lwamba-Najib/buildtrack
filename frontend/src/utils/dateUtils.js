// src/utils/dateUtils.js

export const getOrdinal = (day) => {
    const suffix = ['th', 'st', 'nd', 'rd'];
    const value = day % 100;
    return day + (suffix[(value - 20) % 10] || suffix[value] || suffix[0]);
};
  
export const formatDateWithOrdinal = (dateString) => {
    const date = new Date(dateString);
    const month = date.toLocaleDateString('en-US', { month: 'long' });
    const day = getOrdinal(date.getDate());  // Get day with ordinal
    const year = date.getFullYear();
    return `${month} ${day}, ${year}`;
};  