<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EventRegistrationController extends Controller
{
    /**
     * Inscrit un utilisateur à un événement
     */
    public function register(Request $request, string $eventId): JsonResponse
    {
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement non trouvé',
            ], 404);
        }

        // Vérifier si l'événement accepte les inscriptions
        if (!$event->registration_required) {
            return response()->json([
                'success' => false,
                'message' => 'Cet événement ne nécessite pas d\'inscription',
            ], 400);
        }

        // Vérifier la date limite d'inscription
        if ($event->registration_deadline && now() > $event->registration_deadline) {
            return response()->json([
                'success' => false,
                'message' => 'La date limite d\'inscription est dépassée',
            ], 400);
        }

        // Vérifier la capacité maximale
        if ($event->max_attendees) {
            $currentRegistrations = EventRegistration::where('event_id', $eventId)
                ->where('status', '!=', 'cancelled')
                ->count();
            
            if ($currentRegistrations >= $event->max_attendees) {
                return response()->json([
                    'success' => false,
                    'message' => 'L\'événement est complet',
                ], 400);
            }
        }

        // Vérifier si l'utilisateur n'est pas déjà inscrit
        $existingRegistration = EventRegistration::where('event_id', $eventId)
            ->where('email', $request->email)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingRegistration) {
            return response()->json([
                'success' => false,
                'message' => 'Vous êtes déjà inscrit à cet événement',
            ], 400);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'organization' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Créer l'inscription
        $registration = EventRegistration::create([
            'event_id' => $eventId,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'organization' => $request->organization,
            'position' => $request->position,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        // Mettre à jour le nombre de participants
        $event->increment('current_attendees');

        return response()->json([
            'success' => true,
            'data' => $registration,
            'message' => 'Inscription réussie',
        ], 201);
    }

    /**
     * Récupère toutes les inscriptions d'un événement
     */
    public function getEventRegistrations(string $eventId): JsonResponse
    {
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Événement non trouvé',
            ], 404);
        }

        $registrations = EventRegistration::where('event_id', $eventId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $registrations,
        ]);
    }

    /**
     * Récupère toutes les inscriptions (tous événements)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $eventId = $request->get('event_id');
        $status = $request->get('status');

        $query = EventRegistration::with('event');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $registrations = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $registrations->items(),
            'pagination' => [
                'page' => $registrations->currentPage(),
                'limit' => $registrations->perPage(),
                'total' => $registrations->total(),
                'totalPages' => $registrations->lastPage(),
            ],
        ]);
    }

    /**
     * Met à jour le statut d'une inscription
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $registration = EventRegistration::find($id);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $registration->status;
        $registration->status = $request->status;
        $registration->save();

        // Mettre à jour le nombre de participants de l'événement
        $event = $registration->event;
        if ($event) {
            if ($oldStatus !== 'cancelled' && $request->status === 'cancelled') {
                $event->decrement('current_attendees');
            } elseif ($oldStatus === 'cancelled' && $request->status !== 'cancelled') {
                $event->increment('current_attendees');
            }
        }

        return response()->json([
            'success' => true,
            'data' => $registration,
            'message' => 'Statut mis à jour avec succès',
        ]);
    }

    /**
     * Supprime une inscription
     */
    public function destroy(string $id): JsonResponse
    {
        $registration = EventRegistration::find($id);

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Inscription non trouvée',
            ], 404);
        }

        $event = $registration->event;
        $status = $registration->status;

        $registration->delete();

        // Mettre à jour le nombre de participants si l'inscription n'était pas annulée
        if ($event && $status !== 'cancelled') {
            $event->decrement('current_attendees');
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscription supprimée avec succès',
        ]);
    }
}

