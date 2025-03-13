import { ref, onMounted } from "vue";
import axios from "@/axios"; // Adjust the path if necessary
// Helper function to retrieve token
const getToken = () => {
    const token = localStorage.getItem("token");
    if (!token) throw new Error("No token found");
    return token;
};

// Centralized error handling function
const handleError = (error, alertField = "error") => {
    alerts[alertField] = error.response?.data?.message || "An error occurred. Please try again later.";
    console.error("API Error:", error);
};
// Define a list of menu access keys
export const accessKeys = [
    //Others
    { key: "dashboard", name: "Dashboard", category: "Others", visibility: "public", availability: "public" },    
    { key: "trash", name: "Trash", category: "Others", visibility: "public", availability: "public" }, 
    // Role
    { key: "roleAdd", name: "Add Roles", category: "Role", visibility: "public", availability: "public" },
    { key: "roleList", name: "List Roles", category: "Role", visibility: "public", availability: "public" },
    { key: "roleEdit", name: "Edit Roles", category: "Role", visibility: "public", availability: "public" },
    { key: "roleDelete", name: "Delete Roles", category: "Role", visibility: "public", availability: "public" },
    { key: "roleBulkDelete",name: "Bulk Delete Roles",category: "Role",visibility: "public"},
    // User
    { key: "userAdd", name: "Add Users", category: "User", visibility: "public", availability: "public" },
    { key: "userList", name: "List Users", category: "User", visibility: "public", availability: "public" },
    { key: "userEdit", name: "Edit Users", category: "User", visibility: "public", availability: "public" },
    { key: "userDelete", name: "Delete Users", category: "User", visibility: "public", availability: "public" },
    { key: "userBulkDelete",name: "Bulk Delete Users",category: "User",visibility: "public", availability: "public"},
    { key: "userFilter", name: "Filter Users", category: "User", visibility: "public", availability: "public" },
    { key: "userExport", name: "Export Users", category: "User", visibility: "public", availability: "public" },
    { key: "userLockUnlock", name: "Lock/Unlock Users", category: "User", visibility: "public", availability: "public" },
    // Category
    { key: "categoryAdd", name: "Add Categories", category: "Category", visibility: "public", availability: "private" },
    { key: "categoryList", name: "List Categories", category: "Category", visibility: "public", availability: "private" },
    { key: "categoryEdit", name: "Edit Categories", category: "Category", visibility: "public", availability: "private" },
    { key: "categoryDelete",name: "Delete Categories",category: "Category",visibility: "public", availability: "private"},
    { key: "categoryBulkDelete",name: "Bulk Delete Categories",category: "Category",visibility: "public", availability: "private"},
    { key: "categoryFilter",name: "Filter Categories",category: "Category",visibility: "public", availability: "private"},
    { key: "categoryExport",name: "Export Categories",category: "Category",visibility: "public", availability: "private"},
    // Product 
    { key: "productAdd", name: "Add Products", category: "Product", visibility: "public", availability: "private" },
    { key: "productList", name: "List Products", category: "Product", visibility: "public", availability: "private" },
    { key: "productEdit", name: "Edit Products", category: "Product", visibility: "public", availability: "private" },
    { key: "productDelete",name: "Delete Products",category: "Product",visibility: "public", availability: "private"},
    { key: "productBulkDelete",name: "Bulk Delete products",category: "Product",visibility: "public", availability: "private"},
    { key: "productFilter",name: "Filter Product",category: "Product",visibility: "public", availability: "private"},
    { key: "productExport",name: "Export Product",category: "Product",visibility: "public", availability: "private"},
   
    // Brand
    { key: "brandAdd", name: "Add Brands", category: "Brand", visibility: "public", availability: "private" },
    { key: "brandList", name: "List Brands", category: "Brand", visibility: "public", availability: "private" },
    { key: "brandEdit", name: "Edit Brands", category: "Brand", visibility: "public", availability: "private" },
    { key: "brandDelete",name: "Delete Brands",category: "Brand",visibility: "public", availability: "private"},
    { key: "brandBulkDelete",name: "Bulk Delete Brands",category: "Brand",visibility: "public", availability: "private"},
    { key: "brandFilter",name: "Filter Brand",category: "Brand",visibility: "public", availability: "private"},
    { key: "brandExport",name: "Export Brand",category: "Brand",visibility: "public", availability: "private"},

    // Measurement
    { key: "measurementAdd", name: "Add Measurements", category: "Measurement", visibility: "public", availability: "private" },
    { key: "measurementList", name: "List Measurements", category: "Measurement", visibility: "public", availability: "private" },
    { key: "measurementEdit", name: "Edit Measurements", category: "Measurement", visibility: "public", availability: "private" },
    { key: "measurementDelete",name: "Delete Measurements",category: "Measurement",visibility: "public", availability: "private"},
    { key: "measurementBulkDelete",name: "Bulk Delete Measurements",category: "Measurement",visibility: "public", availability: "private"},
    { key: "measurementFilter",name: "Filter Measurement",category: "Measurement",visibility: "public", availability: "private"},
    { key: "measurementExport",name: "Export Measurement",category: "Measurement",visibility: "public", availability: "private"},

    // Supplier
    { key: "supplierAdd", name: "Add Suppliers", category: "Supplier", visibility: "public", availability: "private" },
    { key: "supplierList", name: "List Suppliers", category: "Supplier", visibility: "public", availability: "private" },
    { key: "supplierEdit", name: "Edit Suppliers", category: "Supplier", visibility: "public", availability: "private" },
    { key: "supplierDelete",name: "Delete Suppliers",category: "Supplier",visibility: "public", availability: "private"},
    { key: "supplierBulkDelete",name: "Bulk Delete Suppliers",category: "Supplier",visibility: "public", availability: "private"},
    { key: "supplierFilter",name: "Filter Supplier",category: "Supplier",visibility: "public", availability: "private"},
    { key: "supplierExport",name: "Export Supplier",category: "Supplier",visibility: "public", availability: "private"},

    // Stock
    { key: "stockAdd", name: "Add Stock", category: "Stock", visibility: "public", availability: "private" },
    { key: "stockList", name: "List Stock", category: "Stock", visibility: "public", availability: "private" },
    { key: "stockEdit", name: "Edit Stock", category: "Stock", visibility: "public", availability: "private" },
    { key: "stockDelete",name: "Delete Stock",category: "Stock",visibility: "public", availability: "private"},
    { key: "stockBulkDelete",name: "Bulk Delete Stock",category: "Stock",visibility: "public", availability: "private"},
    { key: "stockFilter",name: "Filter Stock",category: "Stock",visibility: "public", availability: "private"},
    { key: "stockExport",name: "Export Stock",category: "Stock",visibility: "public", availability: "private"},
    { key: "stockLevel", name: "List Stock Levels", category: "Stock", visibility: "public", availability: "private" },
    { key: "stockLevelExport",name: "Export Stock Level",category: "Stock",visibility: "public", availability: "private"},
    
    // Sales
    { key: "salesPOS", name: "POS", category: "Sales", visibility: "public", availability: "private" },
    { key: "salesList", name: "List Sales", category: "Sales", visibility: "public", availability: "private" },
    { key: "salesFilter",name: "Filter Sales",category: "Sales",visibility: "public", availability: "private"},
    { key: "salesExport",name: "Export Sales",category: "Sales",visibility: "public", availability: "private"},

    /* Reports */
    { key: "accountStatementList", name: "Account Statements", category: "Account Statement Reports", visibility: "private", availability: "private" },
    { key: "accountStatementListExport", name: "Export Account Statement List", category: "Account Statement Reports", visibility: "private", availability: "private" },
    { key: "accountStatementListMaskToggle", name: "Mask Toggle Account Statement List", category: "Account Statement Reports", visibility: "private", availability: "private" },
    { key: "accountStatementShowExport", name: "Export Account Statement Details", category: "Account Statement Reports", visibility: "private", availability: "private" },
    { key: "accountStatementShowMaskToggle", name: "Mask Toggle Account Statement Details", category: "Account Statement Reports", visibility: "private", availability: "private" },

    // Manage Settings
    { key: "settings", name: "Settings", category: "Settings", visibility: "private", availability: "private" },
    { key: "generalSettings",name: "General Settings",category: "Settings",visibility: "public", availability: "private" },
    { key: "emailSettings",name: "Email Settings",category: "Settings",visibility: "public", availability: "private" },
    { key: "securitySettings",name: "Security Settings",category: "Settings",visibility: "public", availability: "private" },


    // Audit Logs
    { key: "applicationLogs", name: "Application Logs", category: "Audit Logs", visibility: "public", availability: "private" },
];

// Initialize reactive variable for menu access with all keys set to false
export const menuAccess = ref(
    Object.fromEntries(accessKeys.map(({ key }) => [key, false]))
);

// Prevent duplicate API calls using a flag
let isMenuAccessFetched = false;

// Function to fetch menu access from the backend
const fetchMenuAccess = async () => {
    if (isMenuAccessFetched) return; // Prevent duplicate calls
    try {
        isMenuAccessFetched = true; // Set the flag to true
        // Retrieve the token from local storage
        const token = getToken(); // Retrieve the token

        const { data } = await axios.post("/accessmenus",
            {
                menuArray: accessKeys.map(({ key }) => key), // Send only keys
            },
            {
                headers: {
                    Authorization: `Bearer ${token}`, // Include token in the Authorization header
                },
            }
        );

        // Check if data is successful and contains the `hasAccess` array
        if (data.success && Array.isArray(data.hasAccess)) {
            // Update menuAccess with dynamic access based on hasAccess array
            menuAccess.value = Object.fromEntries(
                accessKeys.map(({ key }) => [key, data.hasAccess.includes(key)])
            );
        } else {
            throw new Error("Access denied");
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handler
        console.error("Error fetching menu access:", error);
        throw error; // Rethrow the error so the calling component can handle it
    }
};

    // Composable function
export function useMenuAccess() {
    // Use `onMounted` to automatically fetch menu access when the component is mounted
    onMounted(() => {
        fetchMenuAccess();
    });

    // Return the reactive menu access state
    return { menuAccess, fetchMenuAccess };
}
