        <footer class="border-t border-[var(--line)] bg-[rgba(255,255,255,0.88)] backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="space-y-4">
                        <div>
                            <div class="text-xl font-black tracking-tight">
                                <span class="text-[var(--primary)]">RANEY</span>
                                <span class="text-[var(--accent)]"> LUBRICANTS</span>
                            </div>
                            <div class="mt-0.5 text-[10px] uppercase tracking-[0.28em] text-[var(--muted)]">Trading</div>
                        </div>
                        <p class="text-sm leading-6 text-[var(--muted)]">Your trusted partner for premium engine oils and lubricants. Quality products for maximum performance.</p>
                        {{-- Facebook only. The Instagram icon was decoration
                             for an account that does not exist. --}}
                        <div class="flex gap-3">
                            <a href="{{ config('business.facebook') }}" target="_blank" rel="noopener noreferrer"
                               aria-label="RANEY LUBRICANTS TRADING on Facebook"
                               class="flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--primary-soft)] text-[var(--primary)] transition hover:bg-[var(--primary)] hover:text-white">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">About Us</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/about" class="text-[var(--muted)] transition hover:text-[var(--primary)]">Our Story</a></li>
                            <li><a href="/shop" class="text-[var(--muted)] transition hover:text-[var(--primary)]">Our Products</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Customer Service</h4>
                        <ul class="space-y-2 text-sm">
                            {{-- Signed out, /profile bounces to the login screen, so
                                 the link goes there directly instead. --}}
                            <li><a href="{{ auth()->check() && auth()->user()->isCustomer() ? '/profile' : '/shop/login' }}" class="text-[var(--muted)] transition hover:text-[var(--primary)]">My Account</a></li>
                            <li><a href="/returns" class="text-[var(--muted)] transition hover:text-[var(--primary)]">Returns &amp; Refunds</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="mb-4 text-sm font-semibold text-[var(--ink)]">Contact Us</h4>
                        <ul class="space-y-3 text-sm text-[var(--muted)]">
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>{{ config('business.address') }}</span>
                            </li>
                            @if (filled(config('business.phone')))
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span>{{ config('business.phone') }}</span>
                            </li>
                            @endif
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                <span>{{ config('business.email') }}</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <svg class="h-4 w-4 flex-shrink-0 text-[var(--primary)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>{{ config('business.hours') }}</span>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="mt-8 border-t border-[var(--line)] pt-6 flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                    <p class="text-xs text-[var(--muted)]">&copy; {{ now()->year }} RANEY LUBRICANTS TRADING. All rights reserved.</p>
                    <div class="flex gap-4 text-xs text-[var(--muted)]">
                        <a href="/privacy" class="hover:text-[var(--primary)]">Privacy Policy</a>
                        <a href="/terms" class="hover:text-[var(--primary)]">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>
