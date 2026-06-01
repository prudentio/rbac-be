<?php

namespace App\Http\Controllers\Api\Application\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserApplicationAccessView;
use App\Models\Application\Application;

class MatrixController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $sourceTypes = (array) $request->query('sourceTypes', []);

        $usersQuery = User::with(['department', 'role']);

        $totalItems = $usersQuery->count();

        $users = $usersQuery
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $data = $users->map(function ($user) use ($sourceTypes) {

            $accessQuery = UserApplicationAccessView::query()
                ->where('user_id', $user->id);

            if (!empty($sourceTypes)) {
                $accessQuery->whereIn('source_type', $sourceTypes);
            }

            $access = $accessQuery->get();

            $applicationIds = $access->pluck('application_id')->unique();

            $applications = Application::query()
                ->whereIn('id', $applicationIds)
                ->get(['id', 'name', 'url', 'icon']);

            return [
                'userId' => $user->id,
                'name' => $user->name,

                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                ] : null,

                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ] : null,

                'applications' => $applications->map(fn ($app) => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'url' => $app->url,
                    'icon' => $app->icon,
                ])->values(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems
            ]
        ], 200);
    }

    public function show(Request $request, $userId)
    {

        $sourceTypes = $request->query('sourceTypes', []);

        if (is_null($sourceTypes)) {
            $sourceTypes = [];
        }

        if (!is_array($sourceTypes)) {
            $sourceTypes = [$sourceTypes];
        }

        $sourceTypes = array_values(array_unique($sourceTypes));


        $user = User::with(['department', 'role'])->find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 400);
        }


        $accessQuery = UserApplicationAccessView::query()
            ->where('user_id', $user->id);

        if (!empty($sourceTypes)) {
            $accessQuery->whereIn('source_type', $sourceTypes);
        }

        $access = $accessQuery->get();

        $applicationIds = $access
            ->pluck('application_id')
            ->unique()
            ->values();

        $applications = Application::query()
            ->whereIn('id', $applicationIds)
            ->get(['id', 'name', 'url', 'icon']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'userId' => $user->id,
                'name' => $user->name,

                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                ] : null,

                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ] : null,

                'applications' => $applications->map(fn ($app) => [
                    'id' => $app->id,
                    'name' => $app->name,
                    'url' => $app->url,
                    'icon' => $app->icon,
                ])->values(),
            ]
        ], 200);
    }
}