<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['leads', 'contact_messages', 'quote_requests', 'demo_requests', 'diagnostic_requests'];

    /**
     * Estados de la fase 4 → estados comerciales de la fase 5.
     * Solo se reescriben valores; no se elimina ningún registro.
     *
     * @var array<string, string>
     */
    private array $statusMap = [
        'in_progress' => 'contacted',
        'closed' => 'discarded',
        'spam' => 'discarded',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            });

            foreach ($this->statusMap as $from => $to) {
                DB::table($table)->where('status', $from)->update(['status' => $to]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Los estados nuevos sin equivalente previo vuelven a `in_progress`
     * (negociación, convertido) o `closed` (descartado).
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::table($table)->whereIn('status', ['contacted', 'negotiating', 'converted'])->update(['status' => 'in_progress']);
            DB::table($table)->where('status', 'discarded')->update(['status' => 'closed']);

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('assigned_to');
            });
        }
    }
};
