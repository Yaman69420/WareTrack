<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
        <style>
            /* ── Aurora seam: fades canvas into right-panel bg ── */
            .wt-seam {
                position: absolute;
                inset: 0 0 0 auto;
                width: 260px;
                background: linear-gradient(90deg, transparent 0%, #050710 100%);
                z-index: 8;
                pointer-events: none;
            }
            /* Hairline accent glow at the split edge */
            .wt-seam::after {
                content: '';
                position: absolute;
                right: 0; top: 6%; bottom: 6%;
                width: 1px;
                background: linear-gradient(
                    to bottom,
                    transparent 0%,
                    rgba(255,255,255,.05) 20%,
                    rgba(129,140,248,.45) 50%,
                    rgba(255,255,255,.05) 80%,
                    transparent 100%
                );
                opacity: .4;
            }
        </style>
    </head>
    <body class="min-h-screen antialiased" style="background:#050710">

        <div class="relative grid h-dvh flex-col items-center justify-center lg:max-w-none lg:grid-cols-2 lg:px-0">

            {{-- ══════════════════════════════════════
                 LEFT PANEL — Aurora animation
                 ══════════════════════════════════════ --}}
            <div class="wt-panel relative hidden h-full flex-col overflow-hidden lg:flex" style="background:#020613">

                {{-- Aurora canvas fills the entire panel --}}
                <canvas id="wt-aurora" class="absolute inset-0 w-full h-full"></canvas>

                {{-- Gradient seam transition --}}
                <div class="wt-seam"></div>

                {{-- All UI sits above the canvas --}}
                <div class="relative z-10 flex h-full flex-col p-10 text-white">

                    {{-- Logo --}}
                    <a href="{{ route('home') }}" wire:navigate class="wt-logo flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-lg shadow-blue-900/50">
                            <x-app-logo-icon class="h-5 w-5 fill-current text-white" />
                        </span>
                        <span class="text-xl font-bold tracking-tight">WareTrack</span>
                    </a>

                    {{-- Spacer --}}
                    <div class="mt-auto">

                        {{-- Badge --}}
                        <div class="wt-tagline mb-5 inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-300">
                            <span class="size-1.5 animate-pulse rounded-full bg-indigo-400"></span>
                            Live system
                        </div>

                        {{-- Headline --}}
                        <p class="wt-tagline text-[clamp(30px,3.4vw,46px)] font-extrabold leading-[1.1] tracking-tight text-white">
                            Control every<br>
                            <span class="bg-gradient-to-r from-indigo-400 to-cyan-300 bg-clip-text text-transparent">warehouse</span><br>
                            in real time.
                        </p>

                        {{-- Subline --}}
                        <p class="wt-sub mt-4 max-w-xs text-[14.5px] leading-7 text-white/45">
                            Track inventory, manage deliveries and reduce waste — all from a single dashboard.
                        </p>

                        {{-- Stats --}}
                        <div class="wt-stats mt-9 flex gap-8">
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-white">98.4%</p>
                                <p class="mt-1 text-[10.5px] font-medium uppercase tracking-widest text-white/35">Accuracy</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-white">3.2k</p>
                                <p class="mt-1 text-[10.5px] font-medium uppercase tracking-widest text-white/35">Products</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold tracking-tight text-white">12</p>
                                <p class="mt-1 text-[10.5px] font-medium uppercase tracking-widest text-white/35">Locations</p>
                            </div>
                        </div>

                    </div>

                    {{-- Bottom status --}}
                    <div class="wt-badge mt-8">
                        <span class="inline-flex items-center gap-1.5 text-xs text-white/30">
                            <span class="size-1.5 rounded-full bg-emerald-400"></span>
                            All systems operational
                        </span>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════
                 RIGHT PANEL — Form card
                 ══════════════════════════════════════ --}}
            <div class="wt-form-panel relative flex w-full items-center justify-center overflow-hidden px-8 py-12 lg:p-12" style="background:#050710">

                {{-- Matching ambient glow from the left --}}
                <div class="pointer-events-none absolute inset-0" style="background:radial-gradient(ellipse 70% 90% at 8% 55%, rgba(99,102,241,.09) 0%, transparent 70%)"></div>

                <div class="relative mx-auto flex w-full max-w-sm flex-col gap-6">

                    {{-- Mobile logo --}}
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3 font-medium lg:hidden">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 shadow-md">
                            <x-app-logo-icon class="size-5 fill-current text-white" />
                        </span>
                        <span class="text-base font-semibold text-white">WareTrack</span>
                    </a>

                    {{-- Glass card --}}
                    <div class="rounded-2xl border border-indigo-500/20 backdrop-blur-[32px]"
                         style="background:rgba(255,255,255,.045);box-shadow:0 32px 72px rgba(0,0,0,.65),inset 0 1px 0 rgba(255,255,255,.07)">

                        {{-- Card header --}}
                        <div class="flex flex-col items-center gap-3 border-b border-white/[.07] px-8 py-6">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-900/50">
                                <x-app-logo-icon class="size-5 fill-current text-white" />
                            </div>
                            <p class="text-sm font-semibold text-white">WareTrack</p>
                        </div>

                        {{-- Form slot --}}
                        <div class="px-8 py-6">
                            {{ $slot }}
                        </div>
                    </div>

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

            /* ════════════════════════════════════════
               AURORA canvas animation
               ════════════════════════════════════════ */
            const canvas = document.getElementById('wt-aurora');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            let W, H, t = 0, animFrame;

            function resize() {
                W = canvas.width  = canvas.offsetWidth;
                H = canvas.height = canvas.offsetHeight;
            }
            resize();
            window.addEventListener('resize', () => {
                cancelAnimationFrame(animFrame);
                resize();
                draw();
            });

            const mouse = { x: W / 2, y: H / 2 };
            document.addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; });

            /* Aurora bands — [centerY%, amplitude%, freqMultiplier, speed, hue1, hue2, alpha] */
            const bands = [
                { y:.55, amp:.10, freq:.7,  spd:.18, h1:220, h2:260, a:.55 },
                { y:.42, amp:.08, freq:.55, spd:.13, h1:260, h2:290, a:.45 },
                { y:.65, amp:.12, freq:.9,  spd:.22, h1:195, h2:230, a:.40 },
                { y:.35, amp:.07, freq:.45, spd:.10, h1:280, h2:310, a:.30 },
                { y:.72, amp:.09, freq:1.1, spd:.28, h1:170, h2:210, a:.25 },
            ];

            function pseudoRand(n) {
                const x = Math.sin(n) * 43758.5453123;
                return x - Math.floor(x);
            }

            function draw() {
                animFrame = requestAnimationFrame(draw);
                t += 0.005;
                ctx.clearRect(0, 0, W, H);

                /* Deep sky gradient */
                const sky = ctx.createLinearGradient(0, 0, 0, H);
                sky.addColorStop(0, '#020613');
                sky.addColorStop(1, '#050c24');
                ctx.fillStyle = sky;
                ctx.fillRect(0, 0, W, H);

                /* Mouse horizontal position within the panel */
                const mx = Math.min(mouse.x, W);

                /* Blurred aurora fills */
                ctx.save();
                ctx.filter = 'blur(34px)';
                bands.forEach((b, i) => {
                    const pts = [];
                    for (let s = 0; s <= 120; s++) {
                        const px = s / 120 * W;
                        const nx = s / 120;
                        /* Subtle cursor ripple */
                        const pull = Math.exp(-((px - mx) / W) * ((px - mx) / W) * 20)
                                     * 0.04 * Math.sin(t * 3 + i);
                        const py = H * (
                            b.y
                            + Math.sin(nx * Math.PI * 3 * b.freq + t * b.spd) * b.amp
                            + Math.sin(nx * Math.PI * 5 * b.freq * 0.7 + t * b.spd * 1.4) * b.amp * 0.4
                            + pull
                        );
                        pts.push([px, py]);
                    }

                    const g = ctx.createLinearGradient(0, 0, W, 0);
                    g.addColorStop(0,    `hsla(${b.h1}, 90%, 65%, 0)`);
                    g.addColorStop(0.25, `hsla(${b.h1}, 90%, 65%, ${b.a})`);
                    g.addColorStop(0.5,  `hsla(${(b.h1 + b.h2) / 2}, 90%, 65%, ${b.a * 1.2})`);
                    g.addColorStop(0.75, `hsla(${b.h2}, 90%, 65%, ${b.a})`);
                    g.addColorStop(1,    `hsla(${b.h2}, 90%, 65%, 0)`);

                    ctx.beginPath();
                    ctx.moveTo(0, H);
                    pts.forEach(([px, py]) => ctx.lineTo(px, py));
                    ctx.lineTo(W, H);
                    ctx.closePath();
                    ctx.fillStyle = g;
                    ctx.fill();
                });
                ctx.restore();

                /* Crisp ribbon highlights */
                ctx.save();
                ctx.filter = 'blur(4px)';
                bands.slice(0, 3).forEach(b => {
                    ctx.beginPath();
                    for (let s = 0; s <= 200; s++) {
                        const px = s / 200 * W;
                        const nx = s / 200;
                        const py = H * (
                            b.y
                            + Math.sin(nx * Math.PI * 3 * b.freq + t * b.spd) * b.amp
                            + Math.sin(nx * Math.PI * 5 * b.freq * 0.7 + t * b.spd * 1.4) * b.amp * 0.4
                        );
                        s === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py);
                    }
                    const rg = ctx.createLinearGradient(0, 0, W, 0);
                    rg.addColorStop(0,   'transparent');
                    rg.addColorStop(0.3, `hsla(${b.h1}, 100%, 88%, .65)`);
                    rg.addColorStop(0.7, `hsla(${b.h2}, 100%, 88%, .65)`);
                    rg.addColorStop(1,   'transparent');
                    ctx.strokeStyle = rg;
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                });
                ctx.restore();

                /* Stars */
                ctx.save();
                for (let i = 0; i < 180; i++) {
                    const sx = pseudoRand(i * 7  + 42) * W;
                    const sy = pseudoRand(i * 13 + 42) * H * 0.55;
                    const ss = pseudoRand(i * 3  + 42) * 1.2 + 0.3;
                    ctx.globalAlpha = (0.4 + 0.6 * Math.sin(t * (1 + pseudoRand(i * 2)) * 2 + i)) * 0.7;
                    ctx.fillStyle = '#fff';
                    ctx.beginPath();
                    ctx.arc(sx, sy, ss, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.restore();
            }

            draw();

            /* ════════════════════════════════════════
               GSAP entrance animations
               ════════════════════════════════════════ */
            if (typeof gsap !== 'undefined') {
                const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

                tl.from('.wt-logo',        { y: -20, opacity: 0, duration: 0.6 })
                  .from('.wt-tagline',     { y:  22, opacity: 0, duration: 0.65, stagger: 0.08 }, '-=0.3')
                  .from('.wt-sub',         { y:  16, opacity: 0, duration: 0.5 }, '-=0.25')
                  .from('.wt-stats > *',   { y:  12, opacity: 0, duration: 0.4, stagger: 0.09 }, '-=0.25')
                  .from('.wt-badge',       { opacity: 0, duration: 0.4 }, '-=0.1')
                  .from('.wt-form-panel',  { x: 28, opacity: 0, duration: 0.65 }, '-=0.75');
            }
        });
        </script>
    </body>
</html>
