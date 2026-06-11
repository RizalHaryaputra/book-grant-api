<?php

declare(strict_types=1);

namespace App\Services;

/**
 * NotificationService
 *
 * Responsible for retrieving and rendering email notification templates.
 * Currently, templates are hardcoded within this service as a fallback
 * because the `notification_templates` database table has not yet been
 * created. When the table is ready, the getTemplate() method should be
 * refactored to query from it first, falling back to hardcoded defaults.
 *
 * Usage:
 * $service = app(NotificationService::class);
 * ['subject' => $subject, 'body_html' => $body] =
 * $service->getTemplate('account_created', ['name' => 'John Doe', ...]);
 */
class NotificationService
{
    /**
     * Retrieve and render a notification template for the given event type.
     *
     * Supported event types:
     * - 'account_created'      → requires: name, email, raw_password
     * - 'contract_validated'   → requires: name, email, manuscript_title
     *
     * @param  string  $eventType  The event identifier string.
     * @param  array   $data       Key-value pairs used to populate the template.
     *
     * @return array{subject: string, body_html: string}
     *
     * @throws \InvalidArgumentException When the event type has no registered template.
     */
    public function getTemplate(string $eventType, array $data): array
    {
        return match ($eventType) {
            'account_created'    => $this->accountCreatedTemplate($data),
            'contract_validated' => $this->contractValidatedTemplate($data),
            default => throw new \InvalidArgumentException(
                "No notification template registered for event type: [{$eventType}]"
            ),
        };
    }

    // =========================================================================
    //  Private Template Renderers
    // =========================================================================

    /**
     * Build the 'account_created' email template.
     *
     * Required $data keys: name, email, raw_password
     *
     * @param  array  $data
     * @return array{subject: string, body_html: string}
     */
    private function accountCreatedTemplate(array $data): array
    {
        $subject = 'Selamat Datang di Book Grant — Akun Anda Telah Dibuat';

        $bodyHtml = <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .header { background-color: #1a56db; padding: 30px 40px; text-align: center; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
                .body { padding: 30px 40px; color: #333333; line-height: 1.7; }
                .body h2 { color: #1a56db; font-size: 20px; }
                .footer { background-color: #f4f4f4; text-align: center; padding: 20px 40px; font-size: 12px; color: #999999; }
                .btn { display: inline-block; margin-top: 20px; padding: 12px 28px; background-color: #1a56db; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 15px; }
                .password-box { background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; font-family: monospace; font-weight: bold; color: #111827; letter-spacing: 1px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📚 Book Grant Platform</h1>
                </div>
                <div class="body">
                    <h2>Halo, {{name}}!</h2>
                    <p>Selamat datang di <strong>Book Grant Platform</strong>. Akun Anda telah berhasil dibuat dengan kredensial berikut:</p>
                    <p>
                        <strong>Email:</strong> {{email}}<br>
                        <strong>Password:</strong> <span class="password-box">{{password}}</span>
                    </p>
                    <p>Anda kini dapat masuk ke platform dan mulai mengajukan manuskrip Anda untuk program hibah buku. Kami sangat menyarankan Anda untuk segera mengubah password ini setelah berhasil login.</p>
                    <p>Jika Anda merasa tidak mendaftarkan akun ini, abaikan email ini atau hubungi tim dukungan kami.</p>
                    <p>Salam hangat,<br><strong>Tim Book Grant</strong></p>
                </div>
                <div class="footer">
                    &copy; 2026 Book Grant Platform. Semua hak dilindungi.
                </div>
            </div>
        </body>
        </html>
        HTML;

        // Substitute placeholders with actual data values
        $bodyHtml = str_replace(
            ['{{name}}', '{{email}}', '{{password}}'],
            [
                $data['name'] ?? 'Pengguna', 
                $data['email'] ?? '', 
                $data['raw_password'] ?? '********'
            ],
            $bodyHtml
        );

        return [
            'subject'   => $subject,
            'body_html' => $bodyHtml,
        ];
    }

    /**
     * Build the 'contract_validated' email template.
     *
     * Required $data keys: name, email, manuscript_title
     *
     * @param  array  $data
     * @return array{subject: string, body_html: string}
     */
    private function contractValidatedTemplate(array $data): array
    {
        $subject = 'Kontrak Anda Telah Divalidasi — Book Grant Platform';

        $bodyHtml = <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .header { background-color: #057a55; padding: 30px 40px; text-align: center; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; letter-spacing: 1px; }
                .body { padding: 30px 40px; color: #333333; line-height: 1.7; }
                .body h2 { color: #057a55; font-size: 20px; }
                .highlight-box { background-color: #f0fdf4; border-left: 4px solid #057a55; padding: 14px 18px; margin: 20px 0; border-radius: 4px; }
                .footer { background-color: #f4f4f4; text-align: center; padding: 20px 40px; font-size: 12px; color: #999999; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✅ Book Grant Platform</h1>
                </div>
                <div class="body">
                    <h2>Halo, {{name}}!</h2>
                    <p>Kami dengan senang hati menginformasikan bahwa kontrak penerbitan Anda untuk manuskrip berikut telah <strong>berhasil divalidasi</strong>:</p>
                    <div class="highlight-box">
                        <strong>Judul Manuskrip:</strong> {{manuscript_title}}
                    </div>
                    <p>Proses selanjutnya akan segera dimulai oleh tim kami. Pastikan semua dokumen pendukung Anda telah diunggah ke platform.</p>
                    <p>Untuk informasi lebih lanjut, silakan masuk ke akun Anda dengan email: <strong>{{email}}</strong>.</p>
                    <p>Terima kasih atas kepercayaan Anda,<br><strong>Tim Book Grant</strong></p>
                </div>
                <div class="footer">
                    &copy; 2026 Book Grant Platform. Semua hak dilindungi.
                </div>
            </div>
        </body>
        </html>
        HTML;

        // Substitute placeholders with actual data values
        $bodyHtml = str_replace(
            ['{{name}}', '{{email}}', '{{manuscript_title}}'],
            [
                $data['name']             ?? 'Pengguna',
                $data['email']            ?? '',
                $data['manuscript_title'] ?? 'Tanpa Judul',
            ],
            $bodyHtml
        );

        return [
            'subject'   => $subject,
            'body_html' => $bodyHtml,
        ];
    }
}