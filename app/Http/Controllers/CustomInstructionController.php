<?php

namespace App\Http\Controllers;

use App\Models\CustomInstruction;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CustomInstructionController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', CustomInstruction::class);
        
        $instructions = auth()->user()->customInstructions()
            ->latest()
            ->get();

        return Inertia::render('CustomInstructions/Index', [
            'instructions' => $instructions
        ]);
    }

    public function create()
    {
        $this->authorize('create', CustomInstruction::class);
        
        return Inertia::render('CustomInstructions/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', CustomInstruction::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'user_background' => 'nullable|string',
            'user_interests' => 'nullable|array',
            'knowledge_levels' => 'nullable|array',
            'knowledge_levels.*.subject' => 'required|string',
            'knowledge_levels.*.level' => 'required|string|in:beginner,intermediate,advanced,expert',
            'user_goals' => 'nullable|string',
            'assistant_background' => 'nullable|string',
            'assistant_tone' => 'required|string|in:friendly,professional,casual,formal,technical,educational',
            'response_style' => 'required|string|in:normal,concise,detailed,formal,casual',
            'response_format' => 'required|string|in:paragraphs,bullet_points,step_by_step,mixed',
            'custom_commands' => 'present|array',
            'custom_commands.*.name' => 'required_with:custom_commands|string',
            'custom_commands.*.description' => 'required_with:custom_commands|string',
        ]);

        $instruction = auth()->user()->customInstructions()->create($validated);

        return redirect()->route('custom-instructions.index')
            ->with('success', 'Instruction personnalisée créée avec succès.');
    }

    public function show(CustomInstruction $customInstruction)
    {
        $this->authorize('view', $customInstruction);
        
        return Inertia::render('CustomInstructions/Show', [
            'instruction' => $customInstruction
        ]);
    }

    public function update(Request $request, CustomInstruction $customInstruction)
    {
        $this->authorize('update', $customInstruction);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'user_background' => 'nullable|string',
            'user_interests' => 'nullable|array',
            'knowledge_levels' => 'nullable|array',
            'knowledge_levels.*.subject' => 'required|string',
            'knowledge_levels.*.level' => 'required|string|in:beginner,intermediate,advanced,expert',
            'user_goals' => 'nullable|string',
            'assistant_background' => 'nullable|string',
            'assistant_tone' => 'required|string|in:friendly,professional,casual,formal,technical,educational',
            'response_style' => 'required|string|in:normal,concise,detailed,formal,casual',
            'response_format' => 'required|string|in:paragraphs,bullet_points,step_by_step,mixed',
            'custom_commands' => 'present|array',
            'custom_commands.*.name' => 'required_with:custom_commands|string',
            'custom_commands.*.description' => 'required_with:custom_commands|string',
        ]);

        $customInstruction->update($validated);

        return redirect()->route('custom-instructions.index')
            ->with('success', 'Instruction personnalisée mise à jour avec succès.');
    }

    public function destroy(CustomInstruction $customInstruction)
    {
        $this->authorize('delete', $customInstruction);
        
        $customInstruction->delete();

        return redirect()->route('custom-instructions.index')
            ->with('success', 'Instruction personnalisée supprimée avec succès.');
    }

    public function getList()
    {
        $this->authorize('viewAny', CustomInstruction::class);
        
        return response()->json(
            auth()->user()->customInstructions()
                ->select('id', 'title')
                ->latest()
                ->get()
        );
    }
}
