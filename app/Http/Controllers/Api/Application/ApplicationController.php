<?php

namespace App\Http\Controllers\Api\Application;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application\Application;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('perPage', 10);

        $query = Application::with('category');

        $totalItems = $query->count();

        $data = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'description' => $app->description,
                    'url' => $app->url,
                    'icon' => $app->icon,

                    'category' => $app->category ? [
                        'id' => $app->category->id,
                        'name' => $app->category->name,
                    ] : null,

                    'createdAt' => $app->created_at,
                    'updatedAt' => $app->updated_at,
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
        $app = Application::with(['category', 'creator'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $app->id,
                'name' => $app->name,
                'description' => $app->description,
                'url' => $app->url,
                'icon' => $app->icon,

                'category' => $app->category ? [
                    'id' => $app->category->id,
                    'name' => $app->category->name,
                ] : null,

                'creator' => $app->creator ? [
                    'id' => $app->creator->id,
                    'name' => $app->creator->name,
                ] : null,

                'createdAt' => $app->created_at,
                'updatedAt' => $app->updated_at,
            ]
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'url' => 'required|string|max:255',
            'categoryId' => 'required|exists:application_categories,id',
            'icon' => 'nullable|file|mimes:png,jpg,jpeg|max:5120'
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'url' => $request->url,
            'category_id' => $request->categoryId,
        ];

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')
                ->store('application-icons', 'public');
        }

        $app = Application::create($data);

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'url' => 'sometimes|string|max:255',
            'categoryId' => 'sometimes|exists:application_categories,id',
            'icon' => 'sometimes|file|mimes:png,jpg,jpeg|max:5120',
        ]);

        $old = Application::where('id', $id)->first();

        if (!$old) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 400);
        }

        $data = [
            'name' => $request->name ?? $old->name,
            'description' => $request->description ?? $old->description,
            'url' => $request->url ?? $old->url,
            'category_id' => $request->categoryId ?? $old->category_id,
        ];

        if ($request->hasFile('icon')) {
            if ($old->icon) {
                Storage::disk('public')->delete($old->icon);
            }

            $data['icon'] = $request->file('icon')
                ->store('application-icons', 'public');
        }

        $updated = Application::where('id', $id)->update($data);

        if (!$updated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => 'ok'
        ], 200);
    }

   public function destroy($id)
    {
        $old = Application::where('id', $id)->first();

        if ($old && $old->icon) {
            Storage::disk('public')->delete($old->icon);
        }

        $deleted = Application::where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'status' => 'error',
                'message' => 'Application not found'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Application deleted'
        ], 200);
    }
}