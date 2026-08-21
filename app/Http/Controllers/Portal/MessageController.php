<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $messages = Message::where(function ($q) use ($user) {
            $q->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id);
        })
            ->with(['sender', 'recipient'])
            ->latest()
            ->paginate(15);

        // Mark incoming messages as read
        Message::where('to_user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('portal.messages.index', compact('messages', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'thread_id' => ['nullable', 'string', 'max:255'],
        ]);

        // Recipient is the assigned manager, staff or admin
        $staffUser = User::role(['Sachbearbeiter', 'Administrator'])->first() ?? User::where('id', '!=', $user->id)->first();

        Message::create([
            'thread_id' => $validated['thread_id'] ?? Str::uuid()->toString(),
            'from_user_id' => $user->id,
            'to_user_id' => $staffUser?->id,
            'subject' => $validated['subject'] ?? 'Nachricht an Hausverwaltung',
            'body' => $validated['body'],
        ]);

        return redirect()->route('portal.messages.index')
            ->with('success', 'Ihre Nachricht wurde an das Verwaltungsteam gesendet.');
    }
}
