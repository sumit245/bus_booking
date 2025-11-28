<?php

namespace App\Services;

use App\Models\GeneralSetting;

class AgentCommissionCalculator
{
    protected $commissionConfig;

    public function __construct()
    {
        $this->loadCommissionConfig();
    }

    protected function loadCommissionConfig()
    {
        $generalSetting = GeneralSetting::first();
        $this->commissionConfig = $generalSetting->agent_commission_config ?? $this->getDefaultConfig();
    }

    public function getCommissionConfig(): array
    {
        return $this->commissionConfig ?? $this->getDefaultConfig();
    }

    protected function getDefaultConfig()
    {
        return [
            'threshold_amount' => 500,
            'below_threshold' => [
                ['amount' => 50, 'condition' => '0-200'],
                ['amount' => 100, 'condition' => '200-500']
            ],
            'above_threshold' => [
                ['percentage' => 5, 'condition' => '500-1000'],
                ['percentage' => 10, 'condition' => '1000+']
            ]
        ];
    }

    /**
     * Calculate commission for a given booking amount
     * 
     * @param float $bookingAmount The total booking amount
     * @param array|null $config Optional commission configuration (uses default if not provided)
     * @return array Commission details
     */
    public function calculate($bookingAmount, $config = null)
    {
        // Use provided config or load from database
        if ($config !== null) {
            $this->commissionConfig = $config;
        } elseif (!$this->commissionConfig) {
            $this->loadCommissionConfig();
        }

        if ($bookingAmount <= $this->commissionConfig['threshold_amount']) {
            return $this->calculateFixedCommission($bookingAmount);
        } else {
            return $this->calculatePercentageCommission($bookingAmount);
        }
    }

    protected function calculateFixedCommission($bookingAmount)
    {
        $belowThresholdRules = $this->commissionConfig['below_threshold'];

        foreach ($belowThresholdRules as $rule) {
            $condition = $rule['condition'];
            $amount = $rule['amount'];

            if ($this->matchesCondition($bookingAmount, $condition)) {
                return [
                    'commission_amount' => $amount,
                    'commission_type' => 'fixed',
                    'commission_percentage' => 0,
                    'threshold_applied' => 'below',
                    'condition_matched' => $condition,
                ];
            }
        }

        // Default fallback
        return [
            'commission_amount' => 50,
            'commission_type' => 'fixed',
            'commission_percentage' => 0,
            'threshold_applied' => 'below',
            'condition_matched' => 'default',
        ];
    }

    protected function calculatePercentageCommission($bookingAmount)
    {
        $aboveThresholdRules = $this->commissionConfig['above_threshold'];

        foreach ($aboveThresholdRules as $rule) {
            $condition = $rule['condition'];
            $percentage = $rule['percentage'];

            if ($this->matchesCondition($bookingAmount, $condition)) {
                $commissionAmount = ($bookingAmount * $percentage) / 100;

                return [
                    'commission_amount' => $commissionAmount,
                    'commission_type' => 'percentage',
                    'commission_percentage' => $percentage,
                    'threshold_applied' => 'above',
                    'condition_matched' => $condition,
                ];
            }
        }

        // Default fallback
        $defaultPercentage = 5;
        $commissionAmount = ($bookingAmount * $defaultPercentage) / 100;

        return [
            'commission_amount' => $commissionAmount,
            'commission_type' => 'percentage',
            'commission_percentage' => $defaultPercentage,
            'threshold_applied' => 'above',
            'condition_matched' => 'default',
        ];
    }

    protected function matchesCondition($amount, $condition)
    {
        // Parse conditions like "0-200", "200-500", "500-1000", "1000+"
        if (strpos($condition, '+') !== false) {
            // Handle "1000+" case
            $minAmount = (int) str_replace('+', '', $condition);
            return $amount >= $minAmount;
        } elseif (strpos($condition, '-') !== false) {
            // Handle "200-500" case
            [$minAmount, $maxAmount] = explode('-', $condition);
            return $amount >= (int) $minAmount && $amount <= (int) $maxAmount;
        }

        return false;
    }

    public function calculateFullBookingDetails($baseAmount, $serviceFee = 0, $gst = 0, $platformFee = 0)
    {
        $commissionDetails = $this->calculate($baseAmount);

        // Calculate amounts
        $totalFees = $serviceFee + $gst + $platformFee;
        $agentPays = $baseAmount + $totalFees; // Agent pays base + fees
        $passengerPays = $baseAmount + $commissionDetails['commission_amount']; // Passenger pays base + commission

        return [
            'base_amount' => $baseAmount,
            'service_fee' => $serviceFee,
            'gst' => $gst,
            'platform_fee' => $platformFee,
            'total_fees' => $totalFees,
            'agent_pays' => $agentPays,
            'passenger_pays' => $passengerPays,
            'commission' => $commissionDetails,
            'agent_profit' => $commissionDetails['commission_amount'],
        ];
    }

    public function updateCommissionConfig($config)
    {
        $generalSetting = GeneralSetting::first();
        $generalSetting->update([
            'agent_commission_config' => $config
        ]);

        $this->commissionConfig = $config;
    }


    public function validateCommissionConfig($config)
    {
        $errors = [];

        if (!isset($config['threshold_amount']) || !is_numeric($config['threshold_amount'])) {
            $errors[] = 'Threshold amount is required and must be numeric';
        }

        if (!isset($config['below_threshold']) || !is_array($config['below_threshold'])) {
            $errors[] = 'Below threshold rules are required';
        }

        if (!isset($config['above_threshold']) || !is_array($config['above_threshold'])) {
            $errors[] = 'Above threshold rules are required';
        }

        // Validate below threshold rules
        foreach ($config['below_threshold'] ?? [] as $index => $rule) {
            if (!isset($rule['amount']) || !is_numeric($rule['amount'])) {
                $errors[] = "Below threshold rule {$index}: amount is required and must be numeric";
            }
            if (!isset($rule['condition']) || empty($rule['condition'])) {
                $errors[] = "Below threshold rule {$index}: condition is required";
            }
        }

        // Validate above threshold rules
        foreach ($config['above_threshold'] ?? [] as $index => $rule) {
            if (!isset($rule['percentage']) || !is_numeric($rule['percentage'])) {
                $errors[] = "Above threshold rule {$index}: percentage is required and must be numeric";
            }
            if (!isset($rule['condition']) || empty($rule['condition'])) {
                $errors[] = "Above threshold rule {$index}: condition is required";
            }
        }

        return $errors;
    }
}
