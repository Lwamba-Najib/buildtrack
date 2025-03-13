import { createRouter, createWebHistory } from "vue-router";
// Login
import Login from "@/views/whenout/auth/Login.vue";
// Forgot Password
import ForgotPassword from "@/views/whenout/auth/ForgotPassword.vue";
// FAQs
import FaqPublic from "@/views/whenout/faqs/FaqPublic.vue";
// TwoFA
import TwoFA from "@/views/whenin/singles/TwoFA.vue";
// Home
import Home from "@/views/whenin/singles/Home.vue";
// Dashboard
import Dashboard from "@/views/whenin/singles/Dashboard.vue";
// Layout
import Layout from "@/views/whenin/layout/Layout.vue";
//Profile
import Profile from "@/views/whenin/singles/Profile.vue";
// User Settings
import UserSettings from "@/views/whenin/singles/UserSettings.vue";
/* Manage Accounts */
import RoleList from "@/views/whenin/roles/List.vue";
import RolePermission from "@/views/whenin/roles/Permission.vue";
import RoleCreate from "@/views/whenin/roles/Create.vue";
import RoleUpdate from "@/views/whenin/roles/Update.vue";
// Users
import UserList from "@/views/whenin/users/List.vue";
import UserCreate from "@/views/whenin/users/Create.vue";
import UserShow from "@/views/whenin/users/Show.vue";
import UserUpdate from "@/views/whenin/users/Update.vue";

/* Manage Products */
// Categories
import CategoryList from "@/views/whenin/products/categories/List.vue";
import CategoryCreate from "@/views/whenin/products/categories/Create.vue";
import CategoryUpdate from "@/views/whenin/products/categories/Update.vue";
// Product 
import ProductList from "@/views/whenin/products/products/List.vue";
import ProductCreate from "@/views/whenin/products/products/Create.vue";
import ProductUpdate from "@/views/whenin/products/products/Update.vue";
// Brand
import BrandList from "@/views/whenin/products/brands/List.vue";
import BrandCreate from "@/views/whenin/products/brands/Create.vue";
import BrandUpdate from "@/views/whenin/products/brands/Update.vue";
// Measurement
import MeasurementList from "@/views/whenin/products/measurements/List.vue";
import MeasurementCreate from "@/views/whenin/products/measurements/Create.vue";
import MeasurementUpdate from "@/views/whenin/products/measurements/Update.vue";
// Supplier
import SupplierList from "@/views/whenin/products/suppliers/List.vue";
import SupplierCreate from "@/views/whenin/products/suppliers/Create.vue";
import SupplierUpdate from "@/views/whenin/products/suppliers/Update.vue";
// Stock
import StockList from "@/views/whenin/products/stocks/List.vue";
import StockShow from "@/views/whenin/products/stocks/Show.vue";
import StockCreate from "@/views/whenin/products/stocks/Create.vue";
import StockUpdate from "@/views/whenin/products/stocks/Update.vue";
import StockLevel from "@/views/whenin/products/stocks/Level.vue";
// Sales
import SalesPOS from "@/views/whenin/sales/pos/POS.vue";
import SalesList from "@/views/whenin/sales/sales/List.vue";
import SalesShow from "@/views/whenin/sales/sales/Show.vue";

// Account Statement
import AccountStatementList from "@/views/whenin/reports/accountstatement/List.vue";
import AccountStatementShow from "@/views/whenin/reports/accountstatement/Show.vue";

// Trash
import Trash from "@/views/whenin/singles/Trash.vue";
// Audit Logs
import ApplicationLogList from "@/views/whenin/auditlogs/applicationlogs/List.vue";
import ApplicationLogShow from "@/views/whenin/auditlogs/applicationlogs/Show.vue";
// System Settings
import GeneralSettings from "@/views/whenin/settings/general/GeneralSettings.vue";
import EmailSettings from "@/views/whenin/settings/email/EmailSettings.vue";
import SecuritySettings from "@/views/whenin/settings/security/SecuritySettings.vue";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/",
            name: "Login",
            component: Login,
            meta: { public: true },
        },
        {
            path: "/forgotpassword",
            name: "ForgotPassword",
            component: ForgotPassword,
            meta: { public: true },
        },
        {
            path: "/faqpublic",
            name: "FaqPublic",
            component: FaqPublic,
            meta: { public: true },
        },
        {
            path: "/twofa",
            name: "TwoFA",
            component: TwoFA,
            meta: { public: false },
        },
        {
            path: "/",
            component: Layout,
            children: [
                {
                    path: "home",
                    name: "Home",
                    component: Home,
                    meta: { public: false },
                },
                {
                    path: "dashboard",
                    name: "Dashboard",
                    component: Dashboard,
                    meta: { public: false },
                },
                {
                    path: "profile",
                    name: "Profile",
                    component: Profile,
                    meta: { public: false },
                },
                {
                    path: "usersettings",
                    name: "UserSettings",
                    component: UserSettings,
                    meta: { public: false },
                },
                {
                    path: "rolelist",
                    name: "RoleList",
                    component: RoleList,
                    meta: { public: false },
                },
                {
                    path: "rolepermission/:id",
                    name: "RolePermission",
                    component: RolePermission,
                    meta: { public: false },
                },
                {
                    path: "rolecreate",
                    name: "RoleCreate",
                    component: RoleCreate,
                    meta: { public: false },
                },
                {
                    path: "roleupdate/:id",
                    name: "RoleUpdate",
                    component: RoleUpdate,
                    meta: { public: false },
                },
                {
                    path: "userlist",
                    name: "UserList",
                    component: UserList,
                    meta: { public: false },
                },
                {
                    path: "usercreate",
                    name: "UserCreate",
                    component: UserCreate,
                    meta: { public: false },
                },
                {
                    path: "usershow/:id",
                    name: "UserShow",
                    component: UserShow,
                    meta: { public: false },
                },
                {
                    path: "userupdate/:id",
                    name: "UserUpdate",
                    component: UserUpdate,
                    meta: { public: false },
                },
                {
                    path: "categorylist",
                    name: "CategoryList",
                    component: CategoryList,
                    meta: { public: false },
                },
                {
                    path: "categorycreate",
                    name: "CategoryCreate",
                    component: CategoryCreate,
                    meta: { public: false },
                },
                {
                    path: "categoryupdate/:id",
                    name: "CategoryUpdate",
                    component: CategoryUpdate,
                    meta: { public: false },
                },
                {
                    path: "productlist",
                    name: "ProductList",
                    component: ProductList,
                    meta: { public: false },
                },
                {
                    path: "productcreate",
                    name: "ProductCreate",
                    component: ProductCreate,
                    meta: { public: false },
                },
                {
                    path: "productupdate/:id",
                    name: "ProductUpdate",
                    component: ProductUpdate,
                    meta: { public: false },
                },
                {
                    path: "brandlist",
                    name: "BrandList",
                    component: BrandList,
                    meta: { public: false },
                },
                {
                    path: "brandcreate",
                    name: "BrandCreate",
                    component: BrandCreate,
                    meta: { public: false },
                },
                {
                    path: "brandupdate/:id",
                    name: "BrandUpdate",
                    component: BrandUpdate,
                    meta: { public: false },
                },
                {
                    path: "measurementlist",
                    name: "MeasurementList",
                    component: MeasurementList,
                    meta: { public: false },
                },
                {
                    path: "measurementcreate",
                    name: "MeasurementCreate",
                    component: MeasurementCreate,
                    meta: { public: false },
                },
                {
                    path: "measurementupdate/:id",
                    name: "MeasurementUpdate",
                    component: MeasurementUpdate,
                    meta: { public: false },
                },
                {
                    path: "supplierlist",
                    name: "SupplierList",
                    component: SupplierList,
                    meta: { public: false },
                },
                {
                    path: "suppliercreate",
                    name: "SupplierCreate",
                    component: SupplierCreate,
                    meta: { public: false },
                },
                {
                    path: "supplierupdate/:id",
                    name: "SupplierUpdate",
                    component: SupplierUpdate,
                    meta: { public: false },
                },
                {
                    path: "stocklist",
                    name: "StockList",
                    component: StockList,
                    meta: { public: false },
                },
                {
                    path: "stockcreate",
                    name: "StockCreate",
                    component: StockCreate,
                    meta: { public: false },
                },
                {
                    path: "stockshow/:id",
                    name: "StockShow",
                    component: StockShow,
                    meta: { public: false },
                },
                {
                    path: "stockupdate/:id",
                    name: "StockUpdate",
                    component: StockUpdate,
                    meta: { public: false },
                },
                {
                    path: "stocklevel",
                    name: "StockLevel",
                    component: StockLevel,
                    meta: { public: false },
                },
                {
                    path: "salespos",
                    name: "SalesPOS",
                    component: SalesPOS,
                    meta: { public: false },
                },
                {
                    path: "saleslist",
                    name: "SalesList",
                    component: SalesList,
                    meta: { public: false },
                },
                {
                    path: "salesshow/:id",
                    name: "SalesShow",
                    component: SalesShow,
                    meta: { public: false },
                },
                {
                    path: "accountstatementlist",
                    name: "AccountStatementList",
                    component: AccountStatementList,
                    meta: { public: false },
                },
                {
                    path: "accountstatementshow/:id",
                    name: "AccountStatementShow",
                    component: AccountStatementShow,
                    meta: { public: false },
                },
                {
                    path: "generalsettings",
                    name: "GeneralSettings",
                    component: GeneralSettings,
                    meta: { public: false },
                },
                {
                    path: "emailsettings",
                    name: "EmailSettings",
                    component: EmailSettings,
                    meta: { public: false },
                },
                {
                    path: "securitysettings",
                    name: "SecuritySettings",
                    component: SecuritySettings,
                    meta: { public: false },
                },
                {
                    path: "trash",
                    name: "Trash",
                    component: Trash,
                    meta: { public: false },
                },
                {
                    path: "applicationloglist",
                    name: "ApplicationLogList",
                    component: ApplicationLogList,
                    meta: { public: false },
                },
                {
                    path: "applicationlogshow/:id",
                    name: "ApplicationLogShow",
                    component: ApplicationLogShow,
                    meta: { public: false },
                }
                // Other admin routes can go here
            ],
        },
    ],
});
router.beforeEach((to, from, next) => {
    // Check if the user is authenticated (e.g., check if a token is stored)
    const isAuthenticated = !!localStorage.getItem("token");

    if (isAuthenticated && to.meta.public) {
        // If the user is authenticated and tries to access a public page, redirect to /home
        next({ name: "Home" });
    } else if (!to.meta.public && !isAuthenticated && to.name !== "TwoFA") {
        // If the route is not public and the user is not authenticated, redirect to login
        // Allow access to the TwoFA route even if not authenticated
        next({ name: "Login" });
    } else {
        // Otherwise, allow navigation
        next();
    }
});
export default router;