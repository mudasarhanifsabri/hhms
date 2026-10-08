<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;

class UserActivityLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Super Administrator', 'Backend IT', 'Admin']), 403, 'Only authorized administrators can view user activity logs.');
        $filters = $request->validate([
            'event' => 'nullable|in:login_success,login_failed,logout,admin_action', 'user_id' => 'nullable|uuid',
            'ip' => 'nullable|string|max:45', 'date_from' => 'nullable|date', 'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        $logs = UserActivityLog::with('user')->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['ip'] ?? null, fn ($q, $v) => $q->where('ip_address', 'like', '%'.$v.'%'))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('occurred_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('occurred_at', '<=', $v))
            ->latest('occurred_at')->paginate(50)->withQueryString();
        $users = User::withTrashed()->where('role', 'admin')->orderBy('name')->get(['id', 'name', 'email']);
        return view('admin.activity-logs.index', compact('logs', 'users', 'filters'));
    }
}
