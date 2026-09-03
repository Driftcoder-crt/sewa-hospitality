<?php

use App\Modules\Careers\Mail\ApplicationAckMail;
use App\Modules\Careers\Mail\ApplicationReceivedMail;
use App\Modules\Careers\Mail\ApplicationStatusMail;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPosting;
use App\Modules\Leads\Mail\LeadAckMail;
use App\Modules\Leads\Mail\LeadReceivedMail;
use App\Modules\Leads\Mail\NewsletterConfirmMail;
use App\Modules\Leads\Mail\OpsAlertMail;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Models\NewsletterSubscriber;
use App\Support\Mail\MailDispatcher;
use App\Support\Mail\MailLog;
use App\Support\Mail\OpsDigestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
});

it('renders every money-path template without crashing (en)', function (): void {
    $lead = Lead::factory()->quote()->create();
    $subscriber = NewsletterSubscriber::factory()->create();
    $posting = JobPosting::factory()->create();
    $application = JobApplication::factory()->for($posting, 'posting')->create();

    // Body-first assertions: "We received your enquiry" is the SUBJECT
    // line; render() returns the body, so pin the body's own opening
    // (the intro with :kind already substituted).
    expect((new LeadAckMail($lead))->render())
        ->toContain('We have received your quote request')
        ->toContain($lead->name);

    expect((new LeadReceivedMail($lead))->render())
        ->toContain($lead->email)
        ->toContain('SLA due');

    expect((new NewsletterConfirmMail($subscriber))->render())
        ->toContain($subscriber->confirmUrl());

    // Same subject-vs-body distinction as the lead ack above.
    expect((new ApplicationAckMail($application))->render())
        ->toContain('Thank you for applying to Sewa Hospitality');

    expect((new ApplicationReceivedMail($application, 'https://signed.example/resume'))->render())
        ->toContain('Signed link');

    expect((new ApplicationStatusMail($application, 'Interview'))->render())
        ->toContain('Interview');

    expect((new OpsDigestMail(['Leads' => '5', 'SLA' => '0'], ['All quiet.']))->render())
        ->toContain('All quiet.');
});

it('renders the ops alert template with subject and deep link', function (): void {
    $html = (new OpsAlertMail(
        'SLA breached — lead overdue without response',
        ['Lead: Test User', 'Deadline passed'],
        'https://sewahospitality.com/admin/leads/1',
        'Open lead in CRM',
    ))->render();

    expect($html)->toContain('SLA breached')
        ->toContain('Test User');
});

it('never double-sends: the dispatcher is idempotent per key', function (): void {
    $lead = Lead::factory()->create();

    $ack = new LeadAckMail($lead);

    MailDispatcher::send("lead.ack:{$lead->getKey()}", 'lead.ack', $ack);
    $replayed = MailDispatcher::send("lead.ack:{$lead->getKey()}", 'lead.ack', $ack);

    expect($replayed)->toBeTrue();

    Mail::assertSent(LeadAckMail::class, 1);
    expect(MailLog::query()->where('key', "lead.ack:{$lead->getKey()}")->count())->toBe(1);
});

it('hashes recipients in the mail log (privacy lock #5)', function (): void {
    $lead = Lead::factory()->create();

    MailDispatcher::send("lead.ack:{$lead->getKey()}", 'lead.ack', new LeadAckMail($lead));

    $row = MailLog::query()->where('key', "lead.ack:{$lead->getKey()}")->sole();

    expect($row->recipient_hash)->not->toContain($lead->email)
        ->and(strlen((string) $row->recipient_hash))->toBe(64);
});
