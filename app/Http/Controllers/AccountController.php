<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserAvatarRequest;
use App\Http\Requests\UpdateUserAvatarRequest;
use App\Mail\UserAvatarChanged;
use App\Models\AiEventLog;
use App\Models\AiModel;
use App\Models\AiPost;
use App\Models\AiUser;
use App\Models\ChatMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $avatar = $user->aiAvatar;

        $events = collect();
        if ($avatar) {
            $events = AiEventLog::query()
                ->where('user_id', $avatar->id)
                ->latest()
                ->limit(20)
                ->get();
        }

        $nextEditAt = $avatar ? $this->nextAvatarEditAt($avatar) : null;

        return view('account.index', [
            'avatar' => $avatar,
            'events' => $events,
            'canEditAvatar' => $avatar ? $this->canUpdateAvatar($avatar) : true,
            'nextEditAt' => $nextEditAt,
        ]);
    }

    public function storeAvatar(StoreUserAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->aiAvatar) {
            return back()->withErrors([
                'avatar' => 'Hai già creato un avatar.',
            ]);
        }

        $model = $this->pickFreeModel();
        if (! $model) {
            return back()->withErrors([
                'avatar' => 'Nessun modello gratuito disponibile al momento.',
            ]);
        }

        $validated = $request->validated();
        $avatar = AiUser::create([
            'user_id' => $user->id,
            'nome' => $validated['nome'],
            'sesso' => $validated['sesso'] ?? null,
            'orientamento_sessuale' => $validated['orientamento_sessuale'],
            'lavoro' => $validated['lavoro'],
            'orientamento_politico' => $validated['orientamento_politico'],
            'passioni' => $this->parsePassions($validated['passioni']),
            'bias_informativo' => $validated['bias_informativo'],
            'personalita' => $validated['personalita'],
            'stile_comunicativo' => $validated['stile_comunicativo'],
            'atteggiamento_verso_attualita' => $validated['atteggiamento_verso_attualita'],
            'propensione_al_conflitto' => $validated['propensione_al_conflitto'],
            'sensibilita_ai_like' => $validated['sensibilita_ai_like'],
            'ritmo_attivita' => $validated['ritmo_attivita'],
            'generated_by_model' => $model->model_id,
            'is_pay' => false,
            'source_prompt_file' => 'user_avatar_form',
            'energia_sociale' => 100,
            'umore' => 'neutro',
            'bisogno_validazione' => 50,
            'avatar_updated_at' => now(),
        ]);

        Mail::to(config('livelia.contact.email'))
            ->send(new UserAvatarChanged($user, $avatar, 'created'));

        return redirect()
            ->route('account')
            ->with('status', 'Avatar creato con successo.');
    }

    public function updateAvatar(UpdateUserAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $avatar = $user->aiAvatar;

        if (! $avatar) {
            return back()->withErrors([
                'avatar' => 'Non hai ancora creato un avatar.',
            ]);
        }

        if (! $this->canUpdateAvatar($avatar)) {
            $nextEditAt = $this->nextAvatarEditAt($avatar);

            return back()->withErrors([
                'avatar' => $nextEditAt
                    ? 'Potrai modificare l\'avatar dal '.$nextEditAt->format('d/m/Y H:i').'.'
                    : 'Potrai modificare l\'avatar tra 7 giorni.',
            ]);
        }

        $validated = $request->validated();
        $avatar->update([
            'nome' => $validated['nome'],
            'sesso' => $validated['sesso'] ?? null,
            'orientamento_sessuale' => $validated['orientamento_sessuale'],
            'lavoro' => $validated['lavoro'],
            'orientamento_politico' => $validated['orientamento_politico'],
            'passioni' => $this->parsePassions($validated['passioni']),
            'bias_informativo' => $validated['bias_informativo'],
            'personalita' => $validated['personalita'],
            'stile_comunicativo' => $validated['stile_comunicativo'],
            'atteggiamento_verso_attualita' => $validated['atteggiamento_verso_attualita'],
            'propensione_al_conflitto' => $validated['propensione_al_conflitto'],
            'sensibilita_ai_like' => $validated['sensibilita_ai_like'],
            'ritmo_attivita' => $validated['ritmo_attivita'],
            'avatar_updated_at' => now(),
        ]);

        Mail::to(config('livelia.contact.email'))
            ->send(new UserAvatarChanged($user, $avatar, 'updated'));

        return redirect()
            ->route('account')
            ->with('status', 'Avatar aggiornato con successo.');
    }

    public function toggleAvatarNotify(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->notify_on_avatar_activity = ! $user->notify_on_avatar_activity;
        $user->save();

        return redirect()->route('account');
    }

    public function likes(Request $request): View
    {
        $user = $request->user();

        $posts = AiPost::query()
            ->with(['user', 'comments.user', 'comments.parent.user'])
            ->whereHas('humanLikes', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('reaction_type', 'like');
            })
            ->withCount([
                'comments',
                'humanLikes',
                'reactions as ai_likes_count' => fn ($query) => $query->where('reaction_type', 'like'),
                'humanLikes as liked_by_user_count' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->latest()
            ->paginate(10);

        $chatMessages = ChatMessage::query()
            ->with(['user', 'topic'])
            ->whereHas('humanLikes', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('reaction_type', 'like');
            })
            ->withCount([
                'humanLikes',
                'humanLikes as liked_by_user_count' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->latest()
            ->get();

        return view('account.likes', compact('posts', 'chatMessages'));
    }

    private function parsePassions(?string $passions): array
    {
        if (! $passions) {
            return [];
        }

        $items = preg_split('/[,\r\n]+/', $passions) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $item) => trim($item),
            $items
        )));
    }

    private function canUpdateAvatar(AiUser $avatar): bool
    {
        $nextEditAt = $this->nextAvatarEditAt($avatar);

        return $nextEditAt ? $nextEditAt->isPast() : true;
    }

    private function nextAvatarEditAt(AiUser $avatar): ?Carbon
    {
        if (! $avatar->avatar_updated_at) {
            return null;
        }

        return $avatar->avatar_updated_at->copy()->addDays(7);
    }

    private function pickFreeModel(): ?AiModel
    {
        return AiModel::query()
            ->where('is_text', true)
            ->whereNull('deleted_at')
            ->where('is_free', true)
            ->inRandomOrder(rand())
            ->first();
    }
}
