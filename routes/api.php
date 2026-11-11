<?php

use App\Http\Controllers\AddressController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlockedDateController;
use App\Http\Controllers\BreakItemController;
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
use App\Http\Controllers\EventKitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\EventPlaceController;
use App\Http\Controllers\EventAddendumController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PartnerDirectoryController;
use App\Http\Controllers\CeremonialController;
use App\Http\Controllers\EventGiftController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ExtraSaleController;
use App\Http\Controllers\OfficeEventController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CollaboratorIncidentController;
use App\Http\Controllers\CompetingCompanyController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\HowDidYouHearController;
use App\Http\Controllers\EventNoteController;
use App\Http\Controllers\EventBatchController;
use App\Http\Controllers\EventPaymentController;
use App\Http\Controllers\FailureController;
use App\Http\Controllers\LostReasonController;
use App\Http\Controllers\OfficeBudgetController;
use App\Http\Controllers\ParthenonBankAccountController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\ProductSubgroupController;
use App\Http\Controllers\QualificationController;
use App\Models\Qualification;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FinancialOperationController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\DevolutionController;

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

    Route::put('/users/update-profile', [UserController::class, 'updateProfile']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);

    Route::get('users/{user}/client', [UserController::class, 'getClientIdbyUserId'])->name('users.client');
    Route::get('clients/{client}/events', [ClientController::class, 'clientEvents'])->name('clients.events');
});

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('users-available-collaborators', [UserController::class, 'availableCollaborators'])->name('users.available-collaborators');
    Route::resource('users', UserController::class);
    Route::get('users/{user}/access', [UserController::class, 'access'])->name('users.access');
    Route::put('users/{user}/access', [UserController::class, 'updateAccess'])->name('users.update-access');
    Route::post('users/{user}/sync-roles', [UserController::class, 'syncRoles'])->name('users.sync-roles');
    Route::post('users/{user}/sync-permissions', [UserController::class, 'syncPermissions'])->name('users.sync-permissions');

    Route::put('collaborators/{collaborator}/change-password', [CollaboratorController::class, 'changePassword'])->name('collaborators.change-password');
    Route::post('collaborators-import', [CollaboratorController::class, 'import'])->name('collaborators.import');
    Route::get('collaborators-imports', [CollaboratorController::class, 'imports'])->name('collaborators.imports');
    Route::get('collaborators-imports/{collaboratorImport}/download', [CollaboratorController::class, 'downloadImport'])->name('collaborators.imports.download');
    Route::post('collaborators-import-clt', [CollaboratorController::class, 'importClt'])->name('collaborators.import-clt');
    Route::resource('collaborators', CollaboratorController::class);

    Route::resource('roles', RoleController::class);
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');

    Route::resource('permissions', PermissionController::class)->only(['index', 'show']);

    Route::resource('sectors', SectorController::class);
    Route::get('sectors/{sector}/offices', [SectorController::class, 'offices'])->name('sectors.offices');

    Route::resource('sector-offices', SectorOfficeController::class);

    Route::resource('office-budgets', OfficeBudgetController::class);
    Route::post('office-budgets/{officeBudget}/attach-collaborators', [OfficeBudgetController::class, 'attachCollaborators'])->name('office-budgets.attach-collaborators');
    Route::post('office-budgets/{officeBudgetId}/detach-collaborator', [OfficeBudgetController::class, 'detachCollaborator'])->name('office-budgets.detach-collaborators');


    Route::resource('event-statuses', EventStatusController::class);

    Route::resource('decorators', DecoratorController::class);

    Route::resource('event-captures', EventCaptureController::class);

    Route::get('kits/tree', [KitController::class, 'indexTree'])->name('kits.tree');
    Route::get('kits/full-tree', [KitController::class, 'fullTree'])->name('kits.full-tree');
    Route::put('kits/update-product', [KitController::class, 'updateProductKit'])->name('kits.update-product');
    Route::get('kits/{kit}/children', [KitController::class, 'children'])->name('kits.children');
    Route::post('kits/{movingItem}/change-ordination', [KitController::class, 'changeOrdination'])->name('kits.change-ordination');
    Route::resource('kits', KitController::class);

    Route::put('event-kits/update-product', [EventKitController::class, 'updateProductEventKit'])->name('event-kits.update-product');
    Route::get('event-kits/tree', [EventKitController::class, 'indexTree'])->name('event-kits.tree');
    Route::get('event-kits/full-tree', [EventKitController::class, 'fullTree'])->name('event-kits.full-tree');
    Route::post('event-kits/{movingItem}/change-ordination', [EventKitController::class, 'changeOrdination'])->name('event-kits.change-ordination');
    Route::resource('event-kits', EventKitController::class);
    Route::get('event-kits/{kit}/children', [EventKitController::class, 'children'])->name('event-kits.children');

    Route::resource('products', ProductController::class);
    Route::post('products/move-to-kit', [ProductController::class, 'moveToKit']);
    Route::post('products/move-to-event-kit', [ProductController::class, 'moveToEventKit']);
    Route::post('products/attach-to-event-kit', [ProductController::class, 'attachToEventKit']);
    Route::post('products/detach-from-event-kit', [ProductController::class, 'detachFromEventKit']);
    Route::post('products/attach-to-kit', [ProductController::class, 'attachToKit']);
    Route::post('products/detach-from-kit', [ProductController::class, 'detachFromKit']);

    Route::resource('event-types', EventTypeController::class);

    Route::resource('event-places', EventPlaceController::class);

    Route::get('events/calendar', [EventController::class, 'indexCalendar'])->name('events.calendar');
    Route::get('events/form-data/{event?}', [EventController::class, 'getFormData'])->name('events.form-data');
    Route::resource('events', EventController::class);
    Route::get('events/{event}/gifts', [EventController::class, 'gifts'])->name('events.gifts');
    Route::get('events/{event}/materials', [EventController::class, 'materials'])->name('events.materials');
    Route::get('events/{event}/extra-sales', [EventController::class, 'extraSales'])->name('events.extra-sales');
    Route::put('events/{event}/extra-sales', [EventController::class, 'updateExtraSales'])->name('events.update-extra-sales');
    Route::put('events/{event}/installments', [EventController::class, 'updateInstallments'])->name('events.update-installments');
    Route::get('events/{event}/event-kits', [EventController::class, 'eventKits'])->name('events.event-kits');
    Route::get('events/{event}/contracts', [EventController::class, 'resetContract'])->name('events.reset-contract');
    Route::get('events/{event}/office-events', [EventController::class, 'officeEvents'])->name('events.office-events');
    Route::get('events/{event}/office-events/collaborators', [EventController::class, 'getOfficeEventsWithCollaboratorsForEvent']);
    Route::get('events/{eventId}/office-budgets/collaborators', [EventController::class, 'getOfficeBudgetsWithCollaboratorsForEvent']);

    Route::post('events/{event}/legal-representatives', [EventController::class, 'attachLegalRepresentatives'])->name('events.attach-legal-representatives');
    Route::get('events/{event}/legal-representatives', [EventController::class, 'legalRepresentatives'])->name('events.legal-representatives');

    Route::post('events/{event}/materials/{material}', [EventController::class, 'syncMaterials'])->name('events.sync-materials');

    Route::resource('event-batches', EventBatchController::class);

    Route::resource('event-notes', EventNoteController::class);

    Route::get('partner-directory', [PartnerDirectoryController::class, 'index'])->name('partner-directory.index');
    Route::resource('partners', PartnerController::class);

    Route::resource('ceremonials', CeremonialController::class);

    Route::resource('event-gifts', EventGiftController::class);

    Route::resource('materials', MaterialController::class);

    Route::resource('units', UnitController::class);

    Route::resource('extra-sales', ExtraSaleController::class);

    Route::resource('office-events', OfficeEventController::class);

    Route::post('office-events/{officeEvent}/attach-collaborators', [OfficeEventController::class, 'attachCollaborators'])->name('office-events.attach-collaborators');
    Route::post('office-events/{officeEventId}/detach-collaborator', [OfficeEventController::class, 'detachCollaborator'])->name('office-events.detach-collaborators');

    Route::resource('clients', ClientController::class);


    Route::resource('contracts', ContractController::class);

    Route::post('contracts/generate/{event}', [ContractController::class, 'storeGenerate'])->name('contracts.generate-pdf');

    Route::post('contracts/{contract}/to-sign', [ContractController::class, 'toSign'])->name('contracts.to-sign');

    Route::get('/contracts/{eventId}/check-signed', [ContractController::class, 'checkSignedContract']);

    Route::resource('collaborator-incidents', CollaboratorIncidentController::class);

    Route::put('/collaborator/{collaborator}/qualifications', [CollaboratorController::class, 'updateQualifications']);

    Route::resource('qualifications', QualificationController::class);

    Route::resource('blocked-dates', BlockedDateController::class);

    Route::resource('break-items', BreakItemController::class);
    Route::post('events/{event}/break-items/attach', [BreakItemController::class, 'attachBreakItemsToEvent']);
    Route::post('events/{event}/break-items/detach/{breakItem}', [BreakItemController::class, 'detachBreakItemFromEvent']);
    Route::get('events/{event}/break-items', [BreakItemController::class, 'getEventBreakItems']);


    Route::resource('product-groups', ProductGroupController::class);
    Route::resource('product-subgroups', ProductSubgroupController::class);

    Route::resource('how-did-you-hears', HowDidYouHearController::class);

    Route::resource('competing-companies', CompetingCompanyController::class);

    Route::resource('lost-reasons', LostReasonController::class);

    Route::get('product-get-photos/{product}/', [ProductController::class, 'getFilePath'])->name('products.get-photo');

    Route::get('collaborator-get-photos/{collaborator}/', [CollaboratorController::class, 'getFilePath'])->name('collaborators.get-photo');

    Route::resource('failures', FailureController::class);
    Route::get('events/{event}/failures', [EventController::class, 'getEventFailures']);
    Route::post('events/{event}/failures', [EventController::class, 'attachFailure']);
    Route::delete('events/{event}/failures/{failure}', [EventController::class, 'detachFailure']);

    Route::resource('payment-methods', PaymentMethodController::class);
    Route::resource('parthenon-bank-accounts', ParthenonBankAccountController::class);
    Route::resource('event-payments', EventPaymentController::class);

    Route::delete('/events/{event}/related-events', [EventController::class, 'clearRelatedEvents']);
    Route::post('/events/{event}/legal-representative/{legalRepresentative}', [EventController::class, 'attachLegalRepresentative']);
    Route::delete('/events/{event}/legal-representative/{legalRepresentative}', [EventController::class, 'detachLegalRepresentative']);
    Route::post('/events/generate-budget/{event}', [EventController::class, 'generateBudget']);
    Route::get('/events/{event}/proposal-data', [EventController::class, 'proposalData']);
    Route::get('/events/{event}/proposal-history', [EventController::class, 'proposalHistory']);
    Route::post('/events/generate-proposal/{event}', [EventController::class, 'generateProposal']);
    Route::post('/events/generate-letterhead-budget/{event}', [EventController::class, 'generateLetterheadBudget']);
    Route::get('/events/{event}/contract-data', [EventController::class, 'contractData']);
    Route::get('/events/{event}/contract-history', [EventController::class, 'contractHistory']);
    Route::post('/events/generate-event-contract/{event}', [EventController::class, 'generateEventContract']);
    Route::get('/events/{event}/addendums', [EventAddendumController::class, 'index']);
    Route::post('/events/{event}/addendums', [EventAddendumController::class, 'store']);
    Route::post('/events/{event}/addendums/{addendum}/pdf', [EventAddendumController::class, 'pdf'])->scopeBindings();
    Route::post('/events/generate-closure/{event}', [EventController::class, 'generateClosure']);
    Route::post('/events/generate-summary/{event}', [EventController::class, 'generateSummary']);
    Route::post('/events/generate-menu/{event}', [EventController::class, 'generateMenu']);
    Route::post('/events/generate-menu-with-observations/{event}', [EventController::class, 'generateMenuWithObservations']);
    Route::post('/events/generate-collaborators/{event}', [EventController::class, 'generateCollaborators']);
    Route::post('/events/generate-event-checklist/{event}', [EventController::class, 'generateEventChecklist']);
    Route::get('/events/{event}/check-if-has-scheduled-events', [EventController::class, 'checkIfHasScheduledEvents']);
    Route::post('/reports/events/pdf', [ReportController::class, 'generatePdfReport']);
    Route::get('/reports/filter-options', [ReportController::class, 'filterOptions']);
    Route::get('/reports/clients', [ReportController::class, 'clients']);
    Route::get('/reports/{report}', [ReportController::class, 'show'])->whereIn('report', array_keys(ReportController::REPORTS));
    Route::get('/reports/{report}/export', [ReportController::class, 'export'])->whereIn('report', array_keys(ReportController::REPORTS));

    Route::get('/charts/financial', [ChartController::class, 'financial']);
    Route::get('/charts/commercial', [ChartController::class, 'commercial']);
    Route::get('/charts/monthly', [ChartController::class, 'monthly']);

    Route::post('/contract-template/save', [ContractTemplateController::class, 'save']);
    Route::get('/contract-template/get', [ContractTemplateController::class, 'show']);

    Route::get('/addresses/cep/{cep}', [AddressController::class, 'getAddressByCep']);

    Route::resource('suppliers', SupplierController::class);

    // Financeiro (igual ao Parthenon): contas a pagar/receber, BV, acerto, relatório, conciliação e cartão.
    Route::get('payables/monthly-report', [PayableController::class, 'monthlyReport'])->name('payables.monthly-report');
    Route::get('payables/event-settlement/{event}', [PayableController::class, 'eventSettlement'])->name('payables.event-settlement');
    Route::get('payables/bv-suggestion/{event}', [PayableController::class, 'bvSuggestion'])->name('payables.bv-suggestion');
    Route::post('payables/{payable}/approve', [PayableController::class, 'approve'])->name('payables.approve');
    Route::post('payables/{payable}/reject', [PayableController::class, 'reject'])->name('payables.reject');
    Route::post('payables/{payable}/settle', [PayableController::class, 'settle'])->name('payables.settle');
    Route::post('payables/{payable}/reopen', [PayableController::class, 'reopen'])->name('payables.reopen');
    Route::post('receivables/{receivable}/settle', [ReceivableController::class, 'settle'])->name('receivables.settle');
    Route::post('receivables/{receivable}/reopen', [ReceivableController::class, 'reopen'])->name('receivables.reopen');
    Route::get('bank-reconciliation', [\App\Http\Controllers\BankReconciliationController::class, 'overview'])->name('bank-reconciliation.overview');
    Route::get('bank-reconciliation/statement', [\App\Http\Controllers\BankReconciliationController::class, 'statement'])->name('bank-reconciliation.statement');
    Route::post('bank-reconciliation/import', [\App\Http\Controllers\BankReconciliationController::class, 'import'])->name('bank-reconciliation.import');
    Route::delete('bank-reconciliation/imports/{import}', [\App\Http\Controllers\BankReconciliationController::class, 'destroyImport'])->name('bank-reconciliation.imports.destroy');
    Route::post('bank-reconciliation/match-suggested', [\App\Http\Controllers\BankReconciliationController::class, 'matchSuggested'])->name('bank-reconciliation.match-suggested');
    Route::post('bank-reconciliation/lines/{line}/match', [\App\Http\Controllers\BankReconciliationController::class, 'match'])->name('bank-reconciliation.lines.match');
    Route::post('bank-reconciliation/lines/{line}/ignore', [\App\Http\Controllers\BankReconciliationController::class, 'ignore'])->name('bank-reconciliation.lines.ignore');
    Route::post('bank-reconciliation/lines/{line}/reopen', [\App\Http\Controllers\BankReconciliationController::class, 'reopen'])->name('bank-reconciliation.lines.reopen');
    Route::post('bank-reconciliation/lines/{line}/launch', [\App\Http\Controllers\BankReconciliationController::class, 'launch'])->name('bank-reconciliation.lines.launch');
    Route::get('credit-cards', [\App\Http\Controllers\CreditCardController::class, 'index'])->name('credit-cards.index');
    Route::get('credit-cards/{card}/invoice', [\App\Http\Controllers\CreditCardController::class, 'invoice'])->name('credit-cards.invoice');
    Route::get('credit-cards/{card}/statement-due', [\App\Http\Controllers\CreditCardController::class, 'statementDue'])->name('credit-cards.statement-due');
    Route::post('credit-cards/{card}/pay', [\App\Http\Controllers\CreditCardController::class, 'pay'])->name('credit-cards.pay');
    Route::post('credit-cards/{card}/unpay', [\App\Http\Controllers\CreditCardController::class, 'unpay'])->name('credit-cards.unpay');
    Route::post('credit-cards/{card}/import', [\App\Http\Controllers\CreditCardController::class, 'import'])->name('credit-cards.import');
    Route::post('credit-cards/{card}/link-suggested', [\App\Http\Controllers\CreditCardController::class, 'linkSuggested'])->name('credit-cards.link-suggested');
    Route::post('credit-cards/{card}/clear-import', [\App\Http\Controllers\CreditCardController::class, 'clearImport'])->name('credit-cards.clear-import');
    Route::post('credit-cards/lines/{line}/link', [\App\Http\Controllers\CreditCardController::class, 'link'])->name('credit-cards.lines.link');
    Route::post('credit-cards/lines/{line}/launch', [\App\Http\Controllers\CreditCardController::class, 'launch'])->name('credit-cards.lines.launch');
    Route::post('credit-cards/lines/{line}/ignore', [\App\Http\Controllers\CreditCardController::class, 'ignore'])->name('credit-cards.lines.ignore');
    Route::post('credit-cards/lines/{line}/reopen', [\App\Http\Controllers\CreditCardController::class, 'reopen'])->name('credit-cards.lines.reopen');
    Route::resource('payables', PayableController::class);
    Route::resource('receivables', ReceivableController::class);

    Route::resource('companies', CompanyController::class);
    Route::post('financial-operations/default-plan', [FinancialOperationController::class, 'defaultPlan'])->name('financial-operations.default-plan');
    Route::post('financial-operations/structure', [FinancialOperationController::class, 'structure'])->name('financial-operations.structure');
    Route::resource('financial-operations', FinancialOperationController::class);
    Route::resource('cost-centers', CostCenterController::class);

    Route::resource('stocks', StockController::class);

    Route::get('stock', [StockMovementController::class, 'getStock'])->name('stock.index');
    Route::get('stock/product/{product}', [StockMovementController::class, 'getProductStockDetail'])->name('stock.product-detail');
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'show']);

    Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');
    Route::post('stock-levels/upsert', [StockLevelController::class, 'upsert'])->name('stock-levels.upsert');

    Route::resource('purchase-requests', PurchaseRequestController::class);
    Route::get('purchase-requests/{purchaseRequest}/products', [PurchaseRequestController::class, 'products'])->name('purchase-requests.products');
    Route::patch('purchase-requests/{purchaseRequest}/status', [PurchaseRequestController::class, 'updateStatus'])->name('purchase-requests.update-status');

    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::patch('purchase-orders/{purchase_order}/status', [PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.update-status');

    Route::resource('requisitions', RequisitionController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::get('events/{event}/requisitions', [RequisitionController::class, 'byEvent'])->name('events.requisitions');

    Route::resource('devolutions', DevolutionController::class)->only(['index', 'store', 'show']);
    Route::get('requisitions/{requisition}/devolution', [DevolutionController::class, 'showByRequisition'])->name('requisitions.devolution');

    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');

    Route::get('tasks/form-data', [TaskController::class, 'formData'])->name('tasks.form-data');
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::get('tasks/{task}/comments', [TaskController::class, 'comments'])->name('tasks.comments');
    Route::post('tasks/{task}/comments', [TaskController::class, 'storeComment'])->name('tasks.store-comment');
    Route::delete('tasks/{task}/comments/{comment}', [TaskController::class, 'destroyComment'])->name('tasks.destroy-comment');
    Route::resource('tasks', TaskController::class)->except(['create', 'edit']);
    Route::resource('appointments', AppointmentController::class)->except(['create', 'edit']);

    Route::get('/events/{event}/duplicate', [EventController::class, 'duplicate']);
    Route::get('/events/{event}/history', [EventController::class, 'history'])->name('events.history');

    Route::get('activity-history', [\App\Http\Controllers\ActivityHistoryController::class, 'index'])->name('activity-history.index');
    Route::get('activity-history/filters', [\App\Http\Controllers\ActivityHistoryController::class, 'filters'])->name('activity-history.filters');
});

// Configurações > APIs: serviços de fora e suas chaves (só Administrador).
Route::group(['middleware' => ['auth:api', 'admin']], function () {
    Route::get('api-integrations', [\App\Http\Controllers\ApiIntegrationController::class, 'index'])->name('api-integrations.index');
    Route::get('api-integrations/anthropic/spend', [\App\Http\Controllers\ApiIntegrationController::class, 'anthropicSpend'])->name('api-integrations.anthropic-spend');
    Route::get('api-integrations/anthropic/spend-history', [\App\Http\Controllers\ApiIntegrationController::class, 'anthropicSpendHistory'])->name('api-integrations.anthropic-spend-history');
    Route::put('api-integrations/{key}', [\App\Http\Controllers\ApiIntegrationController::class, 'update'])->name('api-integrations.update');
    Route::delete('api-integrations/{key}', [\App\Http\Controllers\ApiIntegrationController::class, 'destroy'])->name('api-integrations.destroy');
    Route::post('api-integrations/{key}/test', [\App\Http\Controllers\ApiIntegrationController::class, 'test'])->name('api-integrations.test');
});
