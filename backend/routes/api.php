<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AddressBookController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\ReportStockController;
use App\Http\Controllers\StockBalanceController;
use App\Http\Controllers\EmailSettingsController;
use App\Http\Controllers\ApplicationLogController;
use App\Http\Controllers\ReportDailySalesController;
use App\Http\Controllers\ReportStockAgingController;
use App\Http\Controllers\SecuritySettingsController;
use App\Http\Controllers\ReportWeeklySalesController;
use App\Http\Controllers\AppearanceSettingsController;
use App\Http\Controllers\ReportMonthlySalesController;
use App\Http\Controllers\ReportStockBalanceController;
use App\Http\Controllers\ReportStockLowAlertController;
use App\Http\Controllers\BusinessInfoSettingsController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\ReportConsolidatedSalesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

########################### CSRF Token Route ###########################
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

########################### PUBLIC ROUTES ###########################
Route::post('/login', [AccessController::class, 'authenticate']);
Route::post('/verifyotp', [AccessController::class, 'verifyOTP']);
Route::post('/getotp', [AccessController::class, 'getOTP']);
Route::post('/forgotpassword', [AccessController::class, 'forgotpassword']);
Route::get('getlogofavicon', [AppearanceSettingsController::class, 'index']);

########################### PROTECTED ROUTES ###########################
Route::middleware('auth:sanctum')->group(function () {
    // Access Routes
    Route::get('/session', [AccessController::class, 'authenticated']);
    Route::get('/loggedinuser', [AccessController::class, 'loggedinuser']);
    Route::post('/generateuserotp', [AccessController::class, 'generateUserOTP']);
    Route::post('/logout', [AccessController::class, 'logout']);

    // Home Routes
    Route::get('home', [HomeController::class, 'index']);

    // Profile Routes
    Route::get('profile', [ProfileController::class, 'index']);

    // Dashboard Routes
    Route::get('dashboard', [DashboardController::class, 'index']);

    // User Routes
    Route::get('userlist', [UserController::class, 'index']);
    Route::get('usergender', [UserController::class, 'userGender']);
    Route::get('userroles', [UserController::class, 'userRoles']);
    Route::post('userstore', [UserController::class, 'store']);
    Route::get('userxlsx', [UserController::class, 'xlsx']);
    Route::get('usercsv', [UserController::class, 'csv']);
    Route::get('usershow/{user}', [UserController::class, 'show']);
    Route::get('userpdf/{user}', [UserController::class, 'pdf']);
    Route::put('userupdate/{user}', [UserController::class, 'update']);
    Route::delete('userdelete/{user}', [UserController::class, 'destroy']);
    Route::delete('usersdelete', [UserController::class, 'massDestroy']);
    Route::post('userchangepassword', [UserController::class, 'userChangePassword']);
    Route::get('userpersonaldetails', [UserController::class, 'userPersonalDetails']);
    Route::put('userlockunlock/{user}', [UserController::class, 'userLockUnlock']);

    // Role Routes
    Route::get('rolelist', [RoleController::class, 'index']);
    Route::post('rolestore', [RoleController::class, 'store']);
    Route::get('access/{role}', [RoleController::class, 'access']);
    Route::get('roleshow/{role}', [RoleController::class, 'show']);
    Route::put('roleupdate/{role}', [RoleController::class, 'update']);
    Route::delete('roledelete/{role}', [RoleController::class, 'destroy']);
    Route::delete('rolesdelete', [RoleController::class, 'massDestroy']);

    // Permission Routes
    Route::post('assignmenus/{role}', [PermissionController::class, 'assignMenus']);
    Route::get('assignedmenus/{role}', [PermissionController::class, 'assignedMenus']);
    Route::post('accessmenus', [PermissionController::class, 'accessMenu']);

    // Category Routes
    Route::get('categorylist', [CategoryController::class, 'index']);
    Route::post('categorystore', [CategoryController::class, 'store']);
    Route::get('categoryshow/{category}', [CategoryController::class, 'show']);
    Route::put('categoryupdate/{category}', [CategoryController::class, 'update']);
    Route::delete('categorydelete/{category}', [CategoryController::class, 'destroy']);
    Route::delete('categoriesdelete', [CategoryController::class, 'massDestroy']);
    Route::get('getcategories', [CategoryController::class, 'getCategories']);

    // Product  Routes
    Route::get('productlist', [ProductController::class, 'index']);
    Route::post('productstore', [ProductController::class, 'store']);
    Route::get('productshow/{product}', [ProductController::class, 'show']);
    Route::put('productupdate/{product}', [ProductController::class, 'update']);
    Route::delete('productdelete/{product}', [ProductController::class, 'destroy']);
    Route::delete('productsdelete', [ProductController::class, 'massDestroy']);
    Route::get('getproducts', [ProductController::class, 'getProducts']);

    // Brand Routes
    Route::get('brandlist', [BrandController::class, 'index']);
    Route::post('brandstore', [BrandController::class, 'store']);
    Route::get('brandshow/{brand}', [BrandController::class, 'show']);
    Route::put('brandupdate/{brand}', [BrandController::class, 'update']);
    Route::delete('branddelete/{brand}', [BrandController::class, 'destroy']);
    Route::delete('brandsdelete', [BrandController::class, 'massDestroy']);
    Route::get('getbrands', [BrandController::class, 'getBrands']);
    Route::get('getbrandsbyproduct/{productId}', [BrandController::class, 'getBrandsByProductId']);

    // Measurement Routes
    Route::get('measurementlist', [MeasurementController::class, 'index']);
    Route::post('measurementstore', [MeasurementController::class, 'store']);
    Route::get('measurementshow/{measurement}', [MeasurementController::class, 'show']);
    Route::put('measurementupdate/{measurement}', [MeasurementController::class, 'update']);
    Route::delete('measurementdelete/{measurement}', [MeasurementController::class, 'destroy']);
    Route::delete('measurementsdelete', [MeasurementController::class, 'massDestroy']);
    Route::get('getmeasurements', [MeasurementController::class, 'getMeasurements']);

    // Supplier Routes
    Route::get('supplierlist', [SupplierController::class, 'index']);
    Route::post('supplierstore', [SupplierController::class, 'store']);
    Route::get('suppliershow/{supplier}', [SupplierController::class, 'show']);
    Route::put('supplierupdate/{supplier}', [SupplierController::class, 'update']);
    Route::delete('supplierdelete/{supplier}', [SupplierController::class, 'destroy']);
    Route::delete('suppliersdelete', [SupplierController::class, 'massDestroy']);
    Route::get('getsuppliers', [SupplierController::class, 'getSuppliers']);
    Route::get('supplierxlsx', [SupplierController::class, 'xlsx']);
    Route::get('suppliercsv', [SupplierController::class, 'csv']);

    // Stock Routes
    Route::get('stocklist', [StockController::class, 'index']);
    Route::post('stockstore', [StockController::class, 'store']);
    Route::get('stockshow/{stock}', [StockController::class, 'show']);
    Route::put('stockupdate/{stock}', [StockController::class, 'update']);
    Route::delete('stockdelete/{stock}', [StockController::class, 'destroy']);
    Route::delete('stocksdelete', [StockController::class, 'massDestroy']);
    Route::get('stockxlsx', [StockController::class, 'xlsx']);
    Route::get('stockcsv', [StockController::class, 'csv']);
    Route::get('stockpdf/{stock}', [StockController::class, 'pdf']);

    // Stock Level Routes
    Route::get('stocklevel', [StockBalanceController::class, 'index']);
    Route::get('stocklevelxlsx', [StockBalanceController::class, 'xlsx']);
    Route::get('stocklevelcsv', [StockBalanceController::class, 'csv']);

    //Sales
    Route::get('saleslist', [SalesController::class, 'index']);
    Route::get('saleshow/{id}', [SalesController::class, 'show']);
    Route::get('getproductsinstock', [SalesController::class, 'getProductsInstock']);
    Route::get('getbatchnumbersbyproductinstock/{productId}', [SalesController::class, 'getBatchNumbersByProductInstock']);
    Route::get('getbrandsbybatchnumberinstock/{batchNumber}', [SalesController::class, 'getBrandsByBatchNumberInstock']);
    Route::get('getmeasurementsbybrandinstock/{brandId}', [SalesController::class, 'getMeasurementsByBrandInstock']);
    Route::get('getsalepriceinstock', [SalesController::class, 'getSalePriceInstock']);
    Route::post('salesstore', [SalesController::class, 'store']);

    //Report Daily Sales
    Route::get('reportdailysaleslist', [ReportDailySalesController::class, 'index']);
    Route::get('reportdailysalesxlsx', [ReportDailySalesController::class, 'xlsx']);
    Route::get('reportdailysalescsv', [ReportDailySalesController::class, 'csv']);

    //Report Weekly Sales
    Route::get('reportweeklysaleslist', [ReportWeeklySalesController::class, 'index']);
    Route::get('reportweeklysalesxlsx', [ReportWeeklySalesController::class, 'xlsx']);
    Route::get('reportweeklysalescsv', [ReportWeeklySalesController::class, 'csv']);

    //Report Monthly Sales
    Route::get('reportmonthlysaleslist', [ReportMonthlySalesController::class, 'index']);
    Route::get('reportmonthlysalesxlsx', [ReportMonthlySalesController::class, 'xlsx']);
    Route::get('reportmonthlysalescsv', [ReportMonthlySalesController::class, 'csv']);

    //Report Consolidated Sales
    Route::get('reportconsolidatedsaleslist', [ReportConsolidatedSalesController::class, 'index']);
    Route::get('reportConsolidatedsalesxlsx', [ReportConsolidatedSalesController::class, 'xlsx']);
    Route::get('reportconsolidatedsalescsv', [ReportConsolidatedSalesController::class, 'csv']);

    //Report Stock
    Route::get('reportstocklist', [ReportStockController::class, 'index']);
    Route::get('reportstockxlsx', [ReportStockController::class, 'xlsx']);
    Route::get('reportstockcsv', [ReportStockController::class, 'csv']);

    //Report Stock Balance
    Route::get('reportstockbalancelist', [ReportStockBalanceController::class, 'index']);
    Route::get('reportstockbalancexlsx', [ReportStockBalanceController::class, 'xlsx']);
    Route::get('reportstockbalancecsv', [ReportStockBalanceController::class, 'csv']);

    //Report Stock Low Alert
    Route::get('reportstocklowalertlist', [ReportStockLowAlertController::class, 'index']);
    Route::get('reportstocklowalertxlsx', [ReportStockLowAlertController::class, 'xlsx']);
    Route::get('reportstocklowalertcsv', [ReportStockLowAlertController::class, 'csv']);

    //Report Stock Aging
    Route::get('reportstockaginglist', [ReportStockAgingController::class, 'index']);
    Route::get('reportstockagingxlsx', [ReportStockAgingController::class, 'xlsx']);
    Route::get('reportstockagingcsv', [ReportStockAgingController::class, 'csv']);

    //Address Book
    Route::get('addressbook/{phone_number}', [AddressBookController::class, 'show']);

    // Business Info Settings Routes
    Route::get('businessinfosettingslist', [BusinessInfoSettingsController::class, 'index']);
    Route::post('businessinfosettingsstore', [BusinessInfoSettingsController::class, 'store']);
    Route::get('getbusinessinfosettings', [BusinessInfoSettingsController::class, 'getBusinessInfoSettings']);

    // Appearance Settings Routes
    Route::get('appearancesettingslist', [AppearanceSettingsController::class, 'index']);
    Route::post('appearancesettingsstore', [AppearanceSettingsController::class, 'store']);

    // Email Settings Routes
    Route::get('emailsettingslist', [EmailSettingsController::class, 'index']);
    Route::post('emailsettingsstore', [EmailSettingsController::class, 'store']);

    // Security Settings Routes
    Route::get('securitysettingslist', [SecuritySettingsController::class, 'index']);
    Route::post('securitysettingsstore', [SecuritySettingsController::class, 'store']);

    // Trash Routes
    Route::get('trashlist', [TrashController::class, 'index']);
    Route::put('trashrestore/{model}/{id}', [TrashController::class, 'restore']);
    Route::delete('trashdelete/{model}/{id}', [TrashController::class, 'destroy']);

    // System Log Routes
    Route::get('applicationloglist', [ApplicationLogController::class, 'index']);
    Route::get('applicationlogcolumns', [ApplicationLogController::class, 'applicationLogColumns']);
    Route::get('applicationlog/{applicationlog}', [ApplicationLogController::class, 'show']);
    Route::get('applicationlogxlsx/{applicationlog}', [ApplicationLogController::class, 'xlsx']);
    Route::get('applicationlogcsv/{applicationlog}', [ApplicationLogController::class, 'csv']);
});

// TEMPORARY ROUTE TO CREATE ADMIN USER - DELETE THIS AFTER USE
use Illuminate\Support\Facades\Hash;

Route::get('/setup-admin', function () {
    $existingUser = \App\Models\User::where('email', 'admin@example.com')->first();
    
    if ($existingUser) {
        return response()->json(['message' => 'Admin user already exists!']);
    }

    $user = new \App\Models\User();
    $user->name = 'Admin';
    $user->email = 'admin@example.com';
    $user->password = Hash::make('password');
    $user->save();

    return response()->json(['message' => 'Success! Admin user created. You can now delete this route.']);
});

// TEMPORARY DEBUG ROUTE - DELETE AFTER USE
Route::get('/debug-user', function () {
    $user = \App\Models\User::where('email', 'admin@example.com')->first();
    
    if (!$user) {
        return response()->json(['error' => 'User not found']);
    }
    
    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_number' => $user->user_number,
            'role_id' => $user->role_id,
            'environment' => $user->environment,
            'is_locked' => $user->is_locked,
            'password_hash' => substr($user->password, 0, 20) . '...',
        ],
        'password_verify' => password_verify('password', $user->password),
    ]);
});

// TEMPORARY: Reset admin password
Route::get('/reset-password', function () {
    $user = \App\Models\User::where('email', 'admin@example.com')->first();
    
    if ($user) {
        $user->password = bcrypt('password');
        $user->save();
        
        return response()->json([
            'message' => 'Password reset successfully!',
            'new_verify' => password_verify('password', $user->password)
        ]);
    }
    
    return response()->json(['error' => 'User not found']);
});

// TEMPORARY: Grant all menu permissions to Super Admin
Route::get('/grant-all-permissions', function () {
    // Get the Super Admin role (id = 1)
    $roleId = 1;
    
    // Get all menu keys from permissions.php
    $allMenus = \App\Models\Permission::all(); // This might fail if table is empty
    
    // Alternative: Just create permissions for all common menus
    $menuList = [
        'dashboard', 'trash',
        'roleAdd', 'roleList', 'roleEdit', 'roleDelete',
        'userAdd', 'userList', 'userEdit', 'userDelete',
        'categoryAdd', 'categoryList', 'categoryEdit', 'categoryDelete',
        'productAdd', 'productList', 'productEdit', 'productDelete',
        'brandAdd', 'brandList', 'brandEdit', 'brandDelete',
        'measurementAdd', 'measurementList', 'measurementEdit', 'measurementDelete',
        'supplierAdd', 'supplierList', 'supplierEdit', 'supplierDelete',
        'stockAdd', 'stockList', 'stockEdit', 'stockDelete', 'stockLevel',
        'salesPOS', 'salesList',
        'reportDailySalesList', 'reportWeeklySalesList', 'reportMonthlySalesList', 'reportConsolidatedSalesList',
        'reportStockList', 'reportStockBalanceList', 'reportStockLowAlertList', 'reportStockAgingList',
        'reportRevenueList', 'reportProfitLossList', 'reportExpenseList', 'reportSupplierList', 'reportTaxList',
        'businessInfoSettings', 'appearanceSettings', 'emailSettings', 'securitySettings',
        'applicationLogs'
    ];
    
    // Delete existing permissions for role 1
    \DB::table('permissions')->where('role_id', $roleId)->delete();
    
    // Insert new permissions
    foreach ($menuList as $menu) {
        \DB::table('permissions')->insert([
            'role_id' => $roleId,
            'menu' => $menu,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    return response()->json(['message' => 'All permissions granted to Super Admin!', 'count' => count($menuList)]);
});

// TEMPORARY: Debug database query directly
Route::get('/debug-menus-query', function () {
    try {
        // We know your admin user is role_id 1
        $roleId = 1;
        
        // Count how many permissions exist for role 1
        $total = \DB::table('permissions')->where('role_id', $roleId)->count();
        
        // Try to fetch the menu names
        $menus = \DB::table('permissions')->where('role_id', $roleId)->pluck('menu')->toArray();
        
        return response()->json([
            'status' => 'success',
            'total_permissions' => $total,
            'menus_found' => $menus
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});