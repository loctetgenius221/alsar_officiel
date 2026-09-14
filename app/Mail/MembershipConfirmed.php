<?php

namespace App\Mail;

use App\Domain\Members\Actions\BuildMembershipQrCode;
use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * `$payment` est nul quand l'adhésion n'est pas passée par une cotisation (cf.
     * RegisterMember, cotisation désactivée pour le moment) — ni la vue ni les pièces
     * jointes de cet email ne le référencent, uniquement `$member`.
     */
    public function __construct(
        public Member $member,
        public ?MembershipPayment $payment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue chez ALSAR — votre adhésion est confirmée',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-confirmed',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $qrCode = (new BuildMembershipQrCode())->handle($this->member);

        $pdf = Pdf::loadView('pdf.membership-card', [
            'member' => $this->member,
            'qrCode' => $qrCode,
            'photo' => $this->member->photoDataUri(),
        ])->output();

        return [
            Attachment::fromData(fn () => $pdf, "carte-adhesion-{$this->member->membership_code}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
