<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RestaurantController;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // api/admin
    Route::group([
        'prefix' => 'admin',
        'middleware' => ['role_check:admin'] // admin is $role_name of UserRoleCheckMiddleware
    ], function () {
        // user management
        Route::apiResource('users', UserController::class);
    });

    // Route::apiResource('restaurants', RestaurantController::class);
    Route::get('/restaurants', [RestaurantController::class, 'index']);
    Route::post('/restaurants', [RestaurantController::class, 'store'])->middleware('role_check:admin_or_restaurant_owner');
   
    /* // user role management
    Route::apiResource('userRole', UserRoleController::class);
    Route::post('/delete_userRole', [UserRoleController::class, 'destroyUserRole']);
    Route::post('/restore_userRole', [UserRoleController::class, 'restoreUserRole']);
    Route::get('/authInfo', [UserRoleController::class, 'getAuthInfo']);
    
    // organizations management
    Route::apiResource('organizations', OrganizationController::class);
    Route::patch('organizations/{id}/restore', [OrganizationController::class, 'restore']);
    Route::get('/organizations_list', [OrganizationController::class, 'getLists']);
    Route::post('organizations/delete_organizations', [OrganizationController::class, 'destroyOrganizations']);
    Route::post('organizations/restore_organizations', [OrganizationController::class, 'restoreOrganizations']);

    //division management
    Route::apiResource('divisions', DivisionController::class);
    Route::patch('divisions/{id}/restore', [DivisionController::class, 'restore']);
    Route::post('divisions/delete_divisions', [DivisionController::class, 'destroyDivisions']);
    Route::post('divisions/restore_divisions', [DivisionController::class, 'restoreDivisions']);
    Route::get('/divisions_list', [DivisionController::class, 'getLists']);
    //factory management

    Route::apiResource('factories', FactoryController::class);
    Route::patch('factories/{id}/restore', [FactoryController::class, 'restore']);
    Route::post('factories/delete_factories', [FactoryController::class, 'destroyFactories']);
    Route::post('factories/restore_factories', [FactoryController::class, 'restoreFactories']);
    //team management
    Route::apiResource('teams', TeamController::class);
    Route::patch('teams/{id}/restore', [TeamController::class, 'restore']);
    Route::post('teams/delete_teams', [TeamController::class, 'destroyTeams']);
    Route::post('teams/restore_teams', [TeamController::class, 'restoreTeams']);
   
    //employee management
    Route::apiResource('employees', EmployeeController::class);
    Route::patch('employees/{id}/restore', [EmployeeController::class, 'restore']);
    Route::post('employees/delete_employees', [EmployeeController::class, 'destroyEmployees']);
    Route::post('employees/restore_employees', [EmployeeController::class, 'restoreEmployees']);
    Route::get('employeesSelectList', [EmployeeController::class, 'SelectList']);

    //employee history management
    Route::apiResource('employee-history', EmployeeHistoryController::class);

    //workType management
    Route::apiResource('/workType', WorkTypeController::class);
    Route::patch('workType/{id}/restore', [WorkTypeController::class, 'restore']);
    Route::post('workType/delete_workType', [WorkTypeController::class, 'destroyWorkType']);
    Route::post('workType/restore_workType', [WorkTypeController::class, 'restoreWorkType']);
    // Route
    //worksheetReport management
    Route::apiResource('/worksheetReport', EmployeeWorksheetController::class);
    Route::post('/worksheetReport/list', [EmployeeWorksheetController::class, 'worksheetReport']);
    // Route::post('factories/getList', [EmployeeWorksheetController::class, 'FactoryList']);
    
   
    Route::apiResource('dashboard', DashboardController::class);
    //setting options 
    Route::post('factories/getList', [SettingController::class, 'FactoryList']);
    Route::post('factoryList', [SettingController::class, 'getFactoryList']);
    Route::post('users/settingLanguage', [SettingController::class, 'changeLanguage']);
    Route::get('/teams_list', [SettingController::class, 'getTeamsLists']);
    Route::get('/users_list', [SettingController::class, 'getUsersLists']);
    //profile/update

    Route::post('profile/update', [ProfileController::class, 'changeAuth']); */

});







