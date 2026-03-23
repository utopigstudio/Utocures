<?php

namespace App\Http\Controllers;

use App\Http\Requests\Audit\AuditIndexRequest;
use OwenIt\Auditing\Models\Audit;
use Inertia\Inertia;
use Illuminate\Support\Str;

class AuditController extends Controller
{
    private const LIST_FIELDS = ['id', 'user_id', 'event', 'created_at'];

    public function index(AuditIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir  = $request->validated('dir') ?? 'desc';

        $audits = Audit::when(in_array($sort, self::LIST_FIELDS), fn($q) => $q->orderBy($sort, $dir))
            ->with('user:id,name,avatar')
            ->paginate(50)
            ->withQueryString();

        $audits = $audits->getCollection()->transform(function ($audit) {
            return $this->transformData($audit);
        });

        return Inertia::render('audits/List', [
            'data' => $audits,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function show($id)
    {
        $audit = Audit::with('user:id,name')->findOrFail($id);

        return Inertia::render('audits/View', [
            'audit' => $this->transformData($audit),
        ]);
    }

    private function resolveResourceName($audit, $realModel)
    {
        if ($realModel) {
            if (method_exists($realModel, 'user') && $realModel->user) {
                return $realModel->user->name;
            } else if (isset($realModel->name)) {
                return $realModel->name;
            }
        }

        if (isset($audit->new_values['name'])) {
            return $audit->new_values['name'];
        }

        if (isset($audit->old_values['name'])) {
            return $audit->old_values['name'];
        }

        return "ID {$audit->auditable_id}";
    }

    private function transformData($audit)
    {
        $realModel = null;
        if (class_exists($audit->auditable_type)) {
            $realModel = $audit->auditable_type::with([])->find($audit->auditable_id);
        }

        $resourceKey = class_basename($audit->auditable_type);

        $audit->resource = __("audit.resources.$resourceKey");
        $audit->action = __("audit.events.{$audit->event}") ?: __("audit.fallback");
        $audit->trans_prefix = $resourceKey ? Str::snake($resourceKey) : 'audit';
        $audit->resource_name = $this->resolveResourceName($audit, $realModel);
        $audit->created_at_formatted = $audit->created_at->format('d/m/Y H:i:s');

        return $audit;
    }
}
