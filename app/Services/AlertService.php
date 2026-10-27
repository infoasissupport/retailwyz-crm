<?php

namespace App\Services;

use App\Enums\AdjustmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\IncentiveStatus;
use App\Models\Delivery;
use App\Models\Incentive;
use App\Models\LeadFollowUp;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Header bell: things that need someone's attention, built from existing
 * data and the user's permissions. Cached briefly per user so it costs
 * nothing on most page loads.
 */
class AlertService
{
    public function __construct(private ReportService $reports) {}

    /**
     * @return list<array{label: string, count: int, url: string, icon: string, tone: string}>
     */
    public function for(User $user): array
    {
        return Cache::remember("alerts.user.{$user->id}", 60, fn () => $this->build($user));
    }

    private function build(User $user): array
    {
        $alerts = [];

        if ($user->can('leads.view')) {
            $due = LeadFollowUp::query()->visibleTo($user)
                ->where('status', LeadFollowUp::PENDING)
                ->where('assigned_to', $user->id)
                ->where('scheduled_at', '<=', today()->endOfDay())
                ->count();
            $alerts[] = ['label' => 'Follow-ups due', 'count' => $due, 'url' => route('leads.follow-ups.index', ['view' => 'due', 'mine' => 1]), 'icon' => 'bi-telephone-outbound', 'tone' => 'warning'];
        }

        if ($user->can('deliveries.view')) {
            $deliveries = Delivery::query()->visibleTo($user)
                ->whereIn('status', [DeliveryStatus::Pending->value, DeliveryStatus::Failed->value])
                ->whereDate('scheduled_date', '<=', today())
                ->count();
            $alerts[] = ['label' => 'Deliveries due or overdue', 'count' => $deliveries, 'url' => route('deliveries.index', ['status' => 'pending']), 'icon' => 'bi-truck', 'tone' => 'info'];
        }

        if ($user->can('inventory.view')) {
            $low = $this->reports->lowStock($user->canAccessAllBranches() ? null : ($user->branch_id ?? 0))->count();
            $alerts[] = ['label' => 'Products at or below minimum stock', 'count' => $low, 'url' => route('inventory.index', ['low_stock' => 1]), 'icon' => 'bi-exclamation-triangle', 'tone' => 'critical'];
        }

        if ($user->can('inventory.approve')) {
            $pending = StockAdjustment::query()->visibleTo($user)
                ->where('status', AdjustmentStatus::Pending->value)
                ->where('created_by', '!=', $user->id)
                ->count();
            $alerts[] = ['label' => 'Stock adjustments to approve', 'count' => $pending, 'url' => route('inventory.adjustments.index', ['status' => 'pending']), 'icon' => 'bi-sliders', 'tone' => 'warning'];
        }

        if ($user->can('incentives.approve')) {
            $incentives = Incentive::query()->visibleTo($user)
                ->where('status', IncentiveStatus::Pending->value)
                ->where('created_by', '!=', $user->id)
                ->count();
            $alerts[] = ['label' => 'Incentives to approve', 'count' => $incentives, 'url' => route('staff.incentives.index', ['status' => 'pending']), 'icon' => 'bi-trophy', 'tone' => 'warning'];
        }

        return array_values(array_filter($alerts, fn ($a) => $a['count'] > 0));
    }
}
