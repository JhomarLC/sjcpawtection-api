<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationHistoryResource;
use App\Models\NotificationsHistory;
use Illuminate\Http\Request;

class NotificationsHistoryController extends Controller
{
    public function index(Request $request)
    {
        $notifications_history = NotificationsHistory::latest()->get();

        return NotificationHistoryResource::collection($notifications_history);
    }

    public function store(Request $request)
    {

        $notifications_history = NotificationsHistory::create($request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'action' => 'required|string'
        ]));

        return new NotificationHistoryResource($notifications_history);
    }
}
