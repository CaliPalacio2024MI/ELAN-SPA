<?php

namespace App\Mail;

use App\Models\Sale;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class FacturaEnviadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sale;
    public $invoice;
    public $pdfData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Sale $sale, Invoice $invoice, $pdfData)
    {
        $this->sale = $sale;
        $this->invoice = $invoice;
        $this->pdfData = $pdfData;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Factura de su compra en ELAN SPA',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.factura',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfData, 'Factura-' . $this->sale->id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}