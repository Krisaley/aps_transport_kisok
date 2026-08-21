<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movement_items', function (Blueprint $table) {
            $table->foreignId('collection_action_id')->nullable()->after('movement_action_id')->constrained('movement_actions')->nullOnDelete();
            $table->foreignId('delivery_action_id')->nullable()->after('collection_action_id')->constrained('movement_actions')->nullOnDelete();
        });

        DB::table('movement_actions')->select(['id', 'action_type'])->orderBy('id')->each(function ($action) {
            $column = in_array($action->action_type, ['collection', 'exchange_collection', 'trade_in', 'yard_receipt'], true)
                ? 'collection_action_id'
                : (in_array($action->action_type, ['delivery', 'exchange_delivery'], true) ? 'delivery_action_id' : null);

            if ($column) {
                DB::table('movement_items')->where('movement_action_id', $action->id)->update([$column => $action->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('movement_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_action_id');
            $table->dropConstrainedForeignId('collection_action_id');
        });
    }
};
