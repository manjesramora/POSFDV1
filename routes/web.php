<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\LabelcatalogController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\InsdosController;
use App\Http\Controllers\FreightController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\RcnController;

Route::get('/', function () {
    return view('login');
})->name('home');

Route::middleware(['auth'])->group(function () {

    Route::get('/index', [UserController::class, 'showDashFDForm'])->name('index');
    Route::get('/indexes', [UserController::class, 'showUserIndexForm'])->name('indexes');

    // Rutas relacionadas con los roles
    Route::get('/roles', [RoleController::class, 'roles'])->name('roles');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');

    // Rutas relacionadas con los empleados
    Route::get('/employees', [EmployeeController::class, 'employees'])->name('employees')->middleware('permission:EMPLEADOS'); // Mostrar lista de empleados
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show'); // Mostrar detalles de un empleado
    // Ruta para almacenar un nuevo empleado
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    // Ruta para mostrar el formulario de edición de un empleado
    Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');

    // Rutas relacionadas con los usuarios
    Route::get('/users', [UserController::class, 'index'])->name('users')->middleware('permission:USUARIOS'); // Mostrar lista de usuarios
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/create', [UserController::class, 'createUserForm'])->name('users.create');
    Route::post('/users/reset-password/{user}', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Rutas relacionadas con los roles
    Route::get('/roles', [RoleController::class, 'roles'])->name('roles')->middleware('permission:ROLES'); // Mostrar lista de roles
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/create', [RoleController::class, 'createRoleForm'])->name('roles.create');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');


    // Rutas relacionadas con los permisos
    Route::get('/permissions', [PermissionController::class, 'permissions'])->name('permissions')->middleware('permission:PERMISOS');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permissions/create', [PermissionController::class, 'createPermissionForm'])->name('permissions.create');
    Route::put('/permissions/{id}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('permissions/{id}', [PermissionController::class, 'destroy'])->name('permissions.destroy');



    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::get('providers/autocomplete', [ProviderController::class, 'autocomplete'])->name('providers.autocomplete')->middleware('permission:ORDENES');
    Route::get('/receptions/{ACMVOIDOC}', [OrderController::class, 'showReceptions'])->name('receptions.show')->middleware('permission:RECEPCIONES');
    Route::post('/receiptOrder/{ACMVOIDOC}', [OrderController::class, 'receiptOrder'])->name('receiptOrder');
    Route::get('/freights', [OrderController::class, 'showFreights'])->name('freights');

    // Rutas relacionadas con fletes
    Route::get('/freights', [FreightController::class, 'index'])->name('freights')->middleware('permission:ETIQUETAS');
    Route::get('/freights/pdf', [FreightController::class, 'generatePDF'])->name('freights.pdf');


    Route::get('/check-username', [UserController::class, 'checkUsername'])->name('check-username');
    Route::get('/labelscatalog', [LabelcatalogController::class, 'labelscatalog'])->name('labelscatalog')->middleware('permission:ETIQUETAS');

    // Rutas relacionadas con rcn
    Route::get('/rcn', [RcnController::class, 'index'])->name('rcn')->middleware('permission:RCN');
    route::get('/rcn/generate-pdf/{ACMROINDOC}', [RcnController::class, 'generatePdf'])->name('rcn.generatePdf');
    
    route::get('/print-report/{ACMROINDOC}', [RcnController::class, 'generatePdf'])->name('generatePdf');
});
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/change-password', [LoginController::class, 'changePassword'])->name('changePassword');

// Rutas relacionadas con Catalogo Etiquetas

Route::get('/etiquetascatalogo', [LabelcatalogController::class, 'labelscatalog']);

//Rutas Relacionadas con Imprimir Etiquetas
Route::post('/print-label', [LabelcatalogController::class, 'printLabel'])->name('print.label');
// Ruta para imprimir etiqueta con SKU y Precio
Route::post('/print-label-with-price', [LabelcatalogController::class, 'printLabelWithPrice'])->name('print.label.with.price');
// Ruta para obtener las UMV disponibles
Route::get('/get-umv/{productId}', [LabelcatalogController::class, 'getUMV']);
// Ruta para convertir el precio base según la UMV seleccionada
Route::post('/convert-price', [LabelcatalogController::class, 'convertPrice']);

Route::get('/insdos', [InsdosController::class, 'index']);
