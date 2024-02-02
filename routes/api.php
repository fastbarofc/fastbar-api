<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\City;
use App\Models\State;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\SectorOfficeController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\EventStatusController;
use App\Http\Controllers\DecoratorController;
use App\Http\Controllers\EventCaptureController;
use App\Http\Controllers\KitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\EventPlaceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\CeremonialController;
use App\Http\Controllers\EventGiftController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ExtraSaleController;
use App\Http\Controllers\OfficeEventController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CollaboratorIncidentController;

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
    Route::post('/logout', 'logout')->middleware('auth:api')->name('logout');
});

Route::group(['middleware' => 'auth:api'], function () {

    Route::get('states', function () {
        return State::all();
    });

    Route::get('cities/{state}', function (Request $request, State $state) {
        return City::where('state_id', $state->id)->get();
    });
});

Route::group(['middleware' => ['auth:api', 'admin']], function () {
    Route::resource('users', UserController::class)->except(['store']);
    Route::post('users/{user}/sync-roles', [UserController::class, 'syncRoles'])->name('users.sync-roles');
    Route::post('users/{user}/sync-permissions', [UserController::class, 'syncPermissions'])->name('users.sync-permissions');

    Route::resource('collaborators', CollaboratorController::class);

    Route::resource('roles', RoleController::class);
    Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');

    Route::resource('permissions', PermissionController::class)->only(['index', 'show']);

    Route::resource('sectors', SectorController::class);
    Route::get('sectors/{sector}/offices', [SectorController::class, 'offices'])->name('sectors.offices');

    Route::resource('sector-offices', SectorOfficeController::class);

    Route::resource('event-statuses', EventStatusController::class);

    Route::resource('decorators', DecoratorController::class);

    Route::resource('event-captures', EventCaptureController::class);

    Route::resource('kits', KitController::class);

    Route::resource('products', ProductController::class);

    Route::resource('event-types', EventTypeController::class);

    Route::resource('event-places', EventPlaceController::class);

    Route::resource('events', EventController::class);
    Route::get('events/{event}/gifts', [EventController::class, 'gifts'])->name('events.gifts');
    Route::get('events/{event}/materials', [EventController::class, 'materials'])->name('events.materials');
    Route::get('events/{event}/extra-sales', [EventController::class, 'extraSales'])->name('events.extra-sales');
    Route::get('events/{event}/event-kits', [EventController::class, 'eventKits'])->name('events.event-kits');
    Route::get('events/{event}/contracts', [EventController::class, 'resetContract'])->name('events.reset-contract');

    Route::resource('partners', PartnerController::class);

    Route::resource('ceremonials', CeremonialController::class);

    Route::resource('event-gifts', EventGiftController::class);

    Route::resource('materials', MaterialController::class);

    Route::resource('units', UnitController::class);

    Route::resource('extra-sales', ExtraSaleController::class);

    Route::resource('office-events', OfficeEventController::class);

    Route::resource('clients', ClientController::class);

    Route::resource('contracts', ContractController::class);
    Route::post('contracts/{contract}/to-sign', [ContractController::class, 'toSign'])->name('contracts.to-sign');

    Route::resource('collaborator-incidents', CollaboratorIncidentController::class);
});
