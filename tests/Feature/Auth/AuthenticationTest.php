<?php

use App\Enums\UserRole;
use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('admin can login and access dashboard', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('warehouse worker can login and access dashboard', function () {
    $worker = workerUser();

    $this->actingAs($worker)
        ->get(route('dashboard'))
        ->assertOk();
});

test('admin role is correctly assigned', function () {
    $admin = adminUser();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->isWarehouseWorker())->toBeFalse();
});

test('warehouse_worker role is correctly assigned', function () {
    $worker = workerUser();

    expect($worker->role)->toBe(UserRole::WarehouseWorker)
        ->and($worker->isAdmin())->toBeFalse()
        ->and($worker->isWarehouseWorker())->toBeTrue();
});
