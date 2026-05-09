<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
        <style>
            /* Fade right edge of canvas into right-panel background */
            .wt-seam {
                position: absolute;
                inset: 0 0 0 auto;
                width: 280px;
                background: linear-gradient(90deg, transparent 0%, #04060f 100%);
                z-index: 8;
                pointer-events: none;
            }
            /* Hairline divider glow */
            .wt-seam::after {
                content: '';
                position: absolute;
                right: 0; top: 8%; bottom: 8%;
                width: 1px;
                background: linear-gradient(
                    to bottom,
                    transparent,
                    rgba(99,102,241,.4) 30%,
                    rgba(147,197,253,.5) 50%,
                    rgba(99,102,241,.4) 70%,
                    transparent
                );
            }
        </style>
    </head>
    <body class="min-h-screen antialiased" style="background:#04060f">

        <div class="relative grid h-dvh lg:max-w-none lg:grid-cols-2">

            {{-- ══════════════════════════════════════
                 LEFT PANEL
                 ══════════════════════════════════════ --}}
            <div class="relative hidden h-full overflow-hidden lg:block" style="background:#020511">

                <canvas id="wt-aurora" class="absolute inset-0 h-full w-full"></canvas>
                <div class="wt-seam"></div>

                {{-- UI layer --}}
                <div class="relative z-10 flex h-full flex-col px-12 py-10 text-white">

                    {{-- Logo top-left --}}
                    <a href="{{ route('home') }}" wire:navigate class="wt-logo flex w-fit items-center gap-3">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-900/40">
                            <x-app-logo-icon class="h-4 w-4 fill-current text-white" />
                        </span>
                        <span class="text-lg font-bold tracking-tight">WareTrack</span>
                    </a>

                    {{-- Center content --}}
                    <div class="my-auto pb-8">

                        <div class="wt-pill mb-6 inline-flex items-center gap-2 rounded-full border border-blue-400/25 bg-blue-500/10 px-3 py-1 text-[10.5px] font-semibold uppercase tracking-widest text-blue-300">
                            <span class="size-1.5 animate-pulse rounded-full bg-blue-400"></span>
                            Warehouse Management System
                        </div>

                        <h1 class="wt-headline text-[clamp(28px,3vw,44px)] font-extrabold leading-[1.1] tracking-tight">
                            Control every<br>
                            <span class="bg-gradient-to-r from-blue-400 via-indigo-300 to-cyan-300 bg-clip-text text-transparent">warehouse</span><br>
                            in real time.
                        </h1>

                        <p class="wt-sub mt-5 max-w-[300px] text-sm leading-relaxed text-white/40">
                            Real-time inventory across multiple locations, full audit trail, low-stock alerts — all in one place.
                        </p>

                        {{-- Real DB counts --}}
                        @php
                            $warehouseCount = \App\Models\Warehouse::count();
                            $productCount   = \App\Models\Product::count();
                            $locationCount  = \App\Models\Location::count();
                        @endphp
                        <div class="wt-stats mt-10 flex gap-10">
                            <div>
                                <div class="text-2xl font-bold tracking-tight">{{ $warehouseCount }}</div>
                                <div class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-white/30">{{ Str::plural('Warehouse', $warehouseCount) }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold tracking-tight">{{ $productCount }}</div>
                                <div class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-white/30">{{ Str::plural('Product', $productCount) }}</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold tracking-tight">{{ $locationCount }}</div>
                                <div class="mt-1 text-[10px] font-semibold uppercase tracking-widest text-white/30">{{ Str::plural('Location', $locationCount) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Status bottom --}}
                    <div class="wt-status">
                        <span class="inline-flex items-center gap-1.5 text-xs text-white/25">
                            <span class="size-1.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"></span>
                            All systems operational
                        </span>
                    </div>

                </div>
            </div>

            {{-- ══════════════════════════════════════
                 RIGHT PANEL
                 ══════════════════════════════════════ --}}
            <div class="wt-form-panel relative flex h-full w-full items-center justify-center overflow-hidden px-8 py-10 lg:px-12" style="background:#04060f">

                {{-- Aurora bleed-in glow from left --}}
                <div class="pointer-events-none absolute inset-0" style="background:
                    radial-gradient(ellipse 100% 80% at -5% 50%, rgba(59,130,246,.14) 0%, transparent 55%),
                    radial-gradient(ellipse 60%  60% at -5% 50%, rgba(99,102,241,.10) 0%, transparent 45%)
                "></div>

                <div class="relative w-full max-w-[360px]">

                    {{-- Mobile logo only --}}
                    <a href="{{ route('home') }}" wire:navigate class="mb-6 flex items-center gap-3 lg:hidden">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md">
                            <x-app-logo-icon class="size-4 fill-current text-white" />
                        </span>
                        <span class="text-base font-bold tracking-tight text-white">WareTrack</span>
                    </a>

                    {{-- Glass card --}}
                    <div class="wt-card overflow-hidden rounded-2xl border border-white/[.07]"
                         style="background:rgba(255,255,255,.04);backdrop-filter:blur(28px);-webkit-backdrop-filter:blur(28px);box-shadow:0 24px 64px rgba(0,0,0,.6),0 0 0 1px rgba(255,255,255,.03) inset">

                        {{-- Card header --}}
                        <div class="flex flex-col items-center gap-2.5 border-b border-white/[.06] px-8 py-5">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-900/40">
                                <x-app-logo-icon class="size-5 fill-current text-white" />
                            </span>
                            <span class="text-sm font-semibold text-white">WareTrack</span>
                        </div>

                        {{-- Form --}}
                        <div class="px-8 py-7">
                            {{ $slot }}
                        </div>
                    </div>

                </div>
            </div>

        </div>

        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts

        <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ── Aurora ──────────────────────────────── */
            const cv = document.getElementById('wt-aurora');
            if (!cv) return;
            const ctx = cv.getContext('2d');
            let W, H, t = 0, raf;

            function resize() { W = cv.width = cv.offsetWidth; H = cv.height = cv.offsetHeight; }
            resize();
            window.addEventListener('resize', () => { cancelAnimationFrame(raf); resize(); });

            const mouse = { x: -1, y: -1 };
            document.addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; });

            /*
              Aurora ribbons — drawn as STROKES (not filled polygons).
              Hues are strictly blue/cyan range: 185–240.
              Each band is:
                1. A thick blurred stroke (glow body) with screen blend
                2. A thin crisp stroke (bright edge)
            */
            const bands = [
                { y:.50, amp:.09, freq:.65, spd:.16, h1:210, h2:230, w:55, a:.55 },
                { y:.40, amp:.07, freq:.50, spd:.12, h1:225, h2:240, w:40, a:.45 },
                { y:.62, amp:.11, freq:.85, spd:.20, h1:195, h2:215, w:65, a:.42 },
                { y:.33, amp:.06, freq:.40, spd:.09, h1:235, h2:242, w:35, a:.30 },
                { y:.70, amp:.08, freq:1.0, spd:.24, h1:188, h2:208, w:45, a:.25 },
            ];

            function wavePts(b, i) {
                const pts = [];
                const mx = mouse.x > 0 ? mouse.x : W * .4;
                for (let s = 0; s <= 100; s++) {
                    const px = s / 100 * W;
                    const nx = s / 100;
                    const pull = Math.exp(-((px - mx) / W) * ((px - mx) / W) * 25)
                                 * .035 * Math.sin(t * 2.5 + i);
                    const py = H * (b.y
                        + Math.sin(nx * Math.PI * 2.8 * b.freq + t * b.spd) * b.amp
                        + Math.sin(nx * Math.PI * 4.5 * b.freq * .7 + t * b.spd * 1.3) * b.amp * .35
                        + pull);
                    pts.push([px, py]);
                }
                return pts;
            }

            function drawBand(pts, b) {
                ctx.beginPath();
                pts.forEach(([px, py], j) => j === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py));

                const g = ctx.createLinearGradient(0, 0, W, 0);
                g.addColorStop(0,    `hsla(${b.h1},85%,65%,0)`);
                g.addColorStop(.15,  `hsla(${b.h1},85%,65%,${b.a})`);
                g.addColorStop(.5,   `hsla(${(b.h1+b.h2)/2},85%,65%,${b.a})`);
                g.addColorStop(.85,  `hsla(${b.h2},85%,65%,${b.a})`);
                g.addColorStop(1,    `hsla(${b.h2},85%,65%,0)`);

                ctx.strokeStyle = g;
                ctx.lineWidth   = b.w + Math.sin(t * .6 + b.y * 10) * 8;
                ctx.stroke();
            }

            function draw() {
                raf = requestAnimationFrame(draw);
                t += .005;
                ctx.clearRect(0, 0, W, H);

                /* Dark sky */
                const sky = ctx.createLinearGradient(0, 0, 0, H);
                sky.addColorStop(0, '#010411');
                sky.addColorStop(1, '#030818');
                ctx.fillStyle = sky;
                ctx.fillRect(0, 0, W, H);

                /* ── Glow bodies (thick blurred strokes, screen blend) ── */
                ctx.save();
                ctx.globalCompositeOperation = 'screen';
                ctx.filter = 'blur(18px)';
                bands.forEach((b, i) => drawBand(wavePts(b, i), b));
                ctx.restore();

                /* ── Crisp bright ribbon edges ── */
                ctx.save();
                ctx.globalCompositeOperation = 'screen';
                ctx.filter = 'blur(2.5px)';
                bands.slice(0, 4).forEach((b, i) => {
                    const pts = wavePts(b, i);
                    ctx.beginPath();
                    pts.forEach(([px, py], j) => j === 0 ? ctx.moveTo(px, py) : ctx.lineTo(px, py));

                    const rg = ctx.createLinearGradient(0, 0, W, 0);
                    rg.addColorStop(0,   'transparent');
                    rg.addColorStop(.2,  `hsla(${b.h1},100%,90%,.7)`);
                    rg.addColorStop(.8,  `hsla(${b.h2},100%,90%,.7)`);
                    rg.addColorStop(1,   'transparent');
                    ctx.strokeStyle = rg;
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                });
                ctx.restore();

                /* ── Stars ── */
                ctx.save();
                ctx.globalCompositeOperation = 'source-over';
                for (let i = 0; i < 200; i++) {
                    const sx = (Math.sin(i * 7.3)  * .5 + .5) * W;
                    const sy = (Math.sin(i * 13.7) * .5 + .5) * H * .65;
                    const sr = (Math.sin(i * 3.1)  * .5 + .5) * 1.1 + .2;
                    const twinkle = .35 + .65 * Math.sin(t * (1 + (Math.sin(i*2.1)*.5+.5)) * 1.8 + i);
                    ctx.globalAlpha = twinkle * .65;
                    ctx.fillStyle   = '#fff';
                    ctx.beginPath();
                    ctx.arc(sx, sy, sr, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.restore();
            }
            draw();

            /* ── GSAP entrance ────────────────────────── */
            if (typeof gsap !== 'undefined') {
                gsap.timeline({ defaults: { ease: 'power3.out' } })
                    .from('.wt-logo',        { y: -18, opacity: 0, duration: .55 })
                    .from('.wt-pill',        { y:  16, opacity: 0, duration: .55 }, '-=.25')
                    .from('.wt-headline',    { y:  20, opacity: 0, duration: .6  }, '-=.3')
                    .from('.wt-sub',         { y:  14, opacity: 0, duration: .5  }, '-=.3')
                    .from('.wt-stats > *',   { y:  10, opacity: 0, duration: .4, stagger: .09 }, '-=.25')
                    .from('.wt-status',      { opacity: 0, duration: .4 }, '-=.1')
                    .from('.wt-card',        { y:  20, opacity: 0, duration: .6  }, '-=.8');
            }
        });
        </script>
    </body>
</html>
