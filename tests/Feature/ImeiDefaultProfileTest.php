<?php

use App\Models\ImeiFilter;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('user sees default profile auto-selected on imei list when no active profile set', function () {
    $user = User::factory()->create();

    $columnsOnly = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Cols only',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make', 'model'],
        ],
        'is_default' => true,
    ]);

    $html = $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('id="imei-profile-select"');
    expect($html)->toContain('value="'.$columnsOnly->id.'" selected');
});

test('cannot set default profile when it has other search criteria', function () {
    $user = User::factory()->create();

    $bad = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Has search',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make'],
            'search' => 'NOPE',
        ],
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->post(route('imeis.filter.default'), [
            'profile_id' => $bad->id,
            'is_default' => '1',
        ])
        ->assertRedirect(route('imeis.filter', ['profile_id' => $bad->id]))
        ->assertSessionHas('error');

    expect($bad->fresh()->is_default)->toBeFalse();
});

test('setting a default profile clears other defaults for the user', function () {
    $user = User::factory()->create();

    $a = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'A',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make'],
        ],
        'is_default' => true,
    ]);

    $b = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'B',
        'params' => [
            'scope' => 'selected',
            'columns' => ['model'],
        ],
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->post(route('imeis.filter.default'), [
            'profile_id' => $b->id,
            'is_default' => '1',
        ])
        ->assertRedirect(route('imeis.filter', ['profile_id' => $b->id]))
        ->assertSessionHas('message');

    expect($a->fresh()->is_default)->toBeFalse();
    expect($b->fresh()->is_default)->toBeTrue();
});

test('reset search loads the default profile when one exists', function () {
    $user = User::factory()->create();

    $default = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Default cols',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make', 'model'],
        ],
        'is_default' => true,
    ]);

    $other = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Other',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'OTHER',
        ],
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $other))
        ->assertRedirect();

    $resetResponse = $this->actingAs($user)
        ->get(route('imeis.search.reset'));

    $resetResponse->assertRedirect();
    expect($resetResponse->headers->get('Location'))->toContain('profile_id='.$default->id);
    expect($resetResponse->headers->get('Location'))->toContain('columns');
    expect($resetResponse->headers->get('Location'))->not->toContain('search=');

    $html = $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'selected',
            'columns' => ['make', 'model'],
            'profile_id' => $default->id,
        ]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Searched Profile: "Default cols"', false);
    expect($html)->toContain('value="'.$default->id.'" selected', false);
    expect($html)->not->toContain('value="OTHER"', false);
});

test('reset search on default profile only clears quick search text', function () {
    $user = User::factory()->create();

    $default = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Default cols',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make', 'model'],
        ],
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $default))
        ->assertRedirect();

    $resetResponse = $this->actingAs($user)
        ->get(route('imeis.search.reset', [
            'profile_id' => $default->id,
            'scope' => 'selected',
            'columns' => ['make', 'model'],
            'search' => 'QUICK_ONLY',
        ]));

    $resetResponse->assertRedirect();
    expect($resetResponse->headers->get('Location'))->toContain('profile_id='.$default->id);
    expect($resetResponse->headers->get('Location'))->not->toContain('search=');

    $html = $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'selected',
            'columns' => ['make', 'model'],
            'profile_id' => $default->id,
        ]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Searched Profile: "Default cols"', false);
    expect($html)->toContain('value="'.$default->id.'" selected', false);
    expect($html)->not->toContain('value="QUICK_ONLY"', false);
});

test('reset search loads none profile when no default exists', function () {
    $user = User::factory()->create();

    $profile = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Only profile',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'KEEPME',
        ],
        'is_default' => false,
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $profile))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('imeis.search.reset'))
        ->assertRedirect(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
        ]));

    $html = $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
        ]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('Searched Profile:', false);
    expect($html)->toContain('value="" selected', false);
    expect($html)->not->toContain('value="KEEPME"', false);
});

test('unchecking default profile clears default for the user', function () {
    $user = User::factory()->create();

    $profile = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'A',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make'],
        ],
        'is_default' => true,
    ]);

    $this->actingAs($user)
        ->post(route('imeis.filter.default'), [
            'profile_id' => $profile->id,
            'is_default' => '0',
        ])
        ->assertRedirect(route('imeis.filter'))
        ->assertSessionHas('message');

    expect($profile->fresh()->is_default)->toBeFalse();
});
