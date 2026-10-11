<?php

use App\Http\Controllers\Crm\CustomerController;
use App\Http\Controllers\Crm\EstimateController;
use App\Http\Controllers\Crm\InvoiceController;
use App\Http\Controllers\Crm\OrganizationSettingController;
use App\Http\Controllers\Crm\PublicIntakeController;
use App\Http\Controllers\Crm\ReportController;
use App\Http\Controllers\Crm\ScheduleController;
use App\Http\Controllers\Crm\ServiceRequestController;
use App\Http\Controllers\Crm\TechnicianWorkspaceController;
use App\Http\Controllers\Crm\WorkOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('request-service', [PublicIntakeController::class, 'create'])->name('public-intake.create');
Route::post('request-service', [PublicIntakeController::class, 'store'])
    ->middleware('throttle:5,5')
    ->name('public-intake.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('team.dashboard');

        Route::resource('customers', CustomerController::class)->except(['destroy']);

        Route::get('requests', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [ServiceRequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');

        Route::get('estimates', [EstimateController::class, 'index'])->name('estimates.index');
        Route::get('estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
        Route::post('estimates', [EstimateController::class, 'store'])->name('estimates.store');
        Route::get('estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
        Route::post('estimates/{estimate}/send', [EstimateController::class, 'send'])->name('estimates.send');
        Route::post('estimates/{estimate}/approve', [EstimateController::class, 'approve'])->name('estimates.approve');
        Route::post('estimates/{estimate}/reject', [EstimateController::class, 'reject'])->name('estimates.reject');
        Route::post('estimates/{estimate}/convert', [EstimateController::class, 'convert'])->name('estimates.convert');

        Route::get('work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('work-orders/create', [WorkOrderController::class, 'create'])->name('work-orders.create');
        Route::post('work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
        Route::get('work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::post('work-orders/{workOrder}/schedule', [WorkOrderController::class, 'schedule'])->name('work-orders.schedule');
        Route::post('work-orders/{workOrder}/assign', [WorkOrderController::class, 'assign'])->name('work-orders.assign');
        Route::post('work-orders/{workOrder}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');
        Route::post('work-orders/{workOrder}/pause', [WorkOrderController::class, 'pause'])->name('work-orders.pause');
        Route::post('work-orders/{workOrder}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
        Route::post('work-orders/{workOrder}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');
        Route::post('work-orders/{workOrder}/invoice', [WorkOrderController::class, 'invoice'])->name('work-orders.invoice');

        Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::get('my-jobs', [TechnicianWorkspaceController::class, 'index'])->name('technician.index');
        Route::get('my-jobs/{workOrder}', [TechnicianWorkspaceController::class, 'show'])->name('technician.show');

        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'pay'])->name('invoices.pay');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('organization-settings', [OrganizationSettingController::class, 'edit'])->name('organization-settings.edit');
        Route::put('organization-settings', [OrganizationSettingController::class, 'update'])->name('organization-settings.update');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
