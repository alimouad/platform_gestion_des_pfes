<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // GET /messages/{userId} — conversation with a specific user
    public function conversation(int $userId): JsonResponse
    {
        $me = Auth::id();

        Message::where('from_user_id', $userId)
            ->where('to_user_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::with(['from', 'to'])
            ->where(fn($q) => $q->where('from_user_id', $me)->where('to_user_id', $userId))
            ->orWhere(fn($q) => $q->where('from_user_id', $userId)->where('to_user_id', $me))
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $messages]);
    }

    // POST /messages — send a message
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'body'       => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id'   => $data['to_user_id'],
            'body'         => $data['body'],
        ]);

        return response()->json(['data' => $message->load(['from', 'to'])], 201);
    }

    // GET /messages/contacts — list all contacts (conversations) with unread count
    public function contacts(): JsonResponse
    {
        $me = Auth::id();

        $messages = Message::with(['from', 'to'])
            ->where('from_user_id', $me)
            ->orWhere('to_user_id', $me)
            ->orderByDesc('created_at')
            ->get();

        $contacts = collect();
        foreach ($messages as $msg) {
            $contactId = $msg->from_user_id === $me ? $msg->to_user_id : $msg->from_user_id;
            if ($contacts->has($contactId)) continue;
            $contact   = $msg->from_user_id === $me ? $msg->to : $msg->from;
            $unread    = Message::where('from_user_id', $contactId)->where('to_user_id', $me)->whereNull('read_at')->count();
            $contacts->put($contactId, [
                'user'        => $contact,
                'last_message'=> $msg,
                'unread'      => $unread,
            ]);
        }

        return response()->json(['data' => $contacts->values()]);
    }
}
