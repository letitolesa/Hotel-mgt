<?php

use Illuminate\Routing\Router;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\PermissionController;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
        $router->resource('roles', RoleController::class);
$router->resource('permissions', PermissionController::class);
        Route::resource('users', \App\Admin\Controllers\UserController::class);
            $router->resource('departments', DepartmentController::class);
            $router->resource('positions', PositionController::class);
            $router->resource('categories', CategoryController::class);
            $router->resource('units', UnitController::class);
            $router->resource('inventory-items', InventoryItemController::class);
            $router->resource('assets', AssetController::class);
            $router->resource('room-types', RoomTypeController::class);
            $router->resource('rooms', RoomController::class);
            $router->resource('sections', SectionController::class);
            $router->resource('hotel-tables', HotelTableController::class);
            $router->resource('waiters', WaiterController::class);
            $router->resource('menu-categories', MenuCategoryController::class);
            $router->resource('menu-items', MenuItemController::class);
            $router->resource('menu-item-ingredients', MenuItemIngredientController::class);
            $router->resource('tax-rates', TaxRateController::class);
            $router->resource('promotions', PromotionController::class);

            


});
