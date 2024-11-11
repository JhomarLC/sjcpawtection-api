<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $event_query = Event::query();

        if (!empty($search)) {
            $event_query->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('place', 'like', '%' . $search . '%');
            });
        }

        return EventResource::collection(
            $event_query->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $event = Event::create($request->validate([
            'name' => 'required|string|max:100',
            'date_time' => 'required|date',
            'place' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]));

        return new EventResource($event);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        return new EventResource($event);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $event->update($request->validate([
            'name' => 'required|string|max:100',
            'date_time' => 'required|date',
            'place' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]));

        return new EventResource($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'message' => 'Event successfully deleted :)',
            'event' => $event
        ], 200);
    }
}
