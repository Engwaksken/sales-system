<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Common units for retail, hardware and wholesale; users can add more. */
    public const DEFAULTS = [
        // Counted
        ['Pieces', 'pcs', 'Count'], ['Units', 'unit', 'Count'], ['Pair', 'pair', 'Count'], ['Dozen', 'dozen', 'Count'],
        ['Set', 'set', 'Count'], ['Pack', 'pack', 'Count'], ['Packet', 'pkt', 'Count'], ['Box', 'box', 'Count'],
        ['Carton', 'ctn', 'Count'], ['Crate', 'crate', 'Count'], ['Tray', 'tray', 'Count'], ['Bundle', 'bundle', 'Count'],
        ['Roll', 'roll', 'Count'], ['Sheet', 'sheet', 'Count'], ['Bag', 'bag', 'Count'], ['Sack', 'sack', 'Count'],
        ['Bale', 'bale', 'Count'], ['Bottle', 'btl', 'Count'], ['Can', 'can', 'Count'], ['Tin', 'tin', 'Count'],
        ['Jerrycan', 'jerrycan', 'Count'], ['Sachet', 'sachet', 'Count'], ['Tube', 'tube', 'Count'], ['Ream', 'ream', 'Count'],
        // Weight
        ['Kilogram', 'kg', 'Weight'], ['Gram', 'g', 'Weight'], ['Tonne', 't', 'Weight'],
        // Volume
        ['Litre', 'ltr', 'Volume'], ['Millilitre', 'ml', 'Volume'], ['Gallon', 'gal', 'Volume'],
        // Length and area
        ['Metre', 'm', 'Length'], ['Centimetre', 'cm', 'Length'], ['Millimetre', 'mm', 'Length'],
        ['Foot', 'ft', 'Length'], ['Inch', 'in', 'Length'], ['Square metre', 'sqm', 'Area'],
    ];

    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            // What stock rows store in stock.unit_type.
            $table->string('abbreviation', 20)->unique();
            $table->string('category', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('unit_types')->insert(array_map(fn ($u) => [
            'name' => $u[0], 'abbreviation' => $u[1], 'category' => $u[2], 'is_active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ], self::DEFAULTS));

        // Units already typed on stock rows stay selectable.
        $known = array_map(fn ($u) => strtolower($u[1]), self::DEFAULTS);
        $existing = DB::table('stock')->whereNotNull('unit_type')->where('unit_type', '!=', '')
            ->distinct()->pluck('unit_type')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '' && ! in_array(strtolower($v), $known, true))
            ->unique(fn ($v) => strtolower($v))
            ->values();
        foreach ($existing as $unit) {
            DB::table('unit_types')->insert([
                'name' => mb_substr(ucfirst($unit), 0, 60), 'abbreviation' => mb_substr($unit, 0, 20),
                'category' => 'Other', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
