@extends('layouts.bare')

@section('title', 'Create Account - RANEY LUBRICANTS TRADING')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_right,_rgba(20,138,103,0.09),_transparent_28%),radial-gradient(circle_at_bottom_left,_rgba(217,177,74,0.10),_transparent_24%),linear-gradient(180deg,_#fbfcfe_0%,_#f3f6f9_100%)] px-4 py-10 sm:px-6">
        <div class="w-full max-w-xl">
            <div class="mb-8 text-center">
                <a href="/shop">
                    <div class="text-3xl font-black tracking-tight sm:text-4xl">
                        <span class="text-[var(--primary)]">RANEY</span>
                        <span class="text-[var(--accent)]"> LUBRICANTS</span>
                    </div>
                    <div class="mt-1 text-[11px] uppercase tracking-[0.32em] text-[var(--muted)]">Trading</div>
                </a>
            </div>
            <section class="mx-auto w-full max-w-xl rounded-[1.5rem] border border-[var(--line)] bg-white p-6 shadow-sm sm:p-8">
                <div class="text-center">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.32em] text-[var(--primary)]">Customer Portal</div>
                    <h2 class="mt-4 text-3xl font-extrabold text-[var(--ink)]">Create Account</h2>
                    <p class="mt-2 text-sm font-semibold tracking-[0.2em] text-[var(--muted)]">RANEY LUBRICANTS TRADING</p>
                </div>

                <div id="message" class="hidden mt-6 rounded-2xl px-4 py-3"></div>

                <form id="registerForm" class="mt-8 space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Full Name</label>
                        <input id="full_name" type="text" required minlength="2" maxlength="100" pattern="[A-Za-z][A-Za-z\s'.-]*" title="Full name must contain letters only. Spaces, apostrophes, periods, and hyphens are allowed." class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Juan Dela Cruz">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Phone</label>
                            <input id="phone" type="text" required inputmode="numeric" maxlength="13" pattern="^(09\d{9}|\+639\d{9})$" title="Enter a valid Philippine mobile number like 09XXXXXXXXX or +639XXXXXXXXX." class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="09XXXXXXXXX">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Email</label>
                            <input id="email" type="email" required maxlength="150" autocomplete="username" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="you@example.com">
                        </div>
                    </div>

                    {{-- Province, then city, then barangay, then the street.

                         The order is the order they narrow in: a barangay
                         cannot be picked before the city it sits in is known.
                         The old form asked for them the other way round, in
                         free text, so the same address arrived spelled six
                         ways and a barangay could be typed that does not exist
                         in the city beside it. --}}
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            @include('partials.address-fields', [
                                'prefix' => 'reg',
                                'required' => true,
                                'labelClass' => 'mb-2 block text-sm font-medium text-[var(--ink)]',
                                'inputClass' => 'block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 pr-10 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400',
                            ])

                            <div>
                                <label for="postal_code" class="mb-2 block text-sm font-medium text-[var(--ink)]">Postal code <span class="font-normal text-[var(--muted)]">optional</span></label>
                                <input id="postal_code" type="text" maxlength="4" inputmode="numeric" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="1200">
                            </div>
                        </div>

                        <div>
                            <label for="house_street" class="mb-2 block text-sm font-medium text-[var(--ink)]">House or building number and street <span class="text-red-500">*</span></label>
                            <input id="house_street" type="text" required minlength="5" maxlength="160" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="123 Rizal Street, Unit 4B">
                            <p class="mt-1 text-xs text-[var(--muted)]">No list exists for these, so type it as the courier should read it.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Password</label>
                            <input id="password" type="password" required minlength="8" maxlength="100" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Create a password">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-[var(--ink)]">Confirm Password</label>
                            <input id="password_confirmation" type="password" required minlength="8" maxlength="100" class="block w-full rounded-2xl border border-[var(--line)] bg-white/85 px-4 py-3 text-[var(--ink)] shadow-sm transition focus:border-[var(--primary)] focus:outline-none focus:ring-4 focus:ring-[var(--primary-soft)]" placeholder="Confirm your password">
                        </div>
                    </div>

                    <button type="submit" class="flex w-full justify-center rounded-xl bg-[var(--primary)] px-4 py-3 text-sm font-bold text-white transition hover:brightness-110">
                        Create Account
                    </button>

                    @include('partials.google-button', ['label' => 'Sign up with Google'])

                    {{-- Placed below both buttons so it covers signing up either
                         way, and worded as notice rather than a tick box, since
                         nothing here is optional to agree to. --}}
                    <p class="mt-5 text-center text-xs leading-6 text-[var(--muted)]">
                        By creating an account you agree to our
                        <a href="/terms" class="font-semibold text-[var(--primary)] hover:underline">Terms of Service</a>
                        and
                        <a href="/privacy" class="font-semibold text-[var(--primary)] hover:underline">Privacy Policy</a>.
                    </p>
                </form>

                <div class="mt-6 text-center text-sm text-[var(--muted)]">
                    Already have an account?
                    <a href="/shop/login" class="font-semibold text-[var(--primary)] hover:underline">Sign in here</a>
                </div>
            </section>
            <p class="mt-6 text-center text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
        </div>
    </div>


@endsection

@push('scripts')
<script>
        let messageTimeout;

        function showMessage(text, type = 'success') {
            const box = document.getElementById('message');
            box.textContent = text;
            box.className = `mt-6 rounded-2xl px-4 py-3 ${type === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`;
            box.classList.remove('hidden');
            clearTimeout(messageTimeout);
            messageTimeout = setTimeout(() => box.classList.add('hidden'), 4000);
        }

        document.getElementById('registerForm').addEventListener('submit', async (event) => {
            event.preventDefault();

            const payload = {
                full_name: document.getElementById('full_name').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                email: document.getElementById('email').value.trim(),
                house_street: document.getElementById('house_street').value.trim(),
                // The three place boxes post what was picked from the list,
                // not what was typed into the search box above it.
                barangay: document.getElementById('reg_barangay').value.trim(),
                city: document.getElementById('reg_city').value.trim(),
                province: document.getElementById('reg_province').value.trim(),
                barangay_code: document.getElementById('reg_barangay_code').value,
                city_code: document.getElementById('reg_city_code').value,
                province_code: document.getElementById('reg_province_code').value,
                postal_code: document.getElementById('postal_code').value.trim(),
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value
            };

            /* Held until the page leaves or the form comes back refused. A
               second press here would try to register the same address twice,
               and the second attempt fails on the account the first one just
               created -- so the reward for impatience was being told the email
               is already taken. */
            const done = startBusy(busyButtonOf(event.target), 'Creating account');

            try {
                const response = await fetch('/shop/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showMessage('Registration successful. Redirecting to login...', 'success');
                    document.getElementById('registerForm').reset();

                    setTimeout(() => {
                        window.location.href = '/shop/login?registered=1';
                    }, 1000);
                    return;
                }

                done();

                if (data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    showMessage(Array.isArray(firstError) ? firstError[0] : 'Registration failed.', 'error');
                    return;
                }

                showMessage(data.message || 'Registration failed.', 'error');
            } catch (error) {
                done();
                showMessage('Could not reach the server. Please try again.', 'error');
            }
        });
</script>
@endpush
