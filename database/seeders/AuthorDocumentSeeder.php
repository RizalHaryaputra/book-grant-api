<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthorDocumentSeeder extends Seeder
{
    public function run()
    {
        // Dinamis menarik ID naskah terbaru yang dibuat
        $manuscript = DB::table('manuscripts')->orderBy('id', 'desc')->first();

        if (!$manuscript) {
            return;
        }

        DB::table('author_documents')->insert([
            [
                'manuscript_id' => $manuscript->id,
                'document_type' => 'surat_pernyataan',
                'file_path' => 'documents/surat_pernyataan_author1.pdf',
                'is_valid' => 1,
                'uploaded_at' => Carbon::now(),
            ],
            [
                'manuscript_id' => $manuscript->id,
                'document_type' => 'scan_bermeteri', 
                'file_path' => 'documents/scan_bermeterai_author1.jpg',
                'is_valid' => 0, 
                'uploaded_at' => Carbon::now(),
            ]
        ]);
    }
}