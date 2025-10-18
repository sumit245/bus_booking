<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SeatLayoutConsistencyManager;

class FixSeatLayoutConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seat-layout:fix-consistency 
                            {--fix-seat-30 : Fix seat 30 booking specifically}
                            {--validate : Validate layout consistency}
                            {--sync-all : Sync all booked seats}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix seat layout consistency issues';

    /**
     * The seat layout consistency manager.
     *
     * @var SeatLayoutConsistencyManager
     */
    protected $consistencyManager;

    /**
     * Create a new command instance.
     */
    public function __construct(SeatLayoutConsistencyManager $consistencyManager)
    {
        parent::__construct();
        $this->consistencyManager = $consistencyManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Fix seat 30 specifically
            if ($this->option('fix-seat-30')) {
                return $this->fixSeat30();
            }

            // Validate consistency
            if ($this->option('validate')) {
                return $this->validateConsistency();
            }

            // Sync all booked seats
            if ($this->option('sync-all')) {
                return $this->syncAllBookedSeats();
            }

            // Default: show options
            $this->showUsage();
            return 0;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Fix seat 30 booking issue
     */
    private function fixSeat30(): int
    {
        $this->info('🔧 Fixing seat 30 booking issue...');

        $result = $this->consistencyManager->fixSeat30Booking();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            return 0;
        } else {
            $this->error('❌ ' . $result['message']);
            return 1;
        }
    }

    /**
     * Validate layout consistency
     */
    private function validateConsistency(): int
    {
        $this->info('🔍 Validating seat layout consistency...');

        $validation = $this->consistencyManager->validateLayoutConsistency();

        if ($validation['consistent']) {
            $this->info('✅ All layouts are consistent!');
            $this->line("📊 Total layouts checked: {$validation['total_layouts']}");
            return 0;
        } else {
            $this->error('❌ Layout consistency issues found:');

            $tableData = [];
            foreach ($validation['issues'] as $issue) {
                if (isset($issue['layout_id'])) {
                    $tableData[] = [
                        $issue['layout_id'],
                        $issue['bus_id'],
                        $issue['seat_id'],
                        $issue['issue']
                    ];
                } else {
                    $this->error('Error: ' . $issue['error']);
                }
            }

            if (!empty($tableData)) {
                $this->table(
                    ['Layout ID', 'Bus ID', 'Seat ID', 'Issue'],
                    $tableData
                );
            }

            return 1;
        }
    }

    /**
     * Sync all booked seats
     */
    private function syncAllBookedSeats(): int
    {
        $this->info('🔄 Syncing all booked seats...');

        $results = $this->consistencyManager->syncAllBookedSeats();

        $this->line('');
        $this->info('📈 Sync Results:');

        $this->table(
            ['Metric', 'Count'],
            [
                ['✅ Layouts Updated', $results['updated']],
                ['⏭️  Layouts Skipped', $results['skipped']],
                ['❌ Errors', $results['errors']]
            ]
        );

        if (!empty($results['details'])) {
            $this->line('');
            $this->info('📋 Detailed Results:');

            $tableData = [];
            foreach ($results['details'] as $detail) {
                $seatsInfo = '';
                if (isset($detail['updated_seats'])) {
                    $seatsInfo = implode(', ', $detail['updated_seats']);
                }

                $tableData[] = [
                    $detail['layout_id'],
                    $detail['bus_id'],
                    $detail['status'],
                    $seatsInfo
                ];
            }

            $this->table(
                ['Layout ID', 'Bus ID', 'Status', 'Updated Seats'],
                $tableData
            );
        }

        return $results['errors'] > 0 ? 1 : 0;
    }

    /**
     * Show usage information
     */
    private function showUsage(): void
    {
        $this->line('');
        $this->info('🎯 Seat Layout Consistency Tool');
        $this->line('');
        $this->line('Available options:');
        $this->line('  --fix-seat-30    Fix seat 30 booking issue specifically');
        $this->line('  --validate       Validate layout consistency');
        $this->line('  --sync-all       Sync all booked seats');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan seat-layout:fix-consistency --fix-seat-30');
        $this->line('  php artisan seat-layout:fix-consistency --validate');
        $this->line('  php artisan seat-layout:fix-consistency --sync-all');
    }
}
