<?php

namespace App\Services;

use App\Models\AlfaLawson\TicketEvidence;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EvidenceService
{
    public function uploadFiles(string $ticketNumber, array $files, array $options = [])
    {
        $successCount = 0;
        $errors = [];

        // Verify if the ticket exists
        $ticketExists = \App\Models\AlfaLawson\Ticket::where('No_Ticket', $ticketNumber)->exists();
        Log::debug('Checking if ticket exists', [
            'ticket_number' => $ticketNumber,
            'exists' => $ticketExists,
        ]);

        if (!$ticketExists) {
            throw new \Exception("Ticket with No_Ticket {$ticketNumber} does not exist.");
        }

        foreach ($files as $index => $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $filePath = 'ticket-evidences/' . $ticketNumber . '/' . time() . '_' . $originalName;
                $mimeType = $file->getMimeType();
                $fileSize = $file->getSize();

                // Store the file
                Log::debug('Storing file to storage', [
                    'file_path' => $filePath,
                    'ticket_number' => $ticketNumber,
                ]);
                Storage::disk('public')->put($filePath, file_get_contents($file));

                // Prepare data for database insertion
                $evidenceData = [
                    'No_Ticket' => $ticketNumber,
                    'file_name' => $originalName,
                    'file_path' => $filePath,
                    'file_type' => TicketEvidence::getFileTypeFromMime($mimeType),
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'description' => $options['description'] ?? null,
                    'uploaded_by' => Auth::check() ? Auth::id() : null,
                    'upload_stage' => $options['upload_stage'] ?? TicketEvidence::STAGE_INITIAL,
                ];

                Log::debug('Attempting to save evidence to database', $evidenceData);

                // Create a new TicketEvidence record
                $evidence = TicketEvidence::create($evidenceData);

                Log::debug('Evidence saved to database', ['id' => $evidence->id]);

                $successCount++;
            } catch (\Exception $e) {
                Log::error('Failed to save evidence', [
                    'file' => $originalName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors[] = [
                    'index' => $index,
                    'file' => $originalName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }
}