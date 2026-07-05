<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const ENTITY_TYPES = [
        'User' => User::class,
        'Vendor' => VendorProfile::class,
        'Product' => Product::class,
        'Category' => Category::class,
    ];

    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'admin_id' => $request->input('admin_id'),
            'action' => $request->input('action'),
            'auditable_type' => $request->input('auditable_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        return view('admin.audit-logs.index', [
            'logs' => $this->auditLogs->filtered($filters),
            'filters' => $filters,
            'admins' => User::where('role', 'admin')->orderBy('name')->get(),
            'entityTypes' => self::ENTITY_TYPES,
            'actions' => AuditAction::cases(),
        ]);
    }
}
