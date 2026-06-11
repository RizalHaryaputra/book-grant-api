<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManuscriptFileSeeder extends Seeder
{
    public function run()
    {
        // Dinamis menarik ID naskah terbaru yang dibuat
        $manuscript = DB::table('manuscripts')->orderBy('id', 'desc')->first();

        if (!$manuscript) {
            return;
        }

        DB::table('manuscript_files')->insert([
            'manuscript_id' => $manuscript->id,
            'file_path' => 'manuscripts/draft_pengantar_ti.pdf',
            'file_type' => 'initial',
            'version' => 1,
            'revision_note' => null,
            'uploaded_at' => Carbon::now(),
        ]);
    }
}