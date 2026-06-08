<?php

use App\Models\Imei;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('guests are redirected from settings', function () {
    $this->get(route('settings.index'))->assertRedirect(route('login'));
});

test('login page does not show authenticated app menu for guests', function () {
    $html = $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Login', false)
        ->assertSee('Register', false)
        ->assertDontSee('id="settings-menu-button"', false)
        ->getContent();

    expect($html)->not->toContain('>IMEI</')
        ->and($html)->not->toContain('>Contacts</')
        ->and($html)->not->toContain('>Notes</');
});

test('guests are redirected from add imei form', function () {
    $this->get(route('imeis.create'))->assertRedirect(route('login'));
});

test('verified users can view settings and add imei form', function () {
    $user = User::factory()->create();

    $settingsHtml = $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertSee('Make', false)
        ->assertSee('Models', false)
        ->assertDontSee('Sale types', false)
        ->assertDontSee('Change password', false)
        ->getContent();

    expect($settingsHtml)->not->toContain('w-full max-w-none');

    $this->actingAs($user)
        ->get(route('settings.makes.index'))
        ->assertSuccessful()
        ->assertSee('Make', false)
        ->assertSee('All makes', false);

    $this->actingAs($user)
        ->get(route('settings.models.index'))
        ->assertSuccessful()
        ->assertSee('Models', false)
        ->assertSee('Select make', false);

    $this->actingAs($user)
        ->get(route('settings.locations.index'))
        ->assertSuccessful()
        ->assertSee('Locations', false)
        ->assertSee('All locations', false);

    $this->actingAs($user)
        ->get(route('settings.types.index'))
        ->assertSuccessful()
        ->assertSee('Types', false)
        ->assertSee('All types', false);

    $this->actingAs($user)
        ->get(route('settings.status.index'))
        ->assertSuccessful()
        ->assertSee('Status', false)
        ->assertSee('All statuses', false);

    $this->actingAs($user)
        ->get(route('settings.sale-types.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/settings/unknown-section')
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('imeis.create', ['embedded' => 1]))
        ->assertSuccessful()
        ->assertSee('Add IMEI', false)
        ->assertDontSee('id="settings-menu-button"', false)
        ->assertDontSee('id="user-menu-button"', false);
});

test('embedded imei edit with close signals parent dialog to close', function () {
    Schema::create('imei', function (Blueprint $table) {
        $table->id();
        $table->dateTime('date_in')->nullable();
        $table->string('cash_stock_type')->default('');
        $table->dateTime('date_updated')->nullable();
        $table->string('make')->default('');
        $table->string('model')->default('');
        $table->string('sn')->default('');
        $table->string('imei');
        $table->string('location')->default('');
        $table->string('type')->default('');
        $table->string('status')->default('');
        $table->text('notes')->nullable();
        $table->string('phonenumber')->default('');
        $table->string('ref')->default('');
        $table->string('staff')->default('');
        $table->string('item_code')->default('');
        $table->string('ourON')->default('');
        $table->string('salesON')->default('');
        $table->string('cost_excl')->default('');
        $table->integer('selling_price')->nullable();
    });

    $user = User::factory()->create();
    $imei = Imei::query()->create(['imei' => 'embeddedclose1']);

    $this->actingAs($user)
        ->get(route('imeis.edit', $imei).'?embedded=1&close=1')
        ->assertSuccessful()
        ->assertSee('const embeddedCloseOnLoad = true', false);
});

test('role 4 users can access sale type settings from imei settings', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertSee('Sale types', false);

    $this->actingAs($user)
        ->get(route('settings.sale-types.index'))
        ->assertSuccessful()
        ->assertSee('Sale types', false);
});
