<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SeatLayoutUpdater;
use Illuminate\Support\Facades\Log;

class SyncSeatLayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seat-layout:sync 
                            {--bus-id= : Sync specific bus ID only}
                            {--force : Force update even if no changes detected}
                            {--stats : Show statistics only}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync seat layouts with current bookings to update seat classes (nseat->bseat, hseat->bhseat, vseat->bvseat)';

    /**
     * The seat layout updater service.
     *
     * @var SeatLayoutUpdater
     */
    protected $seatLayoutUpdater;

    /**
     * Create a new command instance.
     */
    public function __construct(SeatLayoutUpdater $seatLayoutUpdater)
    {
        parent::__construct();
        $this->seatLayoutUpdater = $seatLayoutUpdater;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        try {
            // Show statistics only
            if ($this->option('stats')) {
                $this->showStats();
                return 0;
            }

            // Sync specific bus
            if ($busId = $this->option('bus-id')) {
                return $this->syncSpecificBus($busId);
            }

            // Sync all layouts
            return $this->syncAllLayouts($startTime);

        } catch (\Exception $e) {
            $this->error('Critical error: ' . $e->getMessage());
            Log::error('SeatLayoutSync command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Show statistics about seat layouts and bookings
     */
    private function showStats(): void
    {
        $this->info('📊 Seat Layout Sync Statistics');
        $this->line('');

        $stats = $this->seatLayoutUpdater->getSyncStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Active Layouts', $stats['total_active_layouts']],
                ['Total Bookings', $stats['total_bookings']],
                ['Recent Bookings (Last Hour)', $stats['recent_bookings_last_hour']],
                ['Last Sync Time', $stats['last_sync_time']]
            ]
        );
    }

    /**
     * Sync a specific bus layout
     */
    private function syncSpecificBus(int $busId): int
    {
        $this->info("🔄 Syncing seat layout for bus ID: {$busId}");

        $force = $this->option('force');
        $result = $this->seatLayoutUpdater->syncByBusId($busId, $force);

        if ($result['errors'] > 0) {
            $this->error("❌ Error syncing bus {$busId}: " . ($result['error_message'] ?? 'Unknown error'));
            return 1;
        }

        if ($result['updated'] > 0) {
            $this->info("✅ Successfully updated layout for bus {$busId}");
            if ($this->option('detailed')) {
                $this->line("   Seats updated: " . implode(', ', $result['seats_updated']));
            }
        } else {
            $this->info("ℹ️  No updates needed for bus {$busId}");
        }

        return 0;
    }

    /**
     * Sync all seat layouts
     */
    private function syncAllLayouts(float $startTime): int
    {
        $this->info('🚀 Starting seat layout synchronization...');
        $this->line('');

        $results = $this->seatLayoutUpdater->syncAllLayouts();

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        // Display results
        $this->displayResults($results, $executionTime);

        // Return appropriate exit code
        return $results['errors'] > 0 ? 1 : 0;
    }

    /**
     * Display sync results
     */
    private function displayResults(array $results, float $executionTime): void
    {
        $this->line('');
        $this->info('📈 Sync Results:');

        $this->table(
            ['Metric', 'Count'],
            [
                ['✅ Layouts Updated', $results['updated']],
                ['⏭️  Layouts Skipped', $results['skipped']],
                ['❌ Errors', $results['errors']],
                ['⏱️  Execution Time', $executionTime . 'ms']
            ]
        );

        // Show detailed results if detailed option is used
        if ($this->option('detailed') && !empty($results['details'])) {
            $this->line('');
            $this->info('📋 Detailed Results:');

            $tableData = [];
            foreach ($results['details'] as $detail) {
                $tableData[] = [
                    $detail['layout_id'] ?? 'N/A',
                    $detail['bus_id'] ?? 'N/A',
                    $detail['updated'] ?? 0,
                    $detail['skipped'] ?? 0,
                    $detail['errors'] ?? 0,
                    !empty($detail['seats_updated']) ? implode(', ', $detail['seats_updated']) : '-'
                ];
            }

            $this->table(
                ['Layout ID', 'Bus ID', 'Updated', 'Skipped', 'Errors', 'Seats Updated'],
                $tableData
            );
        }

        // Summary message
        if ($results['errors'] === 0) {
            $this->info('🎉 Seat layout synchronization completed successfully!');
        } else {
            $this->warn('⚠️  Seat layout synchronization completed with some errors. Check logs for details.');
        }
    }
}
