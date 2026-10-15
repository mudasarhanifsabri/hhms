<?php

namespace App\Support;

use App\Models\EmailDelivery;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class OutboundEmailTracker
{
    private const DELIVERY_HEADER = 'X-RMS-Delivery-ID';

    private const TYPE_HEADER = 'X-RMS-Mail-Type';

    private const LANDLORD_HEADER = 'X-RMS-Landlord-ID';

    private const PROPERTY_HEADER = 'X-RMS-Property-ID';

    public static function sending(MessageSending $event): void
    {
        try {
            if (! Schema::hasTable('email_deliveries')) {
                return;
            }

            $message = $event->message;
            $deliveryId = self::headerValue($message, self::DELIVERY_HEADER);

            if ($deliveryId && EmailDelivery::whereKey($deliveryId)->exists()) {
                return;
            }

            $recipients = self::recipients($message);

            if ($recipients === []) {
                return;
            }

            $delivery = EmailDelivery::create([
                'type' => self::headerValue($message, self::TYPE_HEADER) ?: 'system_email',
                'status' => 'queued',
                'recipients' => $recipients,
                'subject' => $message->getSubject() ?: '(No subject)',
                'purpose' => self::purpose($message->getSubject()),
                'landlord_id' => self::headerValue($message, self::LANDLORD_HEADER) ?: null,
                'property_id' => self::headerValue($message, self::PROPERTY_HEADER) ?: null,
                'created_by' => Auth::id(),
                'queued_at' => now(),
            ]);

            $message->getHeaders()->addTextHeader(self::DELIVERY_HEADER, $delivery->getKey());
        } catch (Throwable $exception) {
            // Logging must never prevent the business email itself from being sent.
            Log::warning('Unable to create the RMS email delivery log.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public static function sent(MessageSent $event): void
    {
        try {
            if (! Schema::hasTable('email_deliveries')) {
                return;
            }

            $deliveryId = self::headerValue($event->message, self::DELIVERY_HEADER);

            if (! $deliveryId) {
                return;
            }

            EmailDelivery::whereKey($deliveryId)->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'failure_reason' => null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to mark the RMS email delivery log as sent.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private static function recipients(Email $message): array
    {
        return collect([...$message->getTo(), ...$message->getCc(), ...$message->getBcc()])
            ->filter(fn ($address) => $address instanceof Address)
            ->map(fn (Address $address) => strtolower($address->getAddress()))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function headerValue(Email $message, string $name): ?string
    {
        $header = $message->getHeaders()->get($name);

        return $header ? trim($header->getBodyAsString()) : null;
    }

    private static function purpose(?string $subject): string
    {
        $subject = trim((string) $subject);

        return match (true) {
            str_contains(strtolower($subject), 'password') => 'Password & Account',
            str_contains(strtolower($subject), 'statement') => 'Owner Statement',
            str_contains(strtolower($subject), 'invoice') => 'Invoice',
            str_contains(strtolower($subject), 'booking') => 'Booking Communication',
            str_contains(strtolower($subject), 'checkout') => 'Checkout Reminder',
            str_contains(strtolower($subject), 'payout') => 'Owner Payout',
            default => 'System Email',
        };
    }
}
