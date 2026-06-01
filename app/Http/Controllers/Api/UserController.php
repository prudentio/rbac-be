<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\UserApplicationAccessView;
use App\Models\Application\Application;

class UserController extends Controller
{
    private function normalizeSourceTypes($sourceTypes): array
    {
        if (is_null($sourceTypes)) {
            return [];
        }

        if (!is_array($sourceTypes)) {
            $sourceTypes = [$sourceTypes];
        }

        return array_values(array_unique($sourceTypes));
    }

    private function mapApplication($app): array
    {
        return [
            'id' => $app->id,
            'name' => $app->name,
            'url' => $app->url,
            'icon' => $app->icon,
            'category' => $app->category ? [
                'id' => $app->category->id,
                'name' => $app->category->name,
            ] : null,
        ];
    }

    private function getUserApplications(string $userId, array $sourceTypes = [])
    {
        $accessQuery = UserApplicationAccessView::query()
            ->where('user_id', $userId);

        if (!empty($sourceTypes)) {
            $accessQuery->whereIn('source_type', $sourceTypes);
        }

        $applicationIds = $accessQuery
            ->distinct()
            ->pluck('application_id');

        return Application::query()
            ->with('category')
            ->whereIn('id', $applicationIds)
            ->get(['id', 'name', 'url', 'icon', 'category_id'])
            ->map(fn ($app) => $this->mapApplication($app))
            ->values();
    }

    private function getUsersApplications($userIds, array $sourceTypes = [])
    {
        $accessQuery = UserApplicationAccessView::query()
            ->whereIn('user_id', $userIds);

        if (!empty($sourceTypes)) {
            $accessQuery->whereIn('source_type', $sourceTypes);
        }

        $accessRows = $accessQuery
            ->get(['user_id', 'application_id']);

        $applicationIds = $accessRows
            ->pluck('application_id')
            ->unique()
            ->values();

        $applications = Application::query()
            ->with('category')
            ->whereIn('id', $applicationIds)
            ->get(['id', 'name', 'url', 'icon', 'category_id'])
            ->keyBy('id');

        return $accessRows
            ->groupBy('user_id')
            ->map(function ($rows) use ($applications) {
                return $rows
                    ->pluck('application_id')
                    ->unique()
                    ->map(function ($applicationId) use ($applications) {
                        $app = $applications->get($applicationId);

                        if (!$app) {
                            return null;
                        }

                        return $this->mapApplication($app);
                    })
                    ->values();
            });
    }

    private function mapUser($user, array $sourceTypes = [], $applications = null): array
    {
        $result = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'createdAt' => $user->created_at,
            'updatedAt' => $user->updated_at,

            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
            ] : null,

            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'isSuperAdmin' => $user->role->is_super_admin,
            ] : null,
        ];

        if (!empty($sourceTypes)) {
            $result['applications'] = $applications ?? $this->getUserApplications(
                $user->id,
                $sourceTypes
            );
        }

        return $result;
    }

    public function index(Request $request)
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('perPage', 10), 1), 100);

        $sourceTypes = $this->normalizeSourceTypes(
            $request->query('sourceTypes', [])
        );

        $usersQuery = User::with(['department', 'role']);

        $totalItems = $usersQuery->count();

        $users = $usersQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $applicationsByUser = collect();

        if (!empty($sourceTypes)) {
            $applicationsByUser = $this->getUsersApplications(
                $users->pluck('id'),
                $sourceTypes
            );
        }

        $data = $users
            ->map(fn ($user) => $this->mapUser(
                $user,
                $sourceTypes,
                $applicationsByUser->get($user->id, collect())
            ))
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems,
            ],
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $sourceTypes = $this->normalizeSourceTypes(
            $request->query('sourceTypes', [])
        );

        $user = User::with(['department', 'role'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->mapUser($user, $sourceTypes),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'departmentId' => 'nullable|exists:departments,id',
            'roleId' => 'nullable|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['departmentId'] ?? null,
            'role_id' => $validated['roleId'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6',
            'departmentId' => 'sometimes|nullable|exists:departments,id',
            'roleId' => 'sometimes|nullable|exists:roles,id',
        ]);

        $data = [
            'name' => $validated['name'] ?? null,
            'email' => $validated['email'] ?? null,
            'department_id' => $validated['departmentId'] ?? null,
            'role_id' => $validated['roleId'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $updated = User::where('id', $id)->update(
            array_filter($data, fn ($v) => $v !== null)
        );

        if (!$updated) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User not found or not updated',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok',
        ], 200);
    }

    public function destroy($id)
    {
        $deleted = User::where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User not found or not deleted',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok',
        ], 200);
    }
}