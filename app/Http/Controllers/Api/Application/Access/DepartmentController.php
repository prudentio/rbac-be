<?php

namespace App\Http\Controllers\Api\Application\Access;

use App\Models\DepartmentApplicationAccessView;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application\Access\ApplicationDepartmentAccess;
use App\Models\Department;

class DepartmentController extends Controller
{

    public function departmentIndex(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $departments = Department::query()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $totalItems = Department::count();

        $departmentIds = $departments->pluck('id');

        $accesses = DepartmentApplicationAccessView::query()
            ->whereIn('department_id', $departmentIds)
            ->get()
            ->groupBy('department_id');

        $data = $departments->map(function ($department) use ($accesses) {

            $applications = $accesses
                ->get($department->id, collect())
                ->map(fn ($item) => [
                    'id' => $item->application_id,
                    'name' => $item->application_name,
                ])
                ->values();

            return [
                'departmentId' => $department->id,
                'departmentName' => $department->name,
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
        ], 200);
    }

    public function departmentShow($id)
    {
        $items = DepartmentApplicationAccessView::query()
            ->where('department_id', $id)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department access not found'
            ], 400);
        }

        $first = $items->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'departmentId' => $first->department_id,
                'departmentName' => $first->department_name,

                'applications' => $items
                    ->map(fn ($item) => [
                        'id' => $item->application_id,
                        'name' => $item->application_name,
                    ])
                    ->values(),
            ]
        ], 200);
    }

    public function departmentStore(Request $request)
    {
        $request->validate([
            'departmentId' => 'required|exists:departments,id',
            'applicationIds' => 'required|array|min:1',
            'applicationIds.*' => 'exists:applications,id',
        ]);

        foreach ($request->applicationIds as $applicationId) {
            ApplicationDepartmentAccess::create([
                'department_id' => $request->departmentId,
                'application_id' => $applicationId,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 201);
    }

    public function departmentUpdate(Request $request, $id)
    {
        $request->validate([
            'applicationIds' => 'required|array|min:1',
            'applicationIds.*' => 'exists:applications,id',
        ]);

        $exists = ApplicationDepartmentAccess::where('department_id', $id)->exists();

        if (!$exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department access not found'
            ], 400);
        }

        ApplicationDepartmentAccess::where('department_id', $id)->delete();

        foreach ($request->applicationIds as $applicationId) {
            ApplicationDepartmentAccess::create([
                'department_id' => $id,
                'application_id' => $applicationId,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }

    public function departmentDestroy($id)
    {
        $deleted = ApplicationDepartmentAccess::where(
            'department_id',
            $id
        )->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department access not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Department access deleted'
        ], 200);
    }
    }