@extends('portal.layout')

@section('title', 'Nachrichten & Kontakt')
@section('page-title', 'Nachrichten & Kontakt')

@section('content')
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
    <!-- Message Feed -->
    <div>
        <div style="margin-bottom: 16px;">
            <h2 style="font-size: 20px; margin-bottom: 4px;">Nachrichtenverlauf</h2>
            <p style="color: var(--text-muted); font-size: 13px;">Ihre Kommunikation mit der Hausverwaltung.</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            @forelse($messages as $msg)
                <div class="card" style="padding: 18px; border-left: 3px solid {{ $msg->from_user_id === $user->id ? 'var(--color-primary-500)' : 'var(--color-accent-500)' }};">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <div>
                            <div style="font-weight: 700; font-size: 15px; color: #fff;">{{ $msg->subject ?? 'Nachricht' }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                {{ $msg->from_user_id === $user->id ? 'Gesendet von Ihnen' : 'Von: ' . ($msg->sender->name ?? 'Hausverwaltung') }}
                                &bull; {{ $msg->created_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                        @if($msg->from_user_id === $user->id)
                            <span class="badge badge-info">Ausgehend</span>
                        @else
                            <span class="badge badge-success">Eingehend</span>
                        @endif
                    </div>
                    <div style="font-size: 14px; color: var(--text-main); line-height: 1.6; white-space: pre-wrap;">{{ $msg->body }}</div>
                    @if($msg->from_user_id !== $user->id)
                        <div style="margin-top: 12px; display: flex; justify-content: flex-end;">
                            <button type="button" class="btn btn-outline" style="font-size: 12px; padding: 4px 10px;" onclick="replyToMessage('{{ addslashes($msg->subject ?? 'Nachricht') }}', '{{ $msg->thread_id }}')">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                </svg>
                                {{ __('Antworten') }}
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="card" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                    <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                    <div style="font-weight: 600;">Noch keine Nachrichten vorhanden</div>
                    <div style="font-size: 13px; margin-top: 4px;">Senden Sie rechts eine neue Nachricht an Ihr Verwaltungsteam.</div>
                </div>
            @endforelse

            @if($messages->hasPages())
                <div style="margin-top: 8px;">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- New Message Composer -->
    <div>
        <div style="margin-bottom: 16px;">
            <h2 style="font-size: 20px; margin-bottom: 4px;">Neue Nachricht verfassen</h2>
            <p style="color: var(--text-muted); font-size: 13px;">Schreiben Sie direkt an Ihren zuständigen Verwalter.</p>
        </div>

        <div class="card" id="message-composer">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('portal.messages.store') }}">
                @csrf
                <input type="hidden" id="thread_id" name="thread_id" value="{{ old('thread_id') }}">

                <div class="form-group">
                    <label class="form-label" for="subject">Betreff / Thema *</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="z.B. Schlüsselverlust / Neue Bankverbindung" value="{{ old('subject') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="body">Nachrichtentext *</label>
                    <textarea id="body" name="body" class="form-textarea" rows="7" placeholder="Geben Sie hier Ihre Nachricht ein..." required>{{ old('body') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    Nachricht jetzt absenden
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function replyToMessage(subject, threadId) {
        const subjectInput = document.getElementById('subject');
        const threadInput = document.getElementById('thread_id');
        const bodyInput = document.getElementById('body');

        if (subject && !subject.startsWith('Re: ')) {
            subjectInput.value = 'Re: ' + subject;
        } else if (subject) {
            subjectInput.value = subject;
        }
        if (threadId) {
            threadInput.value = threadId;
        }

        bodyInput.focus();
        document.getElementById('message-composer').scrollIntoView({ behavior: 'smooth' });
    }
</script>
@endsection
