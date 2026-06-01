<?php

namespace App\Http\Controllers\Api\Application;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application\ApplicationCategory;

class CategoryController extends Controller
{
   public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = ApplicationCategory::query();

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
        $category = ApplicationCategory::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'createdAt' => $category->created_at,
                'updatedAt' => $category->updated_at,
            ]
        ], 200);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = ApplicationCategory::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $data = $request->only(['name']);

        $updated = ApplicationCategory::where('id', $id)->update($data);

        if (!$updated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }
    public function destroy($id)
    {
        $deleted = ApplicationCategory::where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted'
        ], 200);
    }
}