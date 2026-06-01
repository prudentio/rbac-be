<?php

namespace App\Http\Controllers\Api\Application\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application\Access\ApplicationRoleAccess;
use App\Models\RoleApplicationAccessView;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function roleIndex(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $roles = Role::query()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $totalItems = Role::count();

        $roleIds = $roles->pluck('id');

        $accesses = RoleApplicationAccessView::query()
            ->whereIn('role_id', $roleIds)
            ->get()
            ->groupBy('role_id');

        $data = $roles->map(function ($role) use ($accesses) {

            $applications = $accesses
                ->get($role->id, collect())
                ->map(fn ($item) => [
                    'id' => $item->application_id,
                    'name' => $item->application_name,
                ])
                ->values();

            return [
                'roleId' => $role->id,
                'roleName' => $role->name,
                'applications' => $applications,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
            ]
        ]);
    }
    
    public function roleShow($id)
    {
        $items = RoleApplicationAccessView::query()
            ->where('role_id', $id)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role access not found'
            ], 400);
        }

        $first = $items->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'roleId' => $first->role_id,
                'roleName' => $first->role_name,

                'applications' => $items
                    ->map(fn ($item) => [
                        'id' => $item->application_id,
                        'name' => $item->application_name,
                    ])
                    ->values(),
            ]
        ]);
    }

    public function roleStore(Request $request)
    {
        $request->validate([
            'roleId' => 'required|exists:roles,id',
            'applicationIds' => 'required|array|min:1',
            'applicationIds.*' => 'exists:applications,id',
        ]);

        $rows = collect($request->applicationIds)
            ->unique()
            ->map(fn ($applicationId) => [
                'role_id' => $request->roleId,
                'application_id' => $applicationId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        ApplicationRoleAccess::insert($rows);

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 201);
    }

    public function roleUpdate(Request $request, $id)
    {
        $request->validate([
            'applicationIds' => 'required|array|min:1',
            'applicationIds.*' => 'exists:applications,id',
        ]);

        $exists = ApplicationRoleAccess::where('role_id', $id)->exists();

        if (!$exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role access not found'
            ], 400);
        }

        DB::transaction(function () use ($request, $id) {
            ApplicationRoleAccess::where('role_id', $id)->delete();

            $rows = collect($request->applicationIds)
                ->unique()
                ->map(fn ($applicationId) => [
                    'role_id' => $id,
                    'application_id' => $applicationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            ApplicationRoleAccess::insert($rows);
        });

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }

    public function roleDestroy($id)
    {
        $deleted = ApplicationRoleAccess::where(
            'role_id',
            $id
        )->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role access not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role access deleted'
        ], 200);
    }
}