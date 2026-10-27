<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasUserstamps;

    protected $table = 'sales';

    protected $fillable = [
        'transaction_type',
        'customer_id',
        'supplier_id',
        'user_id',
        'invoice_no',
        'total_amount',
        'discount',
        'vat',
        'payment_method',
        'sale_date',
        'payment_status',
        'partial_amount',
        'balance_amount',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'sale_date' => 'date',
        'partial_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    /** The delivery note shares the invoice's sequence: INV-20261027-001 → DN-20261027-001. */
    public function getDeliveryNoteNoAttribute(): string
    {
        $invoiceNo = trim((string) $this->invoice_no);

        if ($invoiceNo === '') {
            return 'DN-'.str_pad((string) $this->id, DocumentNumber::PAD, '0', STR_PAD_LEFT);
        }

        return 'DN-'.preg_replace('/^[A-Za-z]+-/', '', $invoiceNo);
    }

    /** Where the goods are going: the linked online order's details first, then the customer record. */
    public function deliveryRecipient(): array
    {
        $order = $this->orders->first();

        return [
            'name' => $order?->customer_name ?: ($this->customer?->name ?: 'Walk-in Customer'),
            'phone' => $order?->phone ?: $this->customer?->phone,
            'email' => $order?->email ?: $this->customer?->email,
            'address' => $order?->delivery_location ?: ($order?->address ?: $this->customer?->address),
            'order_number' => $order?->order_number,
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sale_items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'sale_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'sale_id');
    }

    public function efris_invoices()
    {
        return $this->hasMany(EfrisInvoice::class, 'sale_id');
    }

}
