<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-950">

        <div class="relative grid h-dvh flex-col items-center justify-center lg:max-w-none lg:grid-cols-2 lg:px-0">

            {{-- LEFT PANEL --}}
            <div class="wt-panel relative hidden h-full flex-col overflow-hidden bg-zinc-900 p-10 text-white lg:flex">

                {{-- Animated grid background --}}
                <canvas id="wt-grid" class="absolute inset-0 opacity-20"></canvas>

                {{-- Logo --}}
                <a href="{{ route('home') }}" wire:navigate
                   class="wt-logo relative z-10 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-700">
                        <x-app-logo-icon class="h-6 w-6 fill-current text-white" />
                    </span>
                    <span class="text-xl font-bold tracking-tight">WareTrack</span>
                </a>

                {{-- Hero text --}}
                <div class="relative z-10 mt-16">
                    <p class="wt-tagline text-4xl font-bold leading-tight tracking-tight text-white">
                        Warehouse management,<br>
                        <span class="text-zinc-400">simplified.</span>
                    </p>
                    <p class="wt-sub mt-4 text-base text-zinc-400">
                        Real-time stock tracking across multiple warehouses and locations — with full audit logging.
                    </p>
                </div>

                {{-- Feature highlights --}}
                <ul class="relative z-10 mt-12 space-y-5">
                    <li class="wt-feature flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-700">
                            <svg class="h-3.5 w-3.5 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 11h6M9 15h4" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-white">Multi-location stock management</p>
                            <p class="text-xs text-zinc-400">Track inventory across warehouses and locations in real-time.</p>
                        </div>
                    </li>
                    <li class="wt-feature flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-700">
                            <svg class="h-3.5 w-3.5 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-white">Full delivery & supplier tracking</p>
                            <p class="text-xs text-zinc-400">From supplier order to stock — every step logged.</p>
                        </div>
                    </li>
                    <li class="wt-feature flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-700">
                            <svg class="h-3.5 w-3.5 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-white">Low-stock alerts & reports</p>
                            <p class="text-xs text-zinc-400">Instant overview of products below minimum stock level.</p>
                        </div>
                    </li>
                </ul>

                {{-- Bottom badge --}}
                <div class="wt-badge relative z-10 mt-auto">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-800 px-3 py-1 text-xs text-zinc-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-400"></span>
                        Built with Laravel 13 + Livewire 4
                    </span>
                </div>
            </div>

            {{-- RIGHT PANEL: form --}}
            <div class="wt-form-panel flex w-full items-center justify-center px-8 py-12 lg:p-12">
                <div class="mx-auto flex w-full max-w-sm flex-col gap-6">

                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" wire:navigate
                       class="flex flex-col items-center gap-2 font-medium lg:hidden">
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-zinc-800">
                            <x-app-logo-icon class="size-5 fill-current text-white" />
                        </span>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">WareTrack</span>
                    </a>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // --- Grid canvas animation ---
                const canvas = document.getElementById('wt-grid');
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    let W, H, dots = [], animFrame;

                    function resize() {
                        W = canvas.width  = canvas.offsetWidth;
                        H = canvas.height = canvas.offsetHeight;
                    }

                    function initDots() {
                        dots = [];
                        const cols = Math.ceil(W / 60);
                        const rows = Math.ceil(H / 60);
                        for (let r = 0; r <= rows; r++) {
                            for (let c = 0; c <= cols; c++) {
                                dots.push({
                                    x: c * 60, y: r * 60,
                                    ox: c * 60, oy: r * 60,
                                    vx: (Math.random() - 0.5) * 0.3,
                                    vy: (Math.random() - 0.5) * 0.3,
                                    r: Math.random() * 1.2 + 0.4,
                                });
                            }
                        }
                    }

                    function draw() {
                        ctx.clearRect(0, 0, W, H);

                        // Move dots
                        dots.forEach(d => {
                            d.x += d.vx;
                            d.y += d.vy;
                            if (Math.abs(d.x - d.ox) > 12) d.vx *= -1;
                            if (Math.abs(d.y - d.oy) > 12) d.vy *= -1;
                        });

                        // Draw connections
                        ctx.strokeStyle = 'rgba(255,255,255,0.15)';
                        ctx.lineWidth = 0.5;
                        for (let i = 0; i < dots.length; i++) {
                            for (let j = i + 1; j < dots.length; j++) {
                                const dx = dots[i].x - dots[j].x;
                                const dy = dots[i].y - dots[j].y;
                                const dist = Math.sqrt(dx*dx + dy*dy);
                                if (dist < 80) {
                                    ctx.globalAlpha = (1 - dist / 80) * 0.4;
                                    ctx.beginPath();
                                    ctx.moveTo(dots[i].x, dots[i].y);
                                    ctx.lineTo(dots[j].x, dots[j].y);
                                    ctx.stroke();
                                }
                            }
                        }

                        // Draw dots
                        ctx.globalAlpha = 0.7;
                        dots.forEach(d => {
                            ctx.fillStyle = 'rgba(255,255,255,0.6)';
                            ctx.beginPath();
                            ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
                            ctx.fill();
                        });

                        ctx.globalAlpha = 1;
                        animFrame = requestAnimationFrame(draw);
                    }

                    resize();
                    initDots();
                    draw();
                    window.addEventListener('resize', () => { cancelAnimationFrame(animFrame); resize(); initDots(); draw(); });
                }

                // --- GSAP entrance animations ---
                if (typeof gsap !== 'undefined') {
                    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

                    tl.from('.wt-logo',    { y: -24, opacity: 0, duration: 0.6 })
                      .from('.wt-tagline', { y: 20,  opacity: 0, duration: 0.6 }, '-=0.3')
                      .from('.wt-sub',     { y: 16,  opacity: 0, duration: 0.5 }, '-=0.3')
                      .from('.wt-feature', { x: -20, opacity: 0, duration: 0.45, stagger: 0.12 }, '-=0.2')
                      .from('.wt-badge',   { opacity: 0, duration: 0.4 }, '-=0.1')
                      .from('.wt-form-panel', { x: 30, opacity: 0, duration: 0.6 }, '-=0.8');
                }
            });
        </script>
    </body>
</html>
