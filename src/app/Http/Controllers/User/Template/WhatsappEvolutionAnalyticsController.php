<?php

namespace App\Http\Controllers\User\Template;

use App\Http\Controllers\Controller;
use App\Models\EvolutionButtonClick;
use App\Models\EvolutionHttpActionLog;
use App\Models\EvolutionWhatsappTemplate;
use Illuminate\Http\Request;

class WhatsappEvolutionAnalyticsController extends Controller
{
    public function clicks(Request $request)
    {
        $title = translate('Evolution Button Clicks');
        $userId = auth()->id();

        $query = EvolutionButtonClick::query()->where('user_id', $userId);

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->integer('template_id'));
        }
        if ($request->filled('row_id')) {
            $query->where('selected_row_id', $request->input('row_id'));
        }
        if ($request->filled('date')) {
            [$from, $to] = array_pad(explode(' - ', $request->input('date')), 2, null);
            if ($from) $query->whereDate('created_at', '>=', $from);
            if ($to) $query->whereDate('created_at', '<=', $to);
        }

        $clicks = $query->latest('id')->paginate(paginateNumber(site_settings('paginate_number')))->onEachSide(1)->appends($request->all());
        $templates = EvolutionWhatsappTemplate::where('user_id', $userId)->orderBy('name')->get(['id','name']);

        return view('user.template.whatsapp.evolution.analytics.clicks', compact('title', 'clicks', 'templates'));
    }

    public function clicksSummary(Request $request)
    {
        $title = translate('Evolution Button Clicks Summary');
        $userId = auth()->id();

        $base = EvolutionButtonClick::query()->where('user_id', $userId);
        if ($request->filled('template_id')) {
            $base->where('template_id', $request->integer('template_id'));
        }
        if ($request->filled('date')) {
            [$from, $to] = array_pad(explode(' - ', $request->input('date')), 2, null);
            if ($from) $base->whereDate('created_at', '>=', $from);
            if ($to) $base->whereDate('created_at', '<=', $to);
        }

        $rows = $base->selectRaw('template_id, selected_row_id, COALESCE(MAX(row_title), "") as row_title, COUNT(*) as clicks')
            ->groupBy('template_id', 'selected_row_id')
            ->orderByDesc('clicks')
            ->paginate(paginateNumber(site_settings('paginate_number')))
            ->onEachSide(1)
            ->appends($request->all());

        $templates = EvolutionWhatsappTemplate::where('user_id', $userId)->orderBy('name')->get(['id','name']);

        return view('user.template.whatsapp.evolution.analytics.clicks_summary', compact('title', 'rows', 'templates'));
    }

    public function httpLogs(Request $request)
    {
        $title = translate('Evolution HTTP Action Logs');
        $userId = auth()->id();
        $query = EvolutionHttpActionLog::query()->where('user_id', $userId);

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->integer('template_id'));
        }
        if ($request->filled('status')) {
            if ($request->input('status') === 'error') {
                $query->whereNull('response_status')->orWhereNotNull('error_message');
            } elseif ($request->input('status') === 'success') {
                $query->whereNotNull('response_status');
            }
        }
        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->input('method')));
        }
        if ($request->filled('date')) {
            [$from, $to] = array_pad(explode(' - ', $request->input('date')), 2, null);
            if ($from) $query->whereDate('created_at', '>=', $from);
            if ($to) $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->latest('id')->paginate(paginateNumber(site_settings('paginate_number')))->onEachSide(1)->appends($request->all());
        $templates = EvolutionWhatsappTemplate::where('user_id', $userId)->orderBy('name')->get(['id','name']);

        return view('user.template.whatsapp.evolution.analytics.http_logs', compact('title', 'logs', 'templates'));
    }
}


