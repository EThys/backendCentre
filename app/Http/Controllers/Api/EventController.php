<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $status = $request->get('status');
        $type = $request->get('type');
        $category = $request->get('category');

        $query = Event::query();

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($category) {
            $query->where('category', $category);
        }

        $events = $query->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'pagination' => [
                'page' => $events->currentPage(),
                'limit' => $events->perPage(),
                'total' => $events->total(),
                'totalPages' => $events->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Decode JSON strings for arrays before validation
            $requestData = $request->all();
            
            // Handle JSON strings for arrays
            if (isset($requestData['speakers']) && is_string($requestData['speakers'])) {
                $decoded = json_decode($requestData['speakers'], true);
                $requestData['speakers'] = $decoded !== null ? $decoded : [];
            }
            if (isset($requestData['agenda']) && is_string($requestData['agenda'])) {
                $decoded = json_decode($requestData['agenda'], true);
                $requestData['agenda'] = $decoded !== null ? $decoded : [];
            }
            if (isset($requestData['tags']) && is_string($requestData['tags'])) {
                $decoded = json_decode($requestData['tags'], true);
                $requestData['tags'] = $decoded !== null ? $decoded : [];
            }
            
            // Convert registration_required from string to boolean if needed
            if (isset($requestData['registration_required'])) {
                if (is_string($requestData['registration_required'])) {
                    $requestData['registration_required'] = $requestData['registration_required'] === '1' || $requestData['registration_required'] === 'true' || $requestData['registration_required'] === 'on';
                }
            }

            // Validation rules
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'content' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'type' => 'required|in:conference,workshop,seminar,webinar,other',
                'status' => 'nullable|in:upcoming,ongoing,completed,cancelled',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'location' => 'required|string|max:255',
                'address' => 'nullable|string',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:3',
                'max_attendees' => 'nullable|integer|min:1',
                'registration_required' => 'nullable|boolean',
                'registration_deadline' => 'nullable|date',
                'speakers' => 'nullable|array',
                'agenda' => 'nullable|array',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:255',
                'category' => 'nullable|string|max:255',
            ];

            // Add conditional validation for end_date and end_time
            if (!empty($requestData['end_date']) && !empty($requestData['start_date'])) {
                $rules['end_date'] = 'nullable|date|after_or_equal:start_date';
            }
            if (!empty($requestData['end_time']) && !empty($requestData['start_time'])) {
                $rules['end_time'] = 'nullable|date_format:H:i|after:start_time';
            }

            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('events', 'public');
            }

            // Remove empty strings and convert to null for optional fields
            foreach ($data as $key => $value) {
                if ($value === '' && in_array($key, ['content', 'address', 'category', 'currency'])) {
                    $data[$key] = null;
                }
            }

            $event = Event::create($data);

            return response()->json([
                'success' => true,
                'data' => $event,
                'message' => 'Événement créé avec succès',
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating event: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement non trouvé',
            ], 404);
        }

        // Decode JSON strings for arrays before validation
        $requestData = $request->all();
        if (isset($requestData['speakers']) && is_string($requestData['speakers'])) {
            $requestData['speakers'] = json_decode($requestData['speakers'], true) ?? [];
        }
        if (isset($requestData['agenda']) && is_string($requestData['agenda'])) {
            $requestData['agenda'] = json_decode($requestData['agenda'], true) ?? [];
        }
        if (isset($requestData['tags']) && is_string($requestData['tags'])) {
            $requestData['tags'] = json_decode($requestData['tags'], true) ?? [];
        }
        // Convert registration_required from string to boolean if needed
        if (isset($requestData['registration_required']) && is_string($requestData['registration_required'])) {
            $requestData['registration_required'] = $requestData['registration_required'] === '1' || $requestData['registration_required'] === 'true';
        }

        $validator = Validator::make($requestData, [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'sometimes|required|in:conference,workshop,seminar,webinar,other',
            'status' => 'nullable|in:upcoming,ongoing,completed,cancelled',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'location' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'max_attendees' => 'nullable|integer|min:1',
            'registration_required' => 'nullable|boolean',
            'registration_deadline' => 'nullable|date',
            'speakers' => 'nullable|array',
            'agenda' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        $event->update($data);

        return response()->json([
            'success' => true,
            'data' => $event,
            'message' => 'Événement mis à jour avec succès',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement non trouvé',
            ], 404);
        }

        // Delete associated image
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Événement supprimé avec succès',
        ]);
    }
}
