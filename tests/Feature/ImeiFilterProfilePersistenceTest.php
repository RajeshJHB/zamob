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

test('active imei search profile persists for the user until changed', function () {
    $user = User::factory()->create();

    $filter = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'My Default Profile',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'PERSIST123',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $filter))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertSee('Searched Profile: "My Default Profile"', false)
        ->assertSee('value="PERSIST123"', false);
});

test('search from filter auto loads profile when dropdown differs from active profile', function () {
    $user = User::factory()->create();

    $profileA = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Profile A',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'PROFILE_A',
        ],
    ]);

    $profileB = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Profile B',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'PROFILE_B',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $profileA))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'from_filter' => '1',
            'profile_id' => $profileB->id,
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'STALE_FORM_VALUE',
        ]))
        ->assertRedirect(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'PROFILE_B',
            'profile_id' => $profileB->id,
        ]));

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'PROFILE_B',
            'profile_id' => $profileB->id,
        ]))
        ->assertSuccessful()
        ->assertSee('Searched Profile: "Profile B"', false)
        ->assertSee('value="PROFILE_B"', false);
});

test('search from filter with none selected clears active profile', function () {
    $user = User::factory()->create();

    $filter = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'To Clear',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'CLEARED',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $filter))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'from_filter' => '1',
            'profile_id' => '',
            'scope' => 'selected',
            'columns' => ['make', 'model'],
            'date_scope' => 'all',
            'search' => 'MANUAL_SEARCH',
        ]))
        ->assertRedirect(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'MANUAL_SEARCH',
        ]));

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'MANUAL_SEARCH',
        ]))
        ->assertSuccessful()
        ->assertDontSee('Searched Profile:', false)
        ->assertSee('value="MANUAL_SEARCH"', false)
        ->assertSee('Date in', false)
        ->assertSee('Location', false);

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertDontSee('Searched Profile:', false);
});

test('search from filter with none clears column restrictions from previous profile', function () {
    $user = User::factory()->create();

    $filter = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Few Columns',
        'params' => [
            'scope' => 'selected',
            'columns' => ['make', 'model'],
            'date_scope' => 'all',
        ],
    ]);

    $this->actingAs($user)
        ->get(route('imeis.filter.apply', $filter))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'from_filter' => '1',
            'profile_id' => '',
            'scope' => 'selected',
            'columns' => ['make', 'model'],
            'date_scope' => 'all',
        ]))
        ->assertRedirect(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
        ]));

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
        ]))
        ->assertSuccessful()
        ->assertDontSee('Searched Profile:', false)
        ->assertSee('Date in', false)
        ->assertSee('Location', false)
        ->assertSee('Staff', false);
});

test('search from filter does not redirect loop when profile params contain from_filter', function () {
    $user = User::factory()->create();

    $filter = ImeiFilter::query()->create([
        'user_id' => $user->id,
        'name' => 'Saved With Meta',
        'params' => [
            'scope' => 'all',
            'date_scope' => 'all',
            'search' => 'LOOP_TEST',
            'from_filter' => '1',
        ],
    ]);

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('imeis.index', [
            'from_filter' => '1',
            'profile_id' => $filter->id,
            'scope' => 'all',
            'date_scope' => 'all',
        ]))
        ->assertSuccessful()
        ->assertSee('Searched Profile: "Saved With Meta"', false)
        ->assertSee('value="LOOP_TEST"', false);
});
