<?php

use App\Models\Activity;
use App\Models\CompanyTenant;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/*
| All property managements share one database. These tests make sure that no property management
| can reach records of another one – through queries, portal URLs, the admin panel, the API or IDs
| submitted in forms.
*/

beforeEach(function () {
    $this->seed(InitialSetupSeeder::class);
});

/**
 * Creates a property management with an administrator, a renter and one record of each kind.
 *
 * @return array{tenant: CompanyTenant, admin: User, renterUser: User, property: Property, unit: Unit, ticket: Ticket, document: Document}
 */
function propertyManagement(string $id): array
{
    $tenant = CompanyTenant::query()->create(['id' => $id, 'company_name' => "Verwaltung {$id}"]);

    $records = $tenant->run(function () use ($tenant, $id): array {
        $admin = User::factory()->create(['email' => "admin@{$id}.ch"]);
        $admin->assignRole('Administrator');

        $renterUser = User::factory()->create(['email' => "mieter@{$id}.ch"]);
        $renterUser->assignRole('Mieter');
        $renter = Tenant::factory()->create(['user_id' => $renterUser->id]);

        $property = Property::factory()->create(['name' => "Liegenschaft {$id}"]);
        $unit = Unit::factory()->create(['property_id' => $property->id, 'current_renter_id' => $renter->id]);
        $ticket = Ticket::factory()->create(['title' => "Ticket {$id}", 'tenant_id' => $renter->id, 'created_by' => $renterUser->id]);
        $document = Document::factory()->create([
            'documentable_type' => Tenant::class,
            'documentable_id' => $renter->id,
            'filename' => "Vertrag {$id}.pdf",
        ]);

        return compact('tenant', 'admin', 'renterUser', 'property', 'unit', 'ticket', 'document');
    });

    // Real requests start without an active property management; the user determines it.
    tenancy()->end();

    return $records;
}

test('records of one property management are invisible to another one', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');

    $alpha['tenant']->run(function () use ($beta) {
        expect(Property::pluck('name')->all())->toContain('Liegenschaft alpha')->not->toContain('Liegenschaft beta')
            ->and(Property::find($beta['property']->id))->toBeNull()
            ->and(Ticket::find($beta['ticket']->id))->toBeNull()
            ->and(User::where('email', 'admin@beta.ch')->exists())->toBeFalse();
    });

    // Without an active property management (e.g. console) all records are visible.
    tenancy()->end();
    expect(Property::whereIn('name', ['Liegenschaft alpha', 'Liegenschaft beta'])->count())->toBe(2);
});

test('new records are assigned to the active property management, even without model events', function () {
    $alpha = CompanyTenant::query()->create(['id' => 'alpha']);

    $property = $alpha->run(fn () => Model::withoutEvents(fn () => Property::factory()->create()));

    expect($property->company_tenant_id)->toBe('alpha');
});

test('renters cannot open tickets or documents of another property management by URL', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');

    $this->actingAs($alpha['renterUser'])
        ->get("/portal/tickets/{$beta['ticket']->id}")
        ->assertNotFound();

    $this->actingAs($alpha['renterUser'])
        ->get("/portal/documents/{$beta['document']->id}/download")
        ->assertNotFound();
});

test('administrators only see and edit their own property management in the admin panel', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');

    $this->actingAs($alpha['admin'])
        ->get('/admin/properties')
        ->assertOk()
        ->assertSee('Liegenschaft alpha')
        ->assertDontSee('Liegenschaft beta');

    $this->actingAs($alpha['admin'])
        ->get("/admin/properties/{$beta['property']->id}/edit")
        ->assertNotFound();
});

test('the api only returns records of the property management of the token', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');
    $token = $alpha['admin']->createToken('api')->plainTextToken;

    $this->withToken($token)->getJson('/api/properties')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Liegenschaft alpha'])
        ->assertJsonMissing(['name' => 'Liegenschaft beta']);

    $this->withToken($token)->getJson("/api/properties/{$beta['property']->id}")->assertNotFound();
});

test('ids of another property management are rejected in forms', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');

    $this->withToken($alpha['admin']->createToken('api')->plainTextToken)
        ->postJson('/api/units', [
            'property_id' => $beta['property']->id,
            'unit_number' => 'X1',
            'floor' => 1,
            'size_sqm' => 50,
            'rooms' => 2,
            'rent_amount' => 1000,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('property_id');
});

test('invoice numbers are unique per property management, not across the platform', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');

    $alpha['tenant']->run(fn () => Invoice::factory()->create(['invoice_number' => 'R-2026-001']));

    $this->withToken($beta['admin']->createToken('api')->plainTextToken)
        ->postJson('/api/invoices', [
            'invoice_number' => 'R-2026-001',
            'amount' => 120,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ])
        ->assertCreated();

    $this->withToken($beta['admin']->createToken('api')->plainTextToken)
        ->postJson('/api/invoices', [
            'invoice_number' => 'R-2026-001',
            'amount' => 120,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ])
        ->assertJsonValidationErrors('invoice_number');
});

test('the audit log only shows entries of the own property management', function () {
    $alpha = propertyManagement('alpha');
    $beta = propertyManagement('beta');

    $alpha['tenant']->run(fn () => $alpha['ticket']->update(['status' => 'closed']));
    $beta['tenant']->run(fn () => $beta['ticket']->update(['status' => 'closed']));

    $alpha['tenant']->run(function () use ($alpha, $beta) {
        expect(Activity::where('subject_type', Ticket::class)->pluck('subject_id')->all())
            ->toContain($alpha['ticket']->id)
            ->not->toContain($beta['ticket']->id);
    });
});

test('a deactivated property management is locked out of portal, admin panel and api', function () {
    $alpha = propertyManagement('alpha');
    $token = $alpha['admin']->createToken('api')->plainTextToken;
    $alpha['tenant']->update(['is_active' => false]);
    tenancy()->end();

    $this->actingAs($alpha['renterUser'])->get('/portal/dashboard')->assertForbidden();
    $this->actingAs($alpha['admin'])->get('/admin')->assertForbidden();
    $this->withToken($token)->getJson('/api/properties')->assertForbidden();
});

test('users cannot register themselves through the api', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Fremder',
        'email' => 'fremder@example.ch',
        'password' => 'Passwort12345',
        'password_confirmation' => 'Passwort12345',
    ])->assertNotFound();
});

test('files of each property management are stored in their own directory', function () {
    $alpha = CompanyTenant::query()->create(['id' => 'filesa']);
    $beta = CompanyTenant::query()->create(['id' => 'filesb']);
    $path = 'documents/mietvertrag-'.uniqid().'.pdf';

    $alpha->run(function () use ($path) {
        Storage::disk('public')->put($path, 'vertraulich');

        expect(Storage::disk('public')->path($path))->toContain('tenant_filesa');
    });

    $beta->run(fn () => expect(Storage::disk('public')->exists($path))->toBeFalse());

    $alpha->run(fn () => Storage::disk('public')->delete($path));
});

test('the tenant asset route that serves files without authentication is disabled', function () {
    expect(Route::has('stancl.tenancy.asset'))->toBeFalse();
});

test('migrate storage command copies only the files of the property management into its storage', function () {
    $tenant = CompanyTenant::query()->create(['id' => 'migr'.rand(1000, 9999)]);
    $referencedPath = 'documents/vertrag-'.uniqid().'.pdf';
    $foreignPath = 'documents/fremd-'.uniqid().'.pdf';

    // The formerly shared disk is the storage without an active property management.
    tenancy()->end();
    Storage::disk('public')->put($referencedPath, 'mietvertrag');
    Storage::disk('public')->put($foreignPath, 'gehoert anderer verwaltung');

    DB::table('documents')->insert([
        ['company_tenant_id' => $tenant->id, 'documentable_type' => Property::class, 'documentable_id' => 1, 'filename' => 'Mietvertrag.pdf', 'path' => $referencedPath],
        ['company_tenant_id' => 'test', 'documentable_type' => Property::class, 'documentable_id' => 1, 'filename' => 'Fremd.pdf', 'path' => $foreignPath],
    ]);

    $this->artisan('tenants:migrate-storage', ['--tenant' => $tenant->id])->assertSuccessful();

    $tenant->run(function () use ($referencedPath, $foreignPath) {
        expect(Storage::disk('public')->get($referencedPath))->toBe('mietvertrag')
            ->and(Storage::disk('public')->exists($foreignPath))->toBeFalse();

        Storage::disk('public')->delete($referencedPath);
    });

    Storage::disk('public')->delete([$referencedPath, $foreignPath]);
});

test('error reports are tagged with the property management', function () {
    $tenant = CompanyTenant::query()->create(['id' => 'sentry'.rand(1000, 9999)]);
    tenancy()->end();

    $currentTags = function (): array {
        $tags = [];
        SentrySdk::getCurrentHub()->configureScope(function (Scope $scope) use (&$tags) {
            $tags = $scope->applyToEvent(Event::createEvent())?->getTags() ?? [];
        });

        return $tags;
    };

    $tenant->run(fn () => expect($currentTags())->toHaveKey('tenant', $tenant->id));

    expect($currentTags())->not->toHaveKey('tenant');
});

test('the landing page never lists the property managements', function () {
    CompanyTenant::query()->create(['id' => 'geheim', 'company_name' => 'Geheime Verwaltung GmbH']);

    foreach ([false, true] as $isDemoMode) {
        config(['app.demo_mode' => $isDemoMode]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Geheime Verwaltung GmbH');
    }
});
