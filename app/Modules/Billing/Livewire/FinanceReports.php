<?php

namespace App\Modules\Billing\Livewire;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Quote;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Finance reports island (12 doc §4.5): monthly revenue (last 6
 * months), outstanding aging buckets, quote win rate. Cached 5 min —
 * poll-friendly on shared hosting (07-queues §1).
 */
#[Layout('layouts.admin')]
class FinanceReports extends Component
{
    public function render(): View
    {
        $this->authorize('viewAny', Invoice::class);

        $data = Cache::remember('sewa.billing.reports', 300, fn (): array => $this->compute());

        return view('billing.livewire.finance-reports', $data);
    }

    /** @return array<string, mixed> */
    private function compute(): array
    {
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();

            $months[] = [
                'label' => $monthStart->format('M Y'),
                'invoiced' => (int) Invoice::query()->whereBetween('created_at', [$monthStart, $monthEnd])->sum('total'),
                'collected' => (int) Invoice::query()->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('total'),
            ];
        }

        $outstanding = Invoice::query()->outstanding()->whereNotNull('due_at')->get();

        $aging = [
            ['bucket' => 'Current', 'amount' => 0],
            ['bucket' => '1–15 days', 'amount' => 0],
            ['bucket' => '16–30 days', 'amount' => 0],
            ['bucket' => '30+ days', 'amount' => 0],
        ];

        foreach ($outstanding as $invoice) {
            $days = (int) now()->diffInDays($invoice->due_at, false) * -1;

            $index = match (true) {
                $days <= 0 => 0,
                $days <= 15 => 1,
                $days <= 30 => 2,
                default => 3,
            };

            $aging[$index]['amount'] += $invoice->amountDue();
        }

        $sent = Quote::query()->whereIn('status', ['sent', 'accepted', 'rejected', 'expired'])->count();
        $accepted = Quote::query()->where('status', 'accepted')->count();

        return [
            'months' => $months,
            'aging' => $aging,
            'outstandingTotal' => (int) $outstanding->sum('total'),
            'winRate' => $sent === 0 ? null : sprintf('%d%%', (int) round($accepted / $sent * 100)),
        ];
    }
}
