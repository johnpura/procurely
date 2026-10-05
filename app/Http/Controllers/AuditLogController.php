<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Bid;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:60'],
            'actor' => ['nullable', 'string', 'max:100'],
            'bid' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $like = fn (string $v) => '%'.addcslashes($v, '%_\\').'%';
        $query = AuditLog::query()->orderByDesc('occurred_at')->orderByDesc('id');

        if (filled($data['q'] ?? null)) {
            $query->where('summary', 'like', $like($data['q']));
        }
        if (filled($data['action'] ?? null)) {
            $query->where('action', $data['action']);
        }
        if (filled($data['actor'] ?? null)) {
            $query->where('actor_name', 'like', $like($data['actor']));
        }
        if (filled($data['bid'] ?? null)) {
            $query->where('bid_id', Bid::where('reference_number', $data['bid'])->value('id') ?? 0);
        }
        if (filled($data['from'] ?? null)) {
            $query->whereDate('occurred_at', '>=', $data['from']);
        }
        if (filled($data['to'] ?? null)) {
            $query->whereDate('occurred_at', '<=', $data['to']);
        }

        return view('audit.index', [
            'logs' => $query->paginate(50)->withQueryString(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}