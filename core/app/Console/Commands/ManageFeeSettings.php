<?php

namespace App\Console\Commands;

use App\Models\GeneralSetting;
use Illuminate\Console\Command;

class ManageFeeSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:manage 
                            {--gst= : Set GST percentage (0-100)}
                            {--service= : Set service charge percentage (0-100)}
                            {--platform-percent= : Set platform fee percentage (0-100)}
                            {--platform-fixed= : Set fixed platform fee amount}
                            {--show : Show current fee settings}
                            {--reset : Reset all fees to zero}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage booking fee settings (GST, Service Charge, Platform Fee)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $generalSettings = GeneralSetting::first();

        if (!$generalSettings) {
            $this->error('General settings not found. Please run migrations first.');
            return 1;
        }

        // Show current settings
        if ($this->option('show')) {
            $this->showCurrentSettings($generalSettings);
            return 0;
        }

        // Reset all fees
        if ($this->option('reset')) {
            $this->resetFees($generalSettings);
            return 0;
        }

        $updated = false;

        // Update GST
        if ($this->option('gst') !== null) {
            $gst = (float) $this->option('gst');
            if ($gst >= 0 && $gst <= 100) {
                $generalSettings->gst_percentage = $gst;
                $updated = true;
                $this->info("GST percentage set to {$gst}%");
            } else {
                $this->error('GST percentage must be between 0 and 100');
                return 1;
            }
        }

        // Update Service Charge
        if ($this->option('service') !== null) {
            $service = (float) $this->option('service');
            if ($service >= 0 && $service <= 100) {
                $generalSettings->service_charge_percentage = $service;
                $updated = true;
                $this->info("Service charge percentage set to {$service}%");
            } else {
                $this->error('Service charge percentage must be between 0 and 100');
                return 1;
            }
        }

        // Update Platform Fee Percentage
        if ($this->option('platform-percent') !== null) {
            $platformPercent = (float) $this->option('platform-percent');
            if ($platformPercent >= 0 && $platformPercent <= 100) {
                $generalSettings->platform_fee_percentage = $platformPercent;
                $updated = true;
                $this->info("Platform fee percentage set to {$platformPercent}%");
            } else {
                $this->error('Platform fee percentage must be between 0 and 100');
                return 1;
            }
        }

        // Update Fixed Platform Fee
        if ($this->option('platform-fixed') !== null) {
            $platformFixed = (float) $this->option('platform-fixed');
            if ($platformFixed >= 0) {
                $generalSettings->platform_fee_fixed = $platformFixed;
                $updated = true;
                $this->info("Fixed platform fee set to ₹{$platformFixed}");
            } else {
                $this->error('Fixed platform fee must be 0 or greater');
                return 1;
            }
        }

        if ($updated) {
            $generalSettings->save();
            $this->info('Fee settings updated successfully!');
            $this->showCurrentSettings($generalSettings);
            $this->showExampleCalculation();
        } else {
            $this->info('No changes made. Use --show to view current settings or specify values to update.');
        }

        return 0;
    }

    private function showCurrentSettings($generalSettings)
    {
        $this->info('=== CURRENT FEE SETTINGS ===');
        $this->table(
            ['Fee Type', 'Value'],
            [
                ['GST Percentage', $generalSettings->gst_percentage . '%'],
                ['Service Charge Percentage', $generalSettings->service_charge_percentage . '%'],
                ['Platform Fee Percentage', $generalSettings->platform_fee_percentage . '%'],
                ['Fixed Platform Fee', '₹' . $generalSettings->platform_fee_fixed],
            ]
        );
    }

    private function resetFees($generalSettings)
    {
        $generalSettings->gst_percentage = 0;
        $generalSettings->service_charge_percentage = 0;
        $generalSettings->platform_fee_percentage = 0;
        $generalSettings->platform_fee_fixed = 0;
        $generalSettings->save();

        $this->info('All fees have been reset to zero.');
        $this->showCurrentSettings($generalSettings);
    }

    private function showExampleCalculation()
    {
        $generalSettings = GeneralSetting::first();
        $baseFare = 100;

        $serviceCharge = $baseFare * ($generalSettings->service_charge_percentage / 100);
        $platformFee = ($baseFare * ($generalSettings->platform_fee_percentage / 100)) + $generalSettings->platform_fee_fixed;
        $amountBeforeGST = $baseFare + $serviceCharge + $platformFee;
        $gst = $amountBeforeGST * ($generalSettings->gst_percentage / 100);
        $total = $amountBeforeGST + $gst;

        $this->info('=== EXAMPLE CALCULATION (₹100 base fare) ===');
        $this->table(
            ['Item', 'Amount'],
            [
                ['Base Fare', '₹' . number_format($baseFare, 2)],
                ['Service Charge (' . $generalSettings->service_charge_percentage . '%)', '₹' . number_format($serviceCharge, 2)],
                ['Platform Fee (' . $generalSettings->platform_fee_percentage . '% + ₹' . $generalSettings->platform_fee_fixed . ')', '₹' . number_format($platformFee, 2)],
                ['Subtotal', '₹' . number_format($amountBeforeGST, 2)],
                ['GST (' . $generalSettings->gst_percentage . '%)', '₹' . number_format($gst, 2)],
                ['TOTAL', '₹' . number_format($total, 2)],
            ]
        );
    }
}
