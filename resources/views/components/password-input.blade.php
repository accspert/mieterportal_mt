{{-- Password field with an eye button on the right to show or hide the entered password. --}}
<div class="password-field">
    <input type="password" {{ $attributes }}>
    <button type="button" class="password-toggle" data-password-toggle aria-label="Passwort anzeigen" aria-pressed="false">
        <svg class="icon-show" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <svg class="icon-hide" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.6 3.5M6.6 6.6C3.7 8.4 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
            <path d="m3 3 18 18"/>
        </svg>
    </button>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = button.previousElementSibling;
                    const isVisible = input.type === 'text';

                    input.type = isVisible ? 'password' : 'text';
                    button.setAttribute('aria-pressed', String(! isVisible));
                    button.setAttribute('aria-label', isVisible ? 'Passwort anzeigen' : 'Passwort verbergen');
                    input.focus();
                });
            });
        </script>
    @endpush
@endonce
