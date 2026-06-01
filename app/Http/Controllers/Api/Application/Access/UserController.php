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

class UserController extends Controller
{
    public function userIndex(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $grouped = ApplicationUserAccess::query()
            ->with(['user:id,name', 'application:id,name'])
            ->get()
            ->groupBy('user_id')
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

        $totalItems = $grouped->count();

        $data = $grouped
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $data,

            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'totalItems' => $totalItems
            ]
        ]);
    }

    // public function applicationIndex(Request $request, $id)
    // {
    //     $sourceTypes = $request->query('sourceTypes', []);

    //     if (is_null($sourceTypes)) {
    //         $sourceTypes = [];
    //     }

    //     if (!is_array($sourceTypes)) {
    //         $sourceTypes = [$sourceTypes];
    //     }

    //     $sourceTypes = array_values(array_unique($sourceTypes));

    //     $isNegation = $request->boolean('isNegation');

    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'User not found'
    //         ], 400);
    //     }

    //     $accessQuery = UserApplicationAccessView::query()
    //         ->where('user_id', $id);

    //     if (!empty($sourceTypes)) {
    //         if ($isNegation) {
    //             $accessQuery->whereNotIn('source_type', $sourceTypes);
    //         } else {
    //             $accessQuery->whereIn('source_type', $sourceTypes);
    //         }
    //     }

    //     $applicationIds = $accessQuery
    //         ->pluck('application_id')
    //         ->unique()
    //         ->values();

    //     $applications = Application::query()
    //         ->whereIn('id', $applicationIds)
    //         ->get(['id', 'name', 'url', 'icon']);

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $applications->map(fn ($app) => [
    //             'id' => $app->id,
    //             'name' => $app->name,
    //             'url' => $app->url,
    //             'icon' => $app->icon,
    //         ])->values(),
    //     ], 200);
    // }

    // public function applicationIndex(Request $request, $id)
    // {
    //     $sourceTypes = $request->query('sourceTypes', []);

    //     if (is_null($sourceTypes)) {
    //         $sourceTypes = [];
    //     }

    //     if (!is_array($sourceTypes)) {
    //         $sourceTypes = [$sourceTypes];
    //     }

    //     $sourceTypes = array_values(array_unique($sourceTypes));

    //     $isNegation = $request->boolean('isNegation');

    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'User not found'
    //         ], 400);
    //     }

    //     $allSourceTypes = ['Department', 'Role', 'Special'];

    //     if (empty($sourceTypes)) {
    //         $selectedSourceTypes = $allSourceTypes;
    //     } else if ($isNegation) {
    //         $selectedSourceTypes = array_values(array_diff($allSourceTypes, $sourceTypes));
    //     } else {
    //         $selectedSourceTypes = $sourceTypes;
    //     }

    //     $applicationIds = collect();

    //     if (in_array('Department', $selectedSourceTypes) && $user->department_id) {
    //         $departmentApplicationIds = ApplicationDepartmentAccess::query()
    //             ->where('department_id', $user->department_id)
    //             ->pluck('application_id');

    //         $applicationIds = $applicationIds->merge($departmentApplicationIds);
    //     }

    //     if (in_array('Role', $selectedSourceTypes) && $user->role_id) {
    //         $roleApplicationIds = ApplicationRoleAccess::query()
    //             ->where('role_id', $user->role_id)
    //             ->pluck('application_id');

    //         $applicationIds = $applicationIds->merge($roleApplicationIds);
    //     }

    //     if (in_array('Special', $selectedSourceTypes)) {
    //         $specialApplicationIds = ApplicationUserAccess::query()
    //             ->where('user_id', $user->id)
    //             ->where('is_denied', false)
    //             ->pluck('application_id');

    //         $applicationIds = $applicationIds->merge($specialApplicationIds);
    //     }

    //     $applicationIds = $applicationIds
    //         ->unique()
    //         ->values();

    //     if ($applicationIds->isEmpty()) {
    //         return response()->json([
    //             'status' => 'success',
    //             'data' => []
    //         ], 200);
    //     }

    //     $applications = Application::query()
    //         ->whereIn('id', $applicationIds)
    //         ->get(['id', 'name', 'url', 'icon']);

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $applications->map(fn ($app) => [
    //             'id' => $app->id,
    //             'name' => $app->name,
    //             'url' => $app->url,
    //             'icon' => $app->icon,
    //         ])->values(),
    //     ], 200);
    // }
      
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

    public function userStore(Request $request)
    {
        $request->validate([
            'userId' => 'required|exists:users,id',

            'allowedApps' => 'array',
            'allowedApps.*' => 'exists:applications,id',

            'prohibitedApps' => 'array',
            'prohibitedApps.*' => 'exists:applications,id',
        ]);

        foreach ($request->allowedApps ?? [] as $applicationId) {

            ApplicationUserAccess::create([
                'user_id' => $request->userId,
                'application_id' => $applicationId,
                'is_denied' => false,
            ]);
        }

        foreach ($request->prohibitedApps ?? [] as $applicationId) {

            ApplicationUserAccess::create([
                'user_id' => $request->userId,
                'application_id' => $applicationId,
                'is_denied' => true,
            ]);
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

        ApplicationUserAccess::where('user_id', $id)->delete();

        foreach ($request->allowedApps ?? [] as $applicationId) {

            ApplicationUserAccess::create([
                'user_id' => $id,
                'application_id' => $applicationId,
                'is_denied' => false,
            ]);
        }

        foreach ($request->prohibitedApps ?? [] as $applicationId) {

            ApplicationUserAccess::create([
                'user_id' => $id,
                'application_id' => $applicationId,
                'is_denied' => true,
            ]);
        }

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