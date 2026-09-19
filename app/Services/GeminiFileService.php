<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Upload large PDFs to the Gemini Files API so generateContent does not
 * need a giant base64 inline payload (which hangs browser uploads).
 *
 * The API key is passed in explicitly (rather than read from config) because
 * a file uploaded with one key belongs to that key's project — callers using
 * {@see GeminiKeyPool} must upload and generate with the SAME key.
 */
class GeminiFileService
{
    private const UPLOAD_BASE = 'https://generativelanguage.googleapis.com/upload/v1beta/files';

    private const FILES_BASE = 'https://generativelanguage.googleapis.com/v1beta';

    /**
     * Upload a local file and wait until Gemini reports state=ACTIVE.
     *
     * @return array{uri: string, name: string}
     */
    public function uploadAndWait(
        string $absolutePath,
        string $apiKey,
        string $mimeType = 'application/pdf',
        string $displayName = 'judgment.pdf',
    ): array {
        if (! is_file($absolutePath)) {
            throw new RuntimeException('File not found for Gemini upload.');
        }

        $numBytes = filesize($absolutePath);
        if ($numBytes === false || $numBytes < 1) {
            throw new RuntimeException('Cannot upload an empty file to Gemini.');
        }

        $start = Http::timeout(60)
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
                'X-Goog-Upload-Protocol' => 'resumable',
                'X-Goog-Upload-Command' => 'start',
                'X-Goog-Upload-Header-Content-Length' => (string) $numBytes,
                'X-Goog-Upload-Header-Content-Type' => $mimeType,
                'Content-Type' => 'application/json',
            ])
            ->withBody(json_encode([
                'file' => ['display_name' => $displayName],
            ]), 'application/json')
            ->post(self::UPLOAD_BASE);

        if (! $start->successful()) {
            $quota = GeminiQuotaExceededException::fromApiBody($start->body());
            if ($quota) {
                throw $quota;
            }

            throw new RuntimeException('Gemini file upload start failed: '.$start->body());
        }

        $uploadUrl = $start->header('X-Goog-Upload-URL')
            ?: $start->header('x-goog-upload-url');

        if (! is_string($uploadUrl) || $uploadUrl === '') {
            throw new RuntimeException('Gemini file upload did not return an upload URL.');
        }

        $bytes = file_get_contents($absolutePath);
        if ($bytes === false) {
            throw new RuntimeException('Could not read file for Gemini upload.');
        }

        $uploaded = Http::timeout(300)
            ->withHeaders([
                'Content-Length' => (string) $numBytes,
                'X-Goog-Upload-Offset' => '0',
                'X-Goog-Upload-Command' => 'upload, finalize',
            ])
            ->withBody($bytes, $mimeType)
            ->post($uploadUrl);

        if (! $uploaded->successful()) {
            $quota = GeminiQuotaExceededException::fromApiBody($uploaded->body());
            if ($quota) {
                throw $quota;
            }

            throw new RuntimeException('Gemini file upload finalize failed: '.$uploaded->body());
        }

        $file = $uploaded->json('file') ?? $uploaded->json();
        $name = is_array($file) ? ($file['name'] ?? null) : null;
        $uri = is_array($file) ? ($file['uri'] ?? null) : null;
        $state = is_array($file) ? ($file['state'] ?? null) : null;

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('Gemini file upload response missing file name.');
        }

        // Wait until processing finishes (ACTIVE) before generateContent.
        $deadline = time() + 180;
        while ($state !== 'ACTIVE' && time() < $deadline) {
            if ($state === 'FAILED') {
                throw new RuntimeException('Gemini failed to process the uploaded file.');
            }
            usleep(500_000);
            $poll = Http::timeout(30)
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->get(self::FILES_BASE.'/'.$name);

            if (! $poll->successful()) {
                throw new RuntimeException('Gemini file status poll failed: '.$poll->body());
            }

            $state = $poll->json('state') ?? $poll->json('file.state');
            $uri = $poll->json('uri') ?? $poll->json('file.uri') ?? $uri;
        }

        if ($state !== 'ACTIVE' || ! is_string($uri) || $uri === '') {
            throw new RuntimeException('Timed out waiting for Gemini file processing.');
        }

        return ['uri' => $uri, 'name' => $name];
    }

    public function delete(string $fileName, string $apiKey): void
    {
        Http::timeout(30)
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->delete(self::FILES_BASE.'/'.$fileName);
    }
}
