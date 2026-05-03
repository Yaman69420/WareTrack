<?php

test('warehouse worker cannot access admin-only routes', function () {
    $worker = workerUser();

    $this->actingAs($worker)
        ->get(route('products.index'))
        ->assertForbidden();
});

test('admin can access admin-only routes', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('products.index'))
        ->assertOk();
});

test('guest cannot access admin routes', function () {
    $this->get(route('products.index'))
        ->assertRedirect(route('login'));
});
