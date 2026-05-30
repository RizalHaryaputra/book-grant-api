<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Module4DummyDataSeeder extends Seeder
{
    public function run()
    {
        // Ambil data referensi dari modul lain
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
        if ($publishers->isEmpty() || $manuscripts->isEmpty()) {
            $this->command->error('Tidak ada user penerbit atau data manuscripts. Seeder dihentikan.');
            return;
        }

        // ==========================================
        // 1. Publisher Checks
        // ==========================================
        $checks = [];
        foreach ($manuscripts->take(10) as $manuscript) {
            $checks[] = [
                'manuscript_id' => $manuscript->id,
                'publisher_id' => $publishers->random()->id,
                'cover_ok' => rand(0, 1),
                'page_count_ok' => rand(0, 1),
                'admin_docs_ok' => rand(0, 1),
                'decision' => rand(0, 1) ? 'approved' : 'revised',
                'notes' => $this->getRandomSentence(),
                'checked_at' => Carbon::now()->subDays(rand(1, 10)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('publisher_checks')->insert($checks);
        $this->command->info('✓ Publisher checks seeded.');

        // ==========================================
        // 2. Deadlines
        // ==========================================
        $deadlineTypes = ['draft_upload', 'review', 'revision', 'preprint'];
        $statuses = ['active', 'completed', 'expired'];
        $deadlines = [];
        foreach ($manuscripts->take(15) as $manuscript) {
            $assignee = $authors->isNotEmpty() ? $authors->random() : null;
            if ($reviewers->isNotEmpty() && rand(0, 1)) {
                $assignee = $reviewers->random();
            }
            if (!$assignee) continue;
            $deadlines[] = [
                'manuscript_id' => $manuscript->id,
                'assignee_id' => $assignee->id,
                'deadline_type' => $deadlineTypes[array_rand($deadlineTypes)],
                'due_date' => Carbon::now()->addDays(rand(1, 30)),
                'status' => $statuses[array_rand($statuses)],
                'days_before' => rand(1, 7),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('deadlines')->insert($deadlines);
        $this->command->info('✓ Deadlines seeded.');

        // ==========================================
        // 3. Notification Logs
        // ==========================================
        $eventTypes = [
            'account_created', 'contract_validated', 'draft_uploaded',
            'review_assigned', 'review_completed', 'revision_requested',
            'preprint_entered', 'publisher_approved', 'publisher_revised',
            'deadline_reminder'
        ];
        $statusesNotif = ['pending', 'sent', 'failed'];
        $users = DB::table('users')->get();
        $logs = [];
        for ($i = 0; $i < 50; $i++) {
            $recipient = $users->random();
            $manuscript = $manuscripts->random();
            $reviewSubmission = $reviewSubmissions->isNotEmpty() ? $reviewSubmissions->random() : null;
            $eventType = $eventTypes[array_rand($eventTypes)];
            $status = $statusesNotif[array_rand($statusesNotif)];
            $logs[] = [
                'recipient_id' => $recipient->id,
                'manuscript_id' => $manuscript->id,
                'rs_id' => $reviewSubmission ? $reviewSubmission->id : null,
                'event_type' => $eventType,
                'email_to' => $recipient->email,
                'subject' => 'Notifikasi: ' . str_replace('_', ' ', $eventType),
                'body_html' => '<p>Ini adalah notifikasi dummy untuk event ' . $eventType . '.</p>',
                'status' => $status,
                'sent_at' => $status == 'sent' ? Carbon::now()->subHours(rand(1, 48)) : null,
                'error_message' => $status == 'failed' ? 'SMTP error: timeout' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
                'updated_at' => Carbon::now(),
            ];
        }
        DB::table('notification_log')->insert($logs);
        $this->command->info('✓ Notification logs seeded.');

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