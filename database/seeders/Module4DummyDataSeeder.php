<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Module4DummyDataSeeder extends Seeder
{
    public function run()
    {
        // Ambil data referensi dari modul lain dengan JOIN ke roles
        $publishers = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'penerbit')
            ->select('users.*')
            ->get();
        
        $authors = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'penulis')
            ->select('users.*')
            ->get();
        
        $reviewers = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'reviewer')
            ->select('users.*')
            ->get();
        
        $manuscripts = DB::table('manuscripts')->get();
        $reviewSubmissions = DB::table('review_submissions')->get();

        // Validasi data minimal
        if ($publishers->isEmpty()) {
            $this->command->error('Tidak ada user dengan role penerbit. Seeder dihentikan.');
            return;
        }
        if ($manuscripts->isEmpty()) {
            $this->command->error('Tidak ada data manuscripts. Seeder dihentikan.');
            return;
        }

        // ==========================================
        // 1. Notification Templates
        // ==========================================
        $templates = [
            ['event_type' => 'account_created', 'subject' => 'Akun Berhasil Dibuat', 'body_html' => '<p>Selamat datang {name}, akun Anda telah dibuat.</p>'],
            ['event_type' => 'contract_validated', 'subject' => 'Kontrak Divalidasi', 'body_html' => '<p>Kontrak buku "{title}" telah divalidasi.</p>'],
            ['event_type' => 'draft_uploaded', 'subject' => 'Draft Awal Diunggah', 'body_html' => '<p>Draft awal naskah "{title}" telah diunggah.</p>'],
            ['event_type' => 'review_assigned', 'subject' => 'Penugasan Review', 'body_html' => '<p>Anda ditugaskan mereview naskah "{title}". Deadline: {due_date}</p>'],
            ['event_type' => 'review_completed', 'subject' => 'Review Selesai', 'body_html' => '<p>Review untuk naskah "{title}" telah selesai.</p>'],
            ['event_type' => 'revision_requested', 'subject' => 'Revisi Diperlukan', 'body_html' => '<p>Naskah "{title}" perlu direvisi. Catatan: {notes}</p>'],
            ['event_type' => 'preprint_entered', 'subject' => 'Naskah Masuk Pra-Cetak', 'body_html' => '<p>Naskah "{title}" telah memasuki tahap pra-cetak.</p>'],
            ['event_type' => 'publisher_approved', 'subject' => 'Naskah Disetujui', 'body_html' => '<p>Naskah "{title}" disetujui dan siap cetak.</p>'],
            ['event_type' => 'publisher_revised', 'subject' => 'Revisi dari Penerbit', 'body_html' => '<p>Naskah "{title}" perlu revisi dari penerbit.</p>'],
            ['event_type' => 'deadline_reminder', 'subject' => 'Pengingat Deadline', 'body_html' => '<p>Deadline {task} akan tiba pada {due_date}.</p>'],
        ];

        foreach ($templates as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['event_type' => $template['event_type']],
                [
                    'subject' => $template['subject'],
                    'body_html' => $template['body_html'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        $this->command->info('✓ Notification templates seeded.');

        // ==========================================
        // 2. Publisher Checks
        // ==========================================
        $checks = [];
        foreach ($manuscripts->take(10) as $manuscript) {
            $checks[] = [
                'manuscript_id' => $manuscript->id,
                'publisher_id' => $publishers->random()->id,
                'cover_ok' => rand(0, 1),
                'page_count_ok' => rand(0, 1),
                'admin_docs_ok' => rand(0, 1),
                'notes' => 'Pemeriksaan: ' . ($this->getRandomSentence()),
                'checked_at' => Carbon::now()->subDays(rand(1, 10)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('publisher_checks')->insert($checks);
        $this->command->info('✓ Publisher checks seeded.');

        // ==========================================
        // 3. Deadlines
        // ==========================================
        $deadlineTypes = ['draft_upload', 'review', 'revision', 'preprint'];
        $statuses = ['active', 'completed', 'expired'];
        $deadlines = [];
        foreach ($manuscripts->take(10) as $manuscript) {
            $assignee = $authors->isNotEmpty() ? $authors->random() : null;
            if ($reviewers->isNotEmpty() && rand(0,1)) {
                $assignee = $reviewers->random();
            }
            if (!$assignee) continue;
            $deadlines[] = [
                'manuscript_id' => $manuscript->id,
                'assignee_id' => $assignee->id,
                'deadline_type' => $deadlineTypes[array_rand($deadlineTypes)],
                'due_date' => Carbon::now()->addDays(rand(1, 30))->toDateString(),
                'status' => $statuses[array_rand($statuses)],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('deadlines')->insert($deadlines);
        $this->command->info('✓ Deadlines seeded.');

        // ==========================================
        // 4. Notification Logs
        // ==========================================
        $templatesDb = DB::table('notification_templates')->get();
        $users = DB::table('users')->get(); // Semua user
        $statusesNotif = ['pending', 'sent', 'failed'];
        $logs = [];
        for ($i = 0; $i < 50; $i++) {
            $template = $templatesDb->random();
            $recipient = $users->random();
            $manuscript = $manuscripts->random();
            $status = $statusesNotif[array_rand($statusesNotif)];
            $logs[] = [
                'template_id' => $template->id,
                'recipient_id' => $recipient->id,
                'manuscript_id' => $manuscript->id,
                'email_to' => $recipient->email,
                'subject' => $template->subject,
                'status' => $status,
                'sent_at' => $status == 'sent' ? Carbon::now()->subHours(rand(1, 48)) : null,
                'error_message' => $status == 'failed' ? 'SMTP error: timeout' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ];
        }
        foreach (array_chunk($logs, 50) as $chunk) {
            DB::table('notification_log')->insert($chunk);
        }
        $this->command->info('✓ Notification logs seeded.');

        // ==========================================
        // 5. Publisher Decisions
        // ==========================================
        $checksList = DB::table('publisher_checks')->get();
        $decisions = [];
        foreach ($checksList as $check) {
            $decisions[] = [
                'check_id' => $check->id,
                'publisher_id' => $check->publisher_id,
                'decision' => rand(0, 1) ? 'approved' : 'revised',
                'revision_notes' => rand(0, 1) ? 'Harap perbaiki sistematika penulisan.' : null,
                'decided_at' => Carbon::now()->subDays(rand(0, 5)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('publisher_decisions')->insert($decisions);
        $this->command->info('✓ Publisher decisions seeded.');

        // ==========================================
        // 6. Reminder Logs
        // ==========================================
        $deadlinesList = DB::table('deadlines')->get();
        $reminders = [];
        foreach ($deadlinesList->take(30) as $deadline) {
            $reminders[] = [
                'deadline_id' => $deadline->id,
                'recipient_id' => $deadline->assignee_id,
                'days_before' => rand(1, 7),
                'sent_at' => Carbon::now()->subDays(rand(1, 10)),
                'success' => rand(0, 1),
                'error_message' => rand(0, 1) ? null : 'Failed to send email',
                'created_at' => now(),
            ];
        }
        DB::table('reminder_logs')->insert($reminders);
        $this->command->info('✓ Reminder logs seeded.');

        $this->command->info('All Module 4 dummy data seeded successfully!');
    }

    private function getRandomSentence()
    {
        $sentences = [
            'Periksa kelengkapan dokumen administrasi.',
            'Sampul belum sesuai template.',
            'Jumlah halaman kurang dari ketentuan.',
            'Dokumen administrasi lengkap.',
            'Perlu perbaikan tata letak.',
        ];
        return $sentences[array_rand($sentences)];
    }
}