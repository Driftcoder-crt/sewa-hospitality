<?php

use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationUser;
use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalMessage;
use App\Modules\Portal\Models\PortalMove;
use App\Modules\Portal\Models\PortalNotification;
use App\Modules\Portal\Models\PortalThread;
use App\Modules\Portal\Services\InvitationService;
use App\Modules\Portal\Services\MoveStageMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // syncRoles needs the seeded role matrix (01-cms §2 / 03-database-schema §1).
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Tenant isolation matrix (04-client-portal.md §8): employee A cannot
 * read org B's anything — exhaustive per-surface. Missing authorization
 * is a 404 (existence stays secret), never a 403.
 */
function portalMember(string $roleInOrg = 'manager', ?Organization $organization = null): User
{
    $user = User::factory()->create(['status' => UserStatus::Active]);
    $user->syncRoles([$roleInOrg === 'employee' ? 'client-employee' : 'client-manager']);
    OrganizationUser::create([
        'organization_id' => ($organization ?? Organization::factory()->create())->id,
        'user_id' => $user->id,
        'role_in_org' => $roleInOrg,
        'joined_at' => now(),
    ]);

    return $user;
}

it('shows employees only their own moves and managers the org board', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $employee = portalMember('employee', $orgA);
    $manager = portalMember('manager', $orgB);

    $own = PortalMove::factory()->create(['organization_id' => $orgA->id, 'employee_user_id' => $employee->id]);
    $sibling = PortalMove::factory()->create(['organization_id' => $orgA->id]);
    $foreign = PortalMove::factory()->create(['organization_id' => $orgB->id]);

    // Employee: own move visible, sibling (same org, other employee) NOT.
    $this->actingAs($employee)->get(route('portal.moves'))
        ->assertOk()
        ->assertSee($own->reference)
        ->assertDontSee($sibling->reference);

    // Manager: org-wide board.
    $this->actingAs($manager)->get(route('portal.moves'))
        ->assertOk()
        ->assertSee($foreign->reference);

    // Employee blocked from the other org's move detail — 404, not 403.
    $this->actingAs($employee)->get(route('portal.moves.show', $foreign))
        ->assertNotFound();
});

it('keeps the document visibility matrix honest per role', function () {
    $org = Organization::factory()->create();
    $employee = portalMember('employee', $org);
    $manager = portalMember('manager', $org);
    $move = PortalMove::factory()->create(['organization_id' => $org->id, 'employee_user_id' => $employee->id]);

    $employeeOnly = PortalDocument::factory()->employeeOnly()->create(['move_record_id' => $move->id, 'organization_id' => $org->id]);
    $managerOnly = PortalDocument::factory()->managerOnly()->create(['move_record_id' => $move->id, 'organization_id' => $org->id]);
    $both = PortalDocument::factory()->create(['move_record_id' => $move->id, 'organization_id' => $org->id]);

    // Employee sees employee + both, never manager-only.
    $this->actingAs($employee)->get(route('portal.documents', $move))
        ->assertOk()
        ->assertSee($employeeOnly->title)
        ->assertSee($both->title)
        ->assertDontSee($managerOnly->title);

    // Manager sees manager + both, never employee-only.
    $this->actingAs($manager)->get(route('portal.documents', $move))
        ->assertOk()
        ->assertSee($managerOnly->title)
        ->assertSee($both->title)
        ->assertDontSee($employeeOnly->title);
});

it('serves signed downloads only to authorized members and audits them', function () {
    $org = Organization::factory()->create();
    $employee = portalMember('employee', $org);
    $outsider = portalMember('manager');
    $move = PortalMove::factory()->create(['organization_id' => $org->id, 'employee_user_id' => $employee->id]);

    Storage::fake('local');
    $media = $move->addMediaFromString('passport bytes')->usingFileName('passport.pdf')->toMediaCollection('portal', 'local');

    $document = PortalDocument::factory()->create([
        'move_record_id' => $move->id,
        'organization_id' => $org->id,
        'media_id' => $media->id,
        'visible_to' => 'both',
    ]);

    $url = $document->downloadUrl();

    // Signature without auth → redirected to login.
    $this->get($url)->assertRedirect(route('login'));

    // Authenticated member with the signed URL → bytes + audit row.
    $this->actingAs($employee)->get($url)
        ->assertOk()
        ->assertDownload();

    $this->assertDatabaseCount('activity_log', 1);

    // Tampered signature → forbidden from the signed middleware.
    $this->actingAs($employee)->get($url.'x')->assertForbidden();

    // A re-signed URL for a foreign org still hits the tenant guard.
    $forged = URL::temporarySignedRoute('portal.documents.download', now()->addMinutes(5), ['document' => $document->id]);
    $this->actingAs($outsider)->get($forged)->assertNotFound();
});

it('runs the stage machine linearly and never backwards', function () {
    $move = PortalMove::factory()->intake()->create();
    $machine = app(MoveStageMachine::class);

    expect($machine->allowedTargets($move))->toBe([MoveStage::Planning]);

    $machine->transition($move, MoveStage::Planning);
    expect($move->refresh()->stage->value)->toBe('planning');

    // Skipping ahead is illegal.
    expect($machine->canTransition($move, MoveStage::Complete))->toBeFalse();

    $this->expectException(InvalidArgumentException::class);
    $machine->transition($move, MoveStage::Complete);
});

it('fires the review request chain exactly once on completion', function () {
    // Listeners run inline (they are queued but the queue driver in
    // tests still records them) — we assert the ENGINE's DB effects.
    $org = Organization::factory()->create();
    $employee = portalMember('employee', $org);
    $move = PortalMove::factory()->create([
        'organization_id' => $org->id,
        'employee_user_id' => $employee->id,
        'stage' => MoveStage::Settling,
    ]);

    app(MoveStageMachine::class)->transition($move, MoveStage::Complete);

    // One review-request chain anchored by the move reference (08 §4.3).
    $this->assertDatabaseCount('review_requests', 1);
    $this->assertDatabaseHas('review_requests', ['move_reference' => $move->reference]);

    // Advancing complete → closed must NOT add a second chain.
    app(MoveStageMachine::class)->transition($move, MoveStage::Closed);
    $this->assertDatabaseCount('review_requests', 1);
});

it('keeps chat threads tenant-scoped and marks consultant replies read', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $employee = portalMember('employee', $orgA);

    $ownThread = PortalThread::factory()->create([
        'organization_id' => $orgA->id,
        'move_record_id' => PortalMove::factory()->create(['organization_id' => $orgA->id, 'employee_user_id' => $employee->id])->id,
    ]);
    $foreignThread = PortalThread::factory()->create([
        'organization_id' => $orgB->id,
        'move_record_id' => PortalMove::factory()->create(['organization_id' => $orgB->id])->id,
    ]);

    PortalMessage::factory()->fromConsultant()->create(['thread_id' => $ownThread->id, 'read_at' => null]);

    $this->actingAs($employee)->get(route('portal.messages.show', $ownThread))
        ->assertOk();

    $this->assertNotNull(PortalMessage::query()->where('thread_id', $ownThread->id)->first()->read_at);

    // The other org's thread does not exist for them.
    $this->actingAs($employee)->get(route('portal.messages.show', $foreignThread))
        ->assertNotFound();
});

it('preserves the message body on validation failure and persists on success', function () {
    Queue::fake();

    $org = Organization::factory()->create();
    $employee = portalMember('employee', $org);
    $thread = PortalThread::factory()->create([
        'organization_id' => $org->id,
        'move_record_id' => PortalMove::factory()->create(['organization_id' => $org->id, 'employee_user_id' => $employee->id])->id,
    ]);

    // Validation failure keeps the typed body (04 doc §6 — never lost typing).
    $this->actingAs($employee)
        ->from(route('portal.messages.show', $thread))
        ->post(route('portal.messages.store', $thread), ['body' => ''])
        ->assertRedirect(route('portal.messages.show', $thread))
        ->assertSessionHasErrors('body');

    $this->actingAs($employee)
        ->post(route('portal.messages.store', $thread), ['body' => 'hello'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('portal_messages', ['thread_id' => $thread->id, 'body' => 'hello', 'sender_role' => 'client']);
});

it('supports the invite flow: invited user sets a password and lands active', function () {
    Queue::fake();
    Mail::fake();

    $org = Organization::factory()->create();
    $service = app(InvitationService::class);

    $result = $service->invite($org, 'newbie@example.com', 'New Hire', 'manager');

    expect($result['created'])->toBeTrue();

    // The invited user exists with invited status and the portal role.
    $invited = User::query()->where('email', 'newbie@example.com')->first();
    expect($invited->status->value)->toBe(UserStatus::Invited->value)
        ->and($invited->hasRole('client-manager'))->toBeTrue();

    // Signed accept URL works and completes the activation.
    $this->get($result['url'])
        ->assertOk()
        ->assertSee('Set a password');

    $this->post($result['url'], [
        'password' => 'S3cure-Passphrase!',
        'password_confirmation' => 'S3cure-Passphrase!',
    ])->assertRedirect(route('portal.dashboard'));

    expect($invited->refresh()->status->value)->toBe(UserStatus::Active->value);
});

it('rejects spent invitations with an honest expired page', function () {
    $org = Organization::factory()->create();
    $service = app(InvitationService::class);
    $service->invite($org, 'gone@example.com', 'Gone', 'employee');

    $invited = User::query()->where('email', 'gone@example.com')->first();
    $token = hash('sha256', $invited->id.'|'.$invited->email.'|'.(string) config('app.key'));

    // Complete the invite → token burned (status no longer invited).
    $service->accept($invited, 'S3cure-Passphrase!');

    $url = URL::temporarySignedRoute('portal.invitations.accept', now()->addMinutes(5), ['token' => $token]);
    $this->get($url)->assertOk()->assertSee('expired');
});

it('delivers notifications and marks them read', function () {
    $org = Organization::factory()->create();
    $manager = portalMember('manager', $org);

    $notifications = PortalNotification::factory()->count(3)->create(['user_id' => $manager->id]);

    $this->actingAs($manager)->get(route('portal.notifications'))
        ->assertOk()
        ->assertSee($notifications->first()->title);

    $this->actingAs($manager)->post(route('portal.notifications.read', $notifications->first()))
        ->assertRedirect();

    expect($notifications->first()->refresh()->read_at)->not->toBeNull();

    $this->actingAs($manager)->post(route('portal.notifications.read-all'))->assertRedirect();

    expect(PortalNotification::query()->forUser((string) $manager->id)->unread()->count())->toBe(0);
});

it('hides invoices from employees and shows them to billing roles', function () {
    $org = Organization::factory()->create();
    $employee = portalMember('employee', $org);
    $billing = portalMember('billing', $org);

    Invoice::factory()->sent()->create(['organization_id' => $org->id]);

    $this->actingAs($employee)->get(route('portal.invoices'))->assertNotFound();

    $this->actingAs($billing)->get(route('portal.invoices'))
        ->assertOk()
        ->assertSee(Invoice::query()->first()->number);
});
