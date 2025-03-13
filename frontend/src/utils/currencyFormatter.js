// src/utils/currencyFormatter.js
export const formatCurrency = (amount, mno) => {
    let currencySymbol;
    switch (true) {
        case /uganda/i.test(mno):
            currencySymbol = "UGX";
            break;
        case /ghana/i.test(mno):
            currencySymbol = "GHS";
            break;
        default:
            currencySymbol = "USD"; // Default currency
    }
  
    return `${new Intl.NumberFormat().format(amount)} ${currencySymbol}`;
};