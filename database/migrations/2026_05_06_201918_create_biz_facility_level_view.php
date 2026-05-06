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
        DB::connection('school')->statement("CREATE VIEW \"biz_facility_level\" AS WITH RECURSIVE cte AS (
         SELECT biz_facility.id,
            biz_facility.facility_name,
            biz_facility.parent_id,
            (biz_facility.facility_name)::character varying AS combined_name,
            (biz_facility.id)::character varying AS combined_id,
            1 AS level
           FROM biz_facility
        UNION ALL
         SELECT child.id,
            child.facility_name,
            parent.parent_id,
            (((parent.facility_name)::text || '/'::text) || (child.combined_name)::text) AS combined_name,
            (((parent.id || '>'::text) || (child.combined_id)::text))::character varying AS combined_id,
            (child.level + 1) AS level
           FROM (biz_facility parent
             JOIN cte child ON ((parent.id = child.parent_id)))
        )
 SELECT id,
    combined_name AS level_name,
    rtrim(regexp_replace((combined_name)::text, '[^/]*$'::text, ''::text, 'g'::text), '/'::text) AS parent_level_name
   FROM cte
  WHERE (parent_id IS NULL)
  ORDER BY id, combined_id;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('school')->statement("DROP VIEW IF EXISTS \"biz_facility_level\"");
    }
};
