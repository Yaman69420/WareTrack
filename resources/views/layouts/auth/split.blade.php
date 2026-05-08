<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-950">

        <div class="relative grid h-dvh flex-col items-center justify-center lg:max-w-none lg:grid-cols-2 lg:px-0">

            {{-- LEFT PANEL --}}
            <div class="wt-panel relative hidden h-full flex-col overflow-hidden bg-[#080c14] p-10 text-white lg:flex">

                {{-- Blue glow orb --}}
                <div class="absolute -left-20 top-0 size-72 rounded-full bg-blue-600/20 blur-3xl"></div>
                <div class="absolute bottom-20 right-0 size-64 rounded-full bg-indigo-600/15 blur-3xl"></div>

                {{-- Animated dot grid background --}}
                <canvas id="wt-grid" class="absolute inset-0 opacity-30"></canvas>

                {{-- Logo --}}
                <a href="{{ route('home') }}" wire:navigate class="wt-logo relative z-10 flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-lg shadow-blue-900/50">
                        <x-app-logo-icon class="h-5 w-5 fill-current text-white" />
                    </span>
                    <span class="text-xl font-bold tracking-tight">WareTrack</span>
                </a>

                {{-- Hero text --}}
                <div class="relative z-10 mt-14">
                    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-400">
                        <span class="size-1.5 rounded-full bg-blue-400"></span>
                        Warehouse Management System
                    </div>
                    <p class="wt-tagline text-4xl font-bold leading-tight tracking-tight text-white">
                        Manage stock<br>
                        <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">across any scale.</span>
                    </p>
                    <p class="wt-sub mt-4 text-sm leading-relaxed text-zinc-400">
                        Real-time inventory tracking across multiple warehouses, locations and teams — with full audit trail.
                    </p>
                </div>

                {{-- Mini stats preview --}}
                <div class="wt-stats relative z-10 mt-10 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                        <p class="text-xl font-bold text-white">3</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Warehouses</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                        <p class="text-xl font-bold text-white">20</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Products</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-3 backdrop-blur-sm">
                        <p class="text-xl font-bold text-white">100%</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Traceable</p>
                    </div>
                </div>

                {{-- Feature highlights --}}
                <ul class="relative z-10 mt-8 space-y-4">
                    <li class="wt-feature flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 ring-1 ring-blue-500/30">
                            <svg class="h-3 w-3 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 11h6M9 15h4" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-white">Multi-location stock management</p>
                            <p class="text-xs text-zinc-500">Track inventory across warehouses and locations in real-time.</p>
                        </div>
                    </li>
                    <li class="wt-feature flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 ring-1 ring-indigo-500/30">
                            <svg class="h-3 w-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-white">Delivery & supplier tracking</p>
                            <p class="text-xs text-zinc-500">From supplier order to stock — every step logged.</p>
                        </div>
                    </li>
                    <li class="wt-feature flex items-start gap-3">
                        <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 ring-1 ring-emerald-500/30">
                            <svg class="h-3 w-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium text-white">Low-stock alerts & reports</p>
                            <p class="text-xs text-zinc-500">Instant overview of products below minimum stock level.</p>
                        </div>
                    </li>
                </ul>

                {{-- Bottom badge --}}
                <div class="wt-badge relative z-10 mt-auto">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-800 bg-zinc-900/80 px-3 py-1 text-xs text-zinc-500 backdrop-blur-sm">
                        <span class="size-1.5 rounded-full bg-green-400"></span>
                        Laravel 13 · Livewire 4 · Flux UI
                    </span>
                </div>
            </div>

            {{-- RIGHT PANEL: form --}}
            <div class="wt-form-panel relative flex w-full items-center justify-center bg-white px-8 py-12 dark:bg-zinc-950 lg:p-12">

                {{-- Subtle background grid pattern --}}
                <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:32px_32px]"></div>

                <div class="relative mx-auto flex w-full max-w-sm flex-col gap-8">

                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" wire:navigate
                       class="flex items-center gap-3 font-medium lg:hidden">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 shadow-md">
                            <x-app-logo-icon class="size-5 fill-current text-white" />
                        </span>
                        <span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">WareTrack</span>
                    </a>

                    <div class="rounded-2xl border border-zinc-200/80 bg-white/80 p-8 shadow-xl shadow-zinc-200/50 backdrop-blur-sm dark:border-zinc-800/80 dark:bg-zinc-900/80 dark:shadow-none">
                        {{ $slot }}
                    </div>

                    <p class="text-center text-xs text-zinc-400">
                        &copy; {{ date('Y') }} WareTrack &mdash; {{ __('Built with') }} Laravel 13 + Livewire 4
                    </p>
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

                    tl.from('.wt-logo',       { y: -24, opacity: 0, duration: 0.6 })
                      .from('.wt-tagline',    { y: 20,  opacity: 0, duration: 0.6 }, '-=0.3')
                      .from('.wt-sub',        { y: 16,  opacity: 0, duration: 0.5 }, '-=0.3')
                      .from('.wt-stats > *',  { y: 12,  opacity: 0, duration: 0.4, stagger: 0.08 }, '-=0.3')
                      .from('.wt-feature',    { x: -20, opacity: 0, duration: 0.45, stagger: 0.12 }, '-=0.2')
                      .from('.wt-badge',      { opacity: 0, duration: 0.4 }, '-=0.1')
                      .from('.wt-form-panel', { x: 30, opacity: 0, duration: 0.6 }, '-=0.8');
                }
            });
        </script>
    </body>
</html>
