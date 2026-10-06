<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmOrder;
use App\Actions\RejectOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConfirmOrderRequest;
use App\Http\Requests\Admin\RejectOrderRequest;
use App\Models\Order;
use App\Support\SiteTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $tab = (string) $request->query('status', OrderStatus::Pending->value);
        $status = OrderStatus::tryFrom($tab);
        $search = trim((string) $request->query('q'));

        $orders = Order::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('number', 'like', $like));
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $selected = $request->filled('order')
            ? Order::query()->where('number', $request->query('order'))->first()
            : null;

        $selected ??= $orders->first();
        $selected?->load('affiliate.user:id,name');

        $monthStart = SiteTime::today()->startOfMonth()->toDateString();

        return view('admin.orders', [
            'orders' => $orders,
            'selected' => $selected,
            'receiptIsImage' => $selected?->receipt_path && ! Str::endsWith($selected->receipt_path, '.pdf'),
            'tab' => $status?->value ?? 'all',
            'search' => $search,
            'counts' => Order::query()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'month' => Order::query()->toBase()
                ->where('status', OrderStatus::Confirmed->value)
                ->where('confirmed_on', '>=', $monthStart)
                ->selectRaw('count(*) as confirmed, coalesce(sum(amount), 0) as revenue')
                ->selectRaw('sum(case when confirmed_on >= ? then 1 else 0 end) as this_week', [SiteTime::today()->subDays(6)->toDateString()])
                ->first(),
            'oldestPending' => Order::query()->where('status', OrderStatus::Pending->value)->oldest('updated_at')->value('updated_at'),
        ]);
    }

    /**
     * Receipts live on a private disk and are only ever served from here,
     * behind the admin middleware.
     */
    public function receipt(Order $order): StreamedResponse
    {
        abort_if($order->receipt_path === null || ! Storage::disk('local')->exists($order->receipt_path), 404);

        return Storage::disk('local')->response($order->receipt_path, null, ['Cache-Control' => 'private, no-store']);
    }

    public function confirm(ConfirmOrderRequest $request, Order $order, ConfirmOrder $confirmOrder): RedirectResponse
    {
        $confirmed = $confirmOrder->handle($order, $request->validated('admin_note'));

        return $this->backToOrder($order, $confirmed
            ? 'Payment confirmed. '.$order->name.' has been emailed.'
            : 'Order '.$order->number.' was already '.mb_strtolower($order->refresh()->status->label()).' — nothing changed.');
    }

    public function reject(RejectOrderRequest $request, Order $order, RejectOrder $rejectOrder): RedirectResponse
    {
        $rejected = $rejectOrder->handle($order, $request->validated('admin_note'));

        return $this->backToOrder($order, $rejected
            ? 'Order '.$order->number.' rejected. '.$order->name.' has been emailed.'
            : 'Order '.$order->number.' was already '.mb_strtolower($order->refresh()->status->label()).' — nothing changed.');
    }

    protected function backToOrder(Order $order, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.orders', ['status' => 'all', 'order' => $order->number])
            ->with('status', $message);
    }
}
