<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['session.driver' => 'array']);

    Schema::create('user', function (Blueprint $table) {
        $table->unsignedBigInteger('EmployeeId')->primary();
        $table->string('FirstName')->nullable();
        $table->string('MiddleName')->nullable();
        $table->string('LastName')->nullable();
        $table->string('Suffix')->nullable();
        $table->string('Role');
        $table->string('PhoneNumber')->nullable();
        $table->string('Address')->nullable();
        $table->date('Birthday')->nullable();
        $table->string('Gender')->nullable();
        $table->string('Username')->nullable();
        $table->boolean('is_active')->default(true);
    });

    DB::table('user')->insert([
        'EmployeeId' => 1,
        'FirstName' => 'Test',
        'LastName' => 'Admin',
        'Role' => 'Admin',
        'Username' => 'test-admin',
        'is_active' => true,
    ]);
});

afterEach(function () {
    Schema::dropIfExists('user');
});

test('profile navigation reuses the user loaded by session authentication', function () {
    $userLookups = 0;

    DB::listen(function ($query) use (&$userLookups) {
        $sql = strtolower($query->sql);

        if (str_starts_with($sql, 'select') && str_contains($sql, 'from "user"')) {
            $userLookups++;
        }
    });

    $this->withSession([
        'user_id' => 1,
        'role' => 'Admin',
    ])->get('/admin/profile')
        ->assertOk()
        ->assertSee('Test Admin');

    expect($userLookups)->toBe(1);
});
