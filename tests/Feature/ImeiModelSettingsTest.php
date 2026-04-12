<?php

use App\Models\ImeiMake;
use App\Models\ImeiModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from models settings', function () {
    $this->get(route('settings.models.index'))->assertRedirect(route('login'));
    $this->post(route('settings.models.store'), [
        'make' => 'X',
        'model' => 'Y',
    ])->assertRedirect(route('login'));
});

test('invalid make query redirects with error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.models.index', ['make' => 'NotInTable']))
        ->assertRedirect(route('settings.models.index'))
        ->assertSessionHas('error');
});

test('verified user sees models for selected make in alphabetical order', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Acme']);
    ImeiModel::factory()->create(['make' => 'Acme', 'model' => 'Zulu', 'serial' => '1']);
    ImeiModel::factory()->create(['make' => 'Acme', 'model' => 'Alpha', 'serial' => '2']);

    $response = $this->actingAs($user)
        ->get(route('settings.models.index', ['make' => 'Acme']));

    $response->assertSuccessful();
    $alphaPos = strpos($response->getContent(), 'Alpha');
    $zuluPos = strpos($response->getContent(), 'Zulu');
    expect($alphaPos)->toBeLessThan($zuluPos);
});

test('models list only exact make match', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'BrandA']);
    ImeiMake::factory()->create(['make' => 'BrandB']);
    ImeiModel::factory()->create(['make' => 'BrandA', 'model' => 'OnlyA', 'serial' => '']);
    ImeiModel::factory()->create(['make' => 'BrandB', 'model' => 'OnlyB', 'serial' => '']);

    $response = $this->actingAs($user)
        ->get(route('settings.models.index', ['make' => 'BrandA']));

    $response->assertSuccessful()->assertSee('OnlyA', false)->assertDontSee('OnlyB', false);
});

test('verified user can add a model', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Sony']);

    $this->actingAs($user)
        ->post(route('settings.models.store'), [
            'make' => 'Sony',
            'model' => '  Xperia  ',
            'serial' => ' SN-1 ',
        ])
        ->assertRedirect(route('settings.models.index', ['make' => 'Sony']));

    $row = ImeiModel::query()->where('make', 'Sony')->first();
    expect($row)->not->toBeNull()
        ->and($row->model)->toBe('Xperia')
        ->and($row->serial)->toBe('SN-1');
});

test('store rejects make not in imei_make', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.models.store'), [
            'make' => 'UnknownMake',
            'model' => 'X',
        ])
        ->assertSessionHasErrors('make');
});

test('verified user can update a model', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'LG']);
    $row = ImeiModel::factory()->create(['make' => 'LG', 'model' => 'Old', 'serial' => 's1']);

    $this->actingAs($user)
        ->put(route('settings.models.update', $row), [
            'model' => 'New',
            'serial' => 's2',
        ])
        ->assertRedirect(route('settings.models.index', ['make' => 'LG']));

    expect($row->fresh()->model)->toBe('New')
        ->and($row->fresh()->serial)->toBe('s2')
        ->and($row->fresh()->make)->toBe('LG');
});

test('verified user can delete a model', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Dell']);
    $row = ImeiModel::factory()->create(['make' => 'Dell', 'model' => 'X', 'serial' => '']);

    $this->actingAs($user)
        ->delete(route('settings.models.destroy', $row))
        ->assertRedirect(route('settings.models.index', ['make' => 'Dell']));

    expect(ImeiModel::query()->find($row->id))->toBeNull();
});
