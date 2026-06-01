<?php

namespace App\Http\Controllers\Api\Application\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application\Access\ApplicationUserAccess;
use App\Models\User;
use App\Models\UserApplicationAccessView;
use App\Models\Application\Application;
use App\Models\Application\Access\ApplicationDepartmentAccess;
use App\Models\Application\Access\ApplicationRoleAccess;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function userIndex(Request $request)
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('perPage', 10), 1), 100);

        $userIdsQuery = ApplicationUserAccess::query()
            ->select('user_id')
            ->distinct();

        $totalItems = (clone $userIdsQuery)->count();

        $userIds = $userIdsQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->pluck('user_id');

        $items = ApplicationUserAccess::query()
            ->whereIn('user_id', $userIds)
            ->with(['user:id,name', 'application:id,name'])
            ->get()
            ->groupBy('user_id');

        $data = $items
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'userId' => $first->user->id,
                    'name' => $first->user->name,

                    'allowedApps' => $items
                        ->where('is_denied', false)
                        ->map(fn ($item) => [
                            'id' => $item->application->id,
                            'name' => $item->application->name,
                        ])
                        ->values(),

                    'prohibitedApps' => $items
                        ->where('is_denied', true)
                        ->map(fn ($item) => [
                            'id' => $item->application->id,
                            'name' => $item->application->name,
                        ])
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
            ],
        ]);
    }

    public function applicationIndex(Request $request, $id)
    {
        $mode = $request->query('mode');

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 400);
        }

        $departmentApplicationIds = collect();

        if ($user->department_id) {
            $departmentApplicationIds = ApplicationDepartmentAccess::query()
                ->where('department_id', $user->department_id)
                ->pluck('application_id');
        }

        $roleApplicationIds = collect();

        if ($user->role_id) {
            $roleApplicationIds = ApplicationRoleAccess::query()
                ->where('role_id', $user->role_id)
                ->pluck('application_id');
        }

        $inheritedApplicationIds = $departmentApplicationIds
            ->merge($roleApplicationIds)
            ->unique()
            ->values();

        if ($mode === 'prohibited') {
            $applicationIds = $inheritedApplicationIds;

            $applications = Application::query()
                ->whereIn('id', $applicationIds)
                ->get(['id', 'name', 'url', 'icon']);
        } elseif ($mode === 'allowed') {
            $prohibitedApplicationIds = ApplicationUserAccess::query()
                ->where('user_id', $user->id)
                ->where('is_denied', true)
                ->pluck('application_id');

            $excludedApplicationIds = $inheritedApplicationIds
                ->merge($prohibitedApplicationIds)
                ->unique()
                ->values();

            $applications = Application::query()
                ->whereNotIn('id', $excludedApplicationIds)
                ->get(['id', 'name', 'url', 'icon']);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid mode. Use allowed or prohibited.'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => $applications->map(fn ($app) => [
                'id' => $app->id,
                'name' => $app->name,
                'url' => $app->url,
                'icon' => $app->icon,
            ])->values(),
        ], 200);
    }

    public function userShow($id)
    {
        $items = ApplicationUserAccess::query()
            ->where('user_id', $id)
            ->with(['user:id,name', 'application:id,name'])
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'User access not found'
            ], 400);
        }

        $first = $items->first();

        return response()->json([
            'status' => 'success',

            'data' => [
                'userId' => $first->user->id,
                'name' => $first->user->name,

                'allowedApps' => $items
                    ->where('is_denied', false)
                    ->map(fn ($item) => [
                        'id' => $item->application->id,
                        'name' => $item->application->name,
                    ])
                    ->values(),

                'prohibitedApps' => $items
                    ->where('is_denied', true)
                    ->map(fn ($item) => [
                        'id' => $item->application->id,
                        'name' => $item->application->name,
                    ])
                    ->values(),
            ]
        ]);
    }

    private function buildUserAccessRows(string $userId, array $allowedApps = [], array $prohibitedApps = []): array
    {
        $now = now();

        $allowedRows = collect($allowedApps)
            ->unique()
            ->map(fn ($applicationId) => [
                'user_id' => $userId,
                'application_id' => $applicationId,
                'is_denied' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $prohibitedRows = collect($prohibitedApps)
            ->unique()
            ->map(fn ($applicationId) => [
                'user_id' => $userId,
                'application_id' => $applicationId,
                'is_denied' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        return $allowedRows
            ->merge($prohibitedRows)
            ->values()
            ->all();
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',

            'allowedApps' => 'array',
            'allowedApps.*' => 'exists:applications,id',

            'prohibitedApps' => 'array',
            'prohibitedApps.*' => 'exists:applications,id',
        ]);

        $rows = $this->buildUserAccessRows(
            $request->userId,
            $request->allowedApps ?? [],
            $request->prohibitedApps ?? []
        );

        if (!empty($rows)) {
            ApplicationUserAccess::insert($rows);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 201);
    }

    public function userUpdate(Request $request, $id)
    {
        $request->validate([
            'allowedApps' => 'array',
            'allowedApps.*' => 'exists:applications,id',

            'prohibitedApps' => 'array',
            'prohibitedApps.*' => 'exists:applications,id',
        ]);

        $exists = ApplicationUserAccess::where('user_id', $id)->exists();

        if (!$exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'User access not found'
            ], 400);
        }

        DB::transaction(function () use ($request, $id) {
            ApplicationUserAccess::where('user_id', $id)->delete();

            $rows = $this->buildUserAccessRows(
                $id,
                $request->allowedApps ?? [],
                $request->prohibitedApps ?? []
            );

            if (!empty($rows)) {
                ApplicationUserAccess::insert($rows);
            }
        });

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ]);
    }

    public function userDestroy($id)
    {
        $deleted = ApplicationUserAccess::where('user_id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'User access not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User access deleted'
        ]);
    }
}