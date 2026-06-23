@php
    use App\Models\Conversation;
    use App\Models\Message;
    use Illuminate\Support\Str;

    $max_depth   = (int) config('platform.chat.max_depth');
    $autostream  = session('stream') && ! $conversation->isSurfaced();
    $progress    = $max_depth > 0 ? min(100, round($conversation->current_depth / $max_depth * 100)) : 0;
@endphp

<x-frontend.layout>
    {{-- ===================================================================
         The descent — one rabbit hole. The thread renders server-side; chat.js
         streams the guide's live turn over SSE and reveals the right control
         (prove-it / go-deeper / surfaced) when the turn lands.
         =================================================================== --}}
    <section class="relative mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-3xl flex-col px-4 sm:px-6">

        {{-- Depth header --}}
        <header class="sticky top-16 z-20 -mx-4 border-b border-border/60 bg-background/80 px-4 py-3 backdrop-blur-xl sm:-mx-6 sm:px-6">
            <div class="flex items-baseline justify-between gap-3">
                <p class="min-w-0 truncate text-sm text-foreground-muted">
                    <span class="font-mono text-xs uppercase tracking-[0.16em] text-foreground-muted/70">{{ __('frontend.chat.subject-label') }}</span>
                    <span class="ml-1.5 font-display font-semibold text-foreground">{{ $conversation->subject }}</span>
                </p>
                <span class="shrink-0 font-mono text-xs text-foreground-muted/80">
                    {{ __('frontend.chat.depth') }} {{ $conversation->current_depth }} {{ __('frontend.chat.of') }} {{ $max_depth }}
                </span>
            </div>
            <div class="mt-2 h-1 overflow-hidden rounded-full bg-surface-muted">
                <div class="h-full rounded-full bg-primary transition-[width] duration-700 dth-glow-cyan" style="width: {{ $progress }}%"></div>
            </div>
        </header>

        {{-- Flash --}}
        @if (session('error'))
            <p class="mt-4 rounded-xl border border-danger/40 bg-danger/10 px-4 py-2.5 text-sm text-danger">{{ session('error') }}</p>
        @endif

        {{-- Thread --}}
        <div id="dth-thread" class="flex flex-1 flex-col gap-5 py-6">
            @foreach ($conversation->messages as $message)
                @php $is_user = $message->role === Message::ROLE_USER; @endphp
                <div class="flex {{ $is_user ? 'justify-end' : 'justify-start' }}">
                    <div @class([
                        'max-w-[85%] whitespace-pre-wrap rounded-2xl px-4 py-3 text-[0.95rem] leading-relaxed',
                        'bg-primary/12 text-foreground ring-1 ring-primary/20' => $is_user,
                        'border border-border/70 bg-surface/50 text-foreground backdrop-blur-sm' => ! $is_user,
                    ])>{{ $is_user ? $message->content : Str::of($message->content)->before('```json')->trim() }}</div>
                </div>
            @endforeach

            {{-- Live streaming bubble (filled by chat.js) --}}
            <div id="dth-stream-row" class="hidden justify-start">
                <div class="max-w-[85%] rounded-2xl border border-border/70 bg-surface/50 px-4 py-3 text-[0.95rem] leading-relaxed text-foreground backdrop-blur-sm">
                    <span id="dth-stream" class="whitespace-pre-wrap"></span>
                    <span id="dth-cursor" class="ml-0.5 inline-block h-4 w-1.5 animate-pulse rounded-sm bg-primary align-middle"></span>
                </div>
            </div>
        </div>

        {{-- Controls — chat.js reveals exactly one --}}
        <div class="sticky bottom-0 z-20 -mx-4 border-t border-border/60 bg-background/85 px-4 py-4 backdrop-blur-xl sm:-mx-6 sm:px-6">

            {{-- Prove it --}}
            <form id="dth-control-proof" action="{{ route('hole.continue', $conversation) }}" method="POST" class="hidden">
                @csrf
                <label for="dth-proof" class="mb-2 flex items-center gap-1.5 font-mono text-xs uppercase tracking-[0.16em] text-primary">
                    <span class="material-symbols-outlined text-[1rem]">quiz</span>
                    {{ __('frontend.chat.checkpoint') }}
                </label>
                <div class="rounded-2xl border border-border-strong bg-surface/70 p-2 shadow-xl backdrop-blur-md transition focus-within:border-primary/60 focus-within:ring-2 focus-within:ring-ring/40">
                    <textarea id="dth-proof" name="message" rows="2" required
                              placeholder="{{ __('frontend.chat.proof-placeholder') }}"
                              class="block w-full resize-none border-0 bg-transparent px-3 py-2 text-base text-foreground placeholder:text-foreground-muted/70 focus:outline-none"></textarea>
                    <div class="flex justify-end px-1 pb-1">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            {{ __('frontend.chat.submit-proof') }}
                            <span class="material-symbols-outlined text-[1.1rem]">arrow_downward</span>
                        </button>
                    </div>
                </div>
            </form>

            {{-- Go deeper --}}
            <form id="dth-control-deeper" action="{{ route('hole.continue', $conversation) }}" method="POST" class="hidden">
                @csrf
                <div class="flex flex-col items-center gap-3 text-center">
                    <p class="flex items-center gap-1.5 text-sm text-success">
                        <span class="material-symbols-outlined text-[1.15rem]">check_circle</span>
                        {{ __('frontend.chat.layer-cleared') }}
                    </p>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dth-glow-cyan">
                        {{ __('frontend.chat.go-deeper') }}
                        <span class="material-symbols-outlined text-[1.2rem]">south_east</span>
                    </button>
                </div>
            </form>

            {{-- Surfaced --}}
            <div id="dth-control-surfaced" class="hidden flex-col items-center gap-2 text-center">
                <span class="material-symbols-outlined text-[1.6rem] text-primary dth-text-glow">workspace_premium</span>
                <h2 class="font-display text-lg font-semibold text-foreground">{{ __('frontend.chat.surfaced-title') }}</h2>
                <p class="max-w-md text-sm text-foreground-muted">{{ __('frontend.chat.surfaced-body') }}</p>
                <a href="{{ route('home') }}" class="mt-2 inline-flex items-center gap-2 rounded-xl border border-border-strong px-5 py-2.5 text-sm font-medium text-foreground transition hover:border-primary/50 hover:bg-primary/8">
                    {{ __('frontend.chat.new-descent') }}
                </a>
            </div>

            {{-- Thinking indicator (while the guide streams) --}}
            <p id="dth-thinking" class="hidden items-center justify-center gap-2 text-center font-mono text-xs text-foreground-muted/70">
                <span class="material-symbols-outlined animate-pulse text-[1rem] text-primary">neurology</span>
                {{ __('frontend.chat.thinking') }}
            </p>

            {{-- Error --}}
            <p id="dth-error" class="hidden rounded-xl border border-danger/40 bg-danger/10 px-4 py-2.5 text-center text-sm text-danger"></p>
        </div>

        {{-- Config for chat.js --}}
        <div data-chat
             data-stream-url="{{ route('hole.stream', $conversation) }}"
             data-status="{{ $conversation->status }}"
             data-depth="{{ $conversation->current_depth }}"
             data-max-depth="{{ $max_depth }}"
             data-autostream="{{ $autostream ? '1' : '0' }}"
             hidden></div>
    </section>

    @push('scripts')
        <script src="{{ loadFiles('js/frontend/chat.js') }}"></script>
    @endpush
</x-frontend.layout>
