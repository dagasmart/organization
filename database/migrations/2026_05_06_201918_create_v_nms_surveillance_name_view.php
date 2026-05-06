<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection('school')->statement("CREATE VIEW \"v_nms_surveillance_name\" AS SELECT COALESCE(((max((name)::text))::bigint + 1), (concat((to_char((CURRENT_DATE)::timestamp with time zone, 'YYYYMMDD'::text))::bigint, 1000000))::bigint) AS \"coalesce\"
   FROM biz_stream
  WHERE ((created_at)::date = CURRENT_DATE);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('school')->statement("DROP VIEW IF EXISTS \"v_nms_surveillance_name\"");
    }
};
