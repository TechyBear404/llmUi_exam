<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function destroy(Message $message)
    {
        try {
            $this->authorize('delete', $message);

            $message->delete();

            return back()->with('success', 'Message supprimé avec succès');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Erreur lors de la suppression du message: ' . $e->getMessage()]);
        }
    }
}
