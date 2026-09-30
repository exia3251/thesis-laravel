{{--
    The button that shows what was typed into a password box.

    Include it directly after the input, inside a wrapper with `relative` on
    it, and give the input room on the right (`pr-12`, or `pr-11` on the
    smaller back-office boxes) so the icon does not sit over the text.

    It was written out twice, on the two sign-in pages, and missing from the
    other eleven password boxes in the system -- including both halves of
    "create a password / confirm your password", which is the one place a typo
    cannot be seen and costs the whole form.
--}}

<button type="button" onclick="togglePassword(this)" aria-label="Show password" tabindex="-1"
        class="{{ $class ?? 'absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-[var(--muted)] transition hover:text-[var(--ink)]' }}">
    <svg class="eye-open h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
    <svg class="eye-shut hidden h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 2.04 11.68a1 1 0 0 0 0 .64C3.42 16.49 7.36 19.5 12 19.5c.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.64 0 8.58 3.01 9.96 7.18a1 1 0 0 1 0 .64 10.52 10.52 0 0 1-4.29 5.45M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 1-4.24-4.24m4.24 4.24L9.88 9.88"/></svg>
</button>
