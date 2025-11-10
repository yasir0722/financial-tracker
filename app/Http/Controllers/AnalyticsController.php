<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\RefSpendingType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        // Get selected month or default to current month
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        
        // Parse the selected month
        $date = Carbon::createFromFormat('Y-m', $selectedMonth);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        
        // Get monthly summary by spending type
        $monthlySummary = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->select('spending_type_id', 
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('spending_type_id')
            ->with('spendingType')
            ->get()
            ->keyBy('spending_type_id');
        
        // Get spending types for summary cards
        $spendingTypes = RefSpendingType::active()->ordered()->get();
        
        // Prepare summary data with individual transactions
        $summaryData = [];
        foreach ($spendingTypes as $type) {
            $summary = $monthlySummary->get($type->id);
            
            // Get individual transactions for this type
            $transactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->where('spending_type_id', $type->id)
                ->with('bank') // Load bank relationship
                ->orderBy('transaction_date', 'desc')
                ->get();
            
            $summaryData[$type->code] = [
                'name' => $type->name,
                'debit' => $summary ? $summary->total_debit : 0,
                'credit' => $summary ? $summary->total_credit : 0,
                'count' => $summary ? $summary->transaction_count : 0,
                'badge_class' => $type->badge_class,
                'icon' => $type->icon,
                'transactions' => $transactions
            ];
        }
        
        // Get last 12 months for chart data
        $chartData = $this->getChartData();
        
        // Generate month options (last 24 months)
        $monthOptions = [];
        for ($i = 0; $i < 24; $i++) {
            $month = Carbon::now()->subMonths($i);
            $monthOptions[] = [
                'value' => $month->format('Y-m'),
                'label' => $month->format('F Y'),
                'selected' => $month->format('Y-m') === $selectedMonth
            ];
        }
        
        return view('analytics.index', compact('summaryData', 'chartData', 'monthOptions', 'selectedMonth'));
    }
    
    /**
     * Get chart data for last 12 months
     */
    private function getChartData()
    {
        $months = [];
        $data = [];
        
        // Get last 12 months
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');
            
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();
            
            // Get spending by type for this month
            $monthlyData = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->select('spending_type_id', 
                    DB::raw('SUM(debit) as total_debit'),
                    DB::raw('SUM(credit) as total_credit'))
                ->groupBy('spending_type_id')
                ->get()
                ->keyBy('spending_type_id');
            
            // Store data by spending type
            foreach (RefSpendingType::active()->get() as $type) {
                if (!isset($data[$type->code])) {
                    $data[$type->code] = [
                        'label' => $type->name,
                        'values' => []
                    ];
                }
                
                $monthData = $monthlyData->get($type->id);
                
                // For income, use credit; for others, use debit
                if ($type->code === 'income') {
                    $data[$type->code]['values'][] = $monthData ? $monthData->total_credit : 0;
                } else {
                    $data[$type->code]['values'][] = $monthData ? $monthData->total_debit : 0;
                }
            }
        }
        
        return [
            'months' => $months,
            'datasets' => $data
        ];
    }
}
