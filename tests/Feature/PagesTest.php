<?php

use App\Models\User;

it('halaman utama bisa dibuka setelah login', function (string $route) {
    $this->actingAs(User::factory()->create())
        ->get(route($route))
        ->assertOk();
})->with(['dashboard', 'import', 'activities', 'groups', 'departments.index']);

it('tamu diarahkan ke login', function () {
    $this->get(route('activities'))->assertRedirect(route('login'));
});