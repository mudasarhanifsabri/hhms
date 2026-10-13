<?php

namespace Tests\Feature;

use App\Models\EmailDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailDeliveryTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_outgoing_email_is_recorded_as_sent(): void
    {
        config()->set('mail.default', 'array');

        Mail::raw('Your booking is ready.', function ($message) {
            $message->to('Guest@Example.com')->subject('Booking confirmation BK-100');
        });

        $delivery = EmailDelivery::query()->sole();

        $this->assertSame('system_email', $delivery->type);
        $this->assertSame('sent', $delivery->status);
        $this->assertSame(['guest@example.com'], $delivery->recipients);
        $this->assertSame('Booking Communication', $delivery->purpose);
        $this->assertNotNull($delivery->queued_at);
        $this->assertNotNull($delivery->sent_at);
    }

    public function test_an_existing_delivery_header_does_not_create_a_duplicate(): void
    {
        config()->set('mail.default', 'array');

        $delivery = EmailDelivery::create([
            'type' => 'owner_statement',
            'status' => 'queued',
            'recipients' => ['owner@example.com'],
            'subject' => 'Owner Statement',
            'purpose' => 'Monthly Statement',
            'queued_at' => now(),
        ]);

        Mail::raw('Statement attached.', function ($message) use ($delivery) {
            $message->to('owner@example.com')->subject('Owner Statement');
            $message->getSymfonyMessage()->getHeaders()->addTextHeader('X-RMS-Delivery-ID', $delivery->id);
        });

        $this->assertSame(1, EmailDelivery::count());
        $this->assertSame('sent', $delivery->fresh()->status);
    }
}
