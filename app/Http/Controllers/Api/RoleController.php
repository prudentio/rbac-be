<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = Role::query();

        $totalItems = $query->count();

        $data = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'isSuperAdmin' => (bool) $item->is_super_admin,
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
        $role = Role::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'isSuperAdmin' => (bool) $role->is_super_admin,
                'createdAt' => $role->created_at,
                'updatedAt' => $role->updated_at,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'isSuperAdmin' => 'boolean',
        ]);

        Role::create([
            'name' => $request->name,
            'is_super_admin' => $request->isSuperAdmin ?? false,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => "ok"
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
            'isSuperAdmin' => 'sometimes|boolean'
        ]);

        $updated = Role::where('id', $id)->update([
            'name' => $request->name,
            'is_super_admin' => $request->isSuperAdmin,
        ]);

        if (!$updated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }

    public function destroy($id)
    {
        $deleted = Role::where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Role not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted'
        ], 200);
    }
}