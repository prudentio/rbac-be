<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = Department::query();

        $totalItems = $query->count();

        $data = $query
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'createdAt' => $item->created_at,
                'updatedAt' => $item->updated_at,
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

    public function show($id)
    {
        $department = Department::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $department->id,
                'name' => $department->name,
                'createdAt' => $department->created_at,
                'updatedAt' => $department->updated_at,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name'
        ]);

        Department::create([
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:departments,name'
         ]);

        $updated = Department::where('id', $id)->update([
            'name' => $request->name
        ]);

        if (!$updated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }

    public function destroy($id)
    {
        $deleted = Department::where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }
}