<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EvidenceService;
use Illuminate\Support\Facades\Storage;


class CleanupEvidenceFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'evidence:cleanup 
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up orphaned evidence files that are not referenced in database';

    /**
     * Evidence service instance
     */
    private EvidenceService $evidenceService;

    /**
     * Create a new command instance.
     */
    public function __construct(EvidenceService $evidenceService)
    {
        parent::__construct();
        $this->evidenceService = $evidenceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting evidence files cleanup...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No files will be deleted');
            return $this->performDryRun();
        }

        if (!$this->option('force')) {
            if (!$this->confirm('This will permanently delete orphaned evidence files. Are you sure?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        return $this->performCleanup();
    }

    /**
     * Perform actual cleanup
     */
    private function performCleanup(): int
    {
        $this->info('Scanning for orphaned files...');
        
        $result = $this->evidenceService->cleanupOrphanedFiles();
        
        if ($result['cleaned_count'] > 0) {
            $this->info("✅ Successfully cleaned up {$result['cleaned_count']} orphaned files:");
            foreach ($result['cleaned_files'] as $file) {
                $this->line("   - {$file}");
            }
        } else {
            $this->info('✅ No orphaned files found.');
        }

        if ($result['error_count'] > 0) {
            $this->error("❌ Failed to clean up {$result['error_count']} files:");
            foreach ($result['errors'] as $error) {
                $this->error("   - {$error['file']}: {$error['error']}");
            }
            return 1;
        }

        $this->info('Cleanup completed successfully!');
        return 0;
    }

    /**
     * Perform dry run
     */
    private function performDryRun(): int
    {
        $this->info('Scanning for orphaned files...');
        
        // We need to create a dry-run version of the cleanup method
        $allFiles = Storage::disk('public')->files('ticket-evidences');
        $dbFilePaths = \App\Models\AlfaLawson\TicketEvidence::pluck('file_path')->toArray();
        
        $orphanedFiles = [];
        foreach ($allFiles as $filePath) {
            if (!in_array($filePath, $dbFilePaths)) {
                $orphanedFiles[] = $filePath;
            }
        }

        if (count($orphanedFiles) > 0) {
            $this->warn("Found " . count($orphanedFiles) . " orphaned files that would be deleted:");
            foreach ($orphanedFiles as $file) {
                $this->line("   - {$file}");
            }
            
            // Calculate total size
            $totalSize = 0;
            foreach ($orphanedFiles as $file) {
                if (Storage::disk('public')->exists($file)) {
                    $totalSize += Storage::disk('public')->size($file);
                }
            }
            
            $this->info("Total size to be freed: " . $this->formatFileSize($totalSize));
        } else {
            $this->info('✅ No orphaned files found.');
        }

        $this->info('Dry run completed. Use --force to perform actual cleanup.');
        return 0;
    }

    /**
     * Format file size for human reading
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}