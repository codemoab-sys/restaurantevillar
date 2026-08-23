@php
    $wh = preg_replace('/\D/', '', $web['contact_whatsapp'] ?: $company['phone']);
    $whatsapp_url = $wh ? 'https://wa.me/' . $wh : null;
    $tel_url = $company['phone'] ? 'tel:' . preg_replace('/\D/', '', $company['phone']) : null;
    $year = now()->year;
    $navSections = collect([
        ['id' => 'inicio',     'show' => $web['show_hero'],     'text' => 'Inicio'],
        ['id' => 'nosotros',   'show' => $web['show_about'],    'text' => 'Nosotros'],
        ['id' => 'servicios',  'show' => $web['show_features'], 'text' => 'Servicios'],
        ['id' => 'pedir',      'show' => $web['show_steps'],    'text' => 'Cómo pedir'],
        ['id' => 'contacto',   'show' => $web['show_contact'],  'text' => 'Contacto'],
    ])->filter(fn($s) => $s['show'])->values();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $web['hero_subtitle'] }}">
    <title>{{ $web['hero_title'] }} — {{ $company['name'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --brand: {{ $brand['primary'] }};
            --brand-dark: {{ $brand['primary_hover'] }};
            --brand-soft: {{ $brand['primary_soft'] }};
            --ink: #1e1e2d;
            --ink-soft: #6c6c90;
            --bg-soft: #faf7f2;
        }
        * { scroll-behavior: smooth; }
        html, body { background-color: #fff; }
        body {
            font-family: 'Outfit', sans-serif;
            color: var(--ink);
            background: #fff;
            -webkit-font-smoothing: antialiased;
        }
        section { scroll-margin-top: 84px; }

        /* ── Navbar ── */
        .site-nav {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(30,30,45,.08);
        }
        .site-nav .navbar-brand { font-weight: 800; letter-spacing: -.3px; color: var(--ink); }
        .site-nav .nav-link { font-weight: 600; color: var(--ink-soft); margin: 0 6px; }
        .site-nav .nav-link:hover { color: var(--brand); }
        .site-nav .logo-img {
            height: 40px;
            width: 40px;
            object-fit: cover;
            border-radius: 8px;
        }
        .brand-rect .site-nav .brand-name { display: none; }
        .brand-rect .site-nav .logo-img {
            height: 40px;
            width: auto;
            max-width: 220px;
            object-fit: contain !important;
        }
        .btn-brand { background: var(--brand); color: #fff; font-weight: 700; border-radius: 50px; border: none; }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .btn-ghost { background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.85); font-weight: 700; border-radius: 50px; }
        .btn-ghost:hover { background: #fff; color: var(--ink); }

        .floating-whatsapp-demo {
            position: fixed;
            right: 18px;
            bottom: 20px;
            z-index: 1060;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #1ecb5f 0%, #12a84b 100%);
            color: #fff;
            border-radius: 999px;
            padding: 10px 16px 10px 12px;
            box-shadow: 0 18px 35px -15px rgba(18, 168, 75, .75);
            text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease;
            user-select: none;
        }
        .floating-whatsapp-demo:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 22px 38px -18px rgba(18, 168, 75, .82);
        }
        .floating-whatsapp-demo:focus,
        .floating-whatsapp-demo:active {
            color: #fff;
            outline: none;
        }
        .floating-whatsapp-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .floating-whatsapp-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
            text-align: left;
        }
        .floating-whatsapp-text small {
            font-size: .62rem;
            opacity: .9;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .floating-whatsapp-text strong {
            font-size: .82rem;
            font-weight: 800;
        }

        @media (max-width: 576px) {
            .floating-whatsapp-demo {
                right: 12px;
                bottom: 14px;
                padding: 8px 12px 8px 8px;
            }
            .floating-whatsapp-text strong {
                font-size: .72rem;
            }
        }

        /* ── Hero ── */
        .hero {
            position: relative;
            min-height: 88vh;
            display: flex;
            align-items: center;
            color: #fff;
            isolation: isolate;
            overflow: hidden;
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            z-index: -2;
            background: linear-gradient(135deg, #1e1e2d 0%, #3a3a5c 55%, #ffb84d 140%);
            background-size: cover;
            background-position: center;
        }
        .hero-carousel {
            position: absolute;
            inset: 0;
            z-index: -2;
        }
        .hero-carousel .carousel-inner,
        .hero-carousel .carousel-item {
            height: 100%;
        }
        .hero-controls {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
        }
        .hero-controls .carousel-control-prev,
        .hero-controls .carousel-control-next,
        .hero-controls .carousel-indicators {
            pointer-events: auto;
        }
        .hero .carousel-indicators { margin-bottom: 16px; }
        .hero .carousel-indicators [data-bs-target] { width: 26px; border-top: 3px solid; }
        .hero-overlay {
            position: absolute;
            inset: 0;
            z-index: -1;
            background: linear-gradient(90deg, rgba(20,18,40,.82) 0%, rgba(20,18,40,.55) 55%, rgba(20,18,40,.25) 100%);
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,140,0,.18);
            border: 1px solid rgba(255,140,0,.45);
            color: #ffc36b;
            font-weight: 700;
            font-size: .82rem;
            padding: 6px 16px;
            border-radius: 50px;
            margin-bottom: 18px;
        }
        .hero h1 { font-weight: 800; font-size: clamp(2.2rem, 5vw, 3.6rem); letter-spacing: -.6px; }
        .hero p { font-weight: 300; font-size: clamp(1rem, 2vw, 1.2rem); max-width: 620px; opacity: .92; }

        /* ── Section headers ── */
        .sec-tag { color: var(--brand); font-weight: 700; letter-spacing: .12em; text-transform: uppercase; font-size: .78rem; }
        .sec-title { font-weight: 800; font-size: clamp(1.6rem, 3.5vw, 2.3rem); letter-spacing: -.4px; }
        .sec-sub { color: var(--ink-soft); max-width: 640px; }

        .bg-soft { background: var(--bg-soft); }

        /* ── About ── */
        .about-img-wrap { position: relative; }
        .about-img {
            border-radius: 24px;
            width: 100%;
            height: 460px;
            object-fit: cover;
            box-shadow: 0 24px 60px -18px rgba(30,30,45,.35);
        }
        .about-badge {
            position: absolute;
            bottom: -22px;
            left: -18px;
            background: var(--brand);
            color: #fff;
            padding: 16px 22px;
            border-radius: 18px;
            box-shadow: 0 18px 40px -12px rgba(255,140,0,.65);
            font-weight: 800;
            line-height: 1.1;
            text-align: center;
        }

        /* ── Feature cards ── */
        .feature-card {
            background: #fff;
            border: 1px solid rgba(30,30,45,.07);
            border-radius: 20px;
            padding: 28px 24px;
            height: 100%;
            transition: transform .22s, box-shadow .22s;
        }
        .feature-card:hover { transform: translateY(-6px); box-shadow: 0 22px 44px -18px rgba(30,30,45,.25); }
        .feature-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            background: var(--brand-soft);
            color: var(--brand);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }
        .feature-card h5 { font-weight: 800; }
        .feature-card p { color: var(--ink-soft); font-size: .95rem; margin: 0; }

        /* ── Steps ── */
        .step-item { text-align: center; padding: 24px 18px; }
        .step-icon {
            position: relative;
            width: 92px; height: 92px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid rgba(255,140,0,.35);
            box-shadow: 0 14px 30px -12px rgba(255,140,0,.45);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.1rem; color: var(--brand);
        }
        .step-item h6 { font-weight: 800; }
        .step-item p { color: var(--ink-soft); font-size: .9rem; margin: 0; }

        /* ── Contact ── */
        .contact-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid rgba(30,30,45,.07);
            padding: 22px;
            height: 100%;
            text-align: center;
            transition: transform .2s;
        }
        .contact-card:hover { transform: translateY(-4px); }
        .contact-icon {
            width: 48px; height: 48px;
            border-radius: 50%;
            background: var(--brand-soft);
            color: var(--brand);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
            margin: 0 auto 12px;
        }
        .contact-card h6 { font-weight: 800; font-size: .85rem; text-transform: uppercase; letter-spacing: .06em; color: var(--ink-soft); }
        .contact-card .cp-value { font-weight: 700; color: var(--ink); }

        /* ── CTA banner ── */
        .cta-banner {
            background: linear-gradient(120deg, #1e1e2d, #3a3a5c);
            border-radius: 28px;
            color: #fff;
            padding: 56px 32px;
            position: relative;
            overflow: hidden;
        }
        .cta-banner::after {
            content: '';
            position: absolute;
            right: -70px; top: -70px;
            width: 240px; height: 240px;
            border-radius: 50%;
            background: rgba(255,140,0,.22);
        }

        /* ── Footer ── */
        .site-footer { background: #17182a; color: rgba(255,255,255,.75); }
        .site-footer a { color: rgba(255,255,255,.85); text-decoration: none; }
        .site-footer a:hover { color: var(--brand); }

        @media (max-width: 768px) {
            .about-badge { left: 12px; }
            .hero { min-height: 72vh; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>

    {{-- ═══════════════ NAVBAR ═══════════════ --}}
    <nav class="navbar navbar-expand-lg site-nav py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#inicio">
                @if($company['logo'])
                    <img src="{{ $company['logo'] }}" id="landingLogo" class="logo-img" alt="{{ $company['name'] }}">
                @else
                    <i class="bi bi-shop text-primary" style="font-size:1.6rem; color:var(--brand) !important;"></i>
                @endif
                <span class="brand-name">{{ $company['name'] }}</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    @foreach($navSections as $item)
                        <li class="nav-item">
                            <a class="nav-link" href="#{{ $item['id'] }}">{{ $item['text'] }}</a>
                        </li>
                    @endforeach
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-brand px-4 py-2" href="{{ route('menu.index') }}" target="_blank" rel="noopener">
                            <i class="bi bi-qr-code-scan me-1"></i> Carta Digital
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-dark px-4 py-2" href="{{ route('login') }}">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sistema
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- ═══════════════ HERO ═══════════════ --}}
    @if($web['show_hero'])
    <header class="hero" id="inicio">
        @if(count($web['slides']) > 1)
            <div id="heroSlider" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="6000" data-bs-pause="hover">
                <div class="carousel-inner h-100">
                    @foreach($web['slides'] as $slide)
                        <div class="carousel-item h-100 {{ $loop->first ? 'active' : '' }}">
                            <div class="hero-bg" style="background-image:url('{{ $slide }}');"></div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="hero-controls">
                <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Siguiente</span>
                </button>
                <div class="carousel-indicators">
                    @foreach($web['slides'] as $idx => $slide)
                        <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="{{ $idx }}" class="{{ $idx === 0 ? 'active' : '' }}" aria-current="{{ $idx === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $idx + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        @elseif(count($web['slides']) === 1)
            <div class="hero-bg" style="background-image:url('{{ $web['slides'][0] }}');"></div>
        @else
            <div class="hero-bg"></div>
        @endif
        <div class="hero-overlay"></div>
        <div class="container py-5">
            <div class="col-lg-8">
                <div class="hero-badge">
                    <i class="bi bi-stars"></i> Sabor y buen servicio
                </div>
                <h1 class="mb-3">{{ $web['hero_title'] }}</h1>
                <p class="mb-4">{{ $web['hero_subtitle'] }}</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('menu.index') }}" target="_blank" rel="noopener" class="btn btn-brand btn-lg px-4">
                        <i class="bi bi-egg-fried me-2"></i>{{ $web['hero_btn_text'] }}
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar al Sistema
                    </a>
                    @if($whatsapp_url)
                        <a href="{{ $whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-ghost btn-lg px-4">
                            <i class="bi bi-whatsapp me-2"></i>Pedir ahora
                        </a>
                    @elseif($tel_url)
                        <a href="{{ $tel_url }}" class="btn btn-ghost btn-lg px-4">
                            <i class="bi bi-telephone me-2"></i>Llamar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>
    @endif

    {{-- ═══════════════ NOSOTROS ═══════════════ --}}
    @if($web['show_about'])
    <section class="py-5 my-lg-4" id="nosotros">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="sec-tag"><i class="bi bi-shop-window me-2"></i>Nosotros</span>
                    <h2 class="sec-title mt-2 mb-3">{{ $web['about_title'] }}</h2>
                    <p class="sec-sub lead" style="line-height:1.8;">{{ $web['about_text'] }}</p>
                    @if($whatsapp_url)
                        <a href="{{ $whatsapp_url }}" target="_blank" rel="noopener" class="btn btn-brand px-4 mt-3">
                            <i class="bi bi-whatsapp me-2"></i>Hablemos
                        </a>
                    @endif
                </div>
                <div class="col-lg-6">
                    <div class="about-img-wrap">
                        @if($web['about_image'])
                            <img src="{{ $web['about_image'] }}" alt="{{ $web['about_title'] }}" class="about-img">
                        @else
                            <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1000&q=60" alt="{{ $company['name'] }}" class="about-img">
                        @endif
                        @if($web['about_badge'])
                            <div class="about-badge">
                                <span style="font-size:1.15rem;">{{ $web['about_badge'] }}</span><br>
                                <span style="font-size:.7rem; letter-spacing:.08em; opacity:.9;">DE EXPERIENCIA</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════ SERVICIOS ═══════════════ --}}
    @if($web['show_features'])
    <section class="bg-soft py-5" id="servicios">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="sec-tag"><i class="bi bi-grid-fill me-2"></i>Lo que ofrecemos</span>
                <h2 class="sec-title mt-2">Nuestros servicios</h2>
            </div>
            <div class="row g-4">
                @foreach($web['features'] as $feature)
                    <div class="col-md-6 col-lg-3">
                        <div class="feature-card">
                            <div class="feature-icon"><i class="bi {{ $feature['icon'] ?? 'bi-egg-fried' }}"></i></div>
                            <h5>{{ $feature['title'] ?? '' }}</h5>
                            <p>{{ $feature['text'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════ CÓMO PEDIR ═══════════════ --}}
    @if($web['show_steps'])
    <section class="py-5" id="pedir">
        <div class="container py-4">
            <div class="text-center mb-4">
                <span class="sec-tag"><i class="bi bi-lightning-charge-fill me-2"></i>Simple y rápido</span>
                <h2 class="sec-title mt-2">¿Cómo pedir?</h2>
            </div>
            <div class="row mt-3">
                @foreach($web['steps'] as $step)
                    <div class="col-md-6 col-lg-3 step-item">
                        <div class="step-icon"><i class="bi {{ $step['icon'] ?? 'bi-check-circle-fill' }}"></i></div>
                        <h6>{{ $step['title'] ?? '' }}</h6>
                        <p>{{ $step['text'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════ CTA BANNER ═══════════════ --}}
    @if($web['show_hero'])
    <section class="py-3">
        <div class="container">
            <div class="cta-banner text-center">
                <h3 class="fw-bold mb-3" style="letter-spacing:-.3px;">¿Listo para probar lo mejor de {{ $company['name'] }}?</h3>
                <p class="mb-4" style="color:rgba(255,255,255,.85);">Explora nuestra carta digital desde tu celular y descubre todos nuestros platos.</p>
                <a href="{{ route('menu.index') }}" target="_blank" rel="noopener" class="btn btn-brand btn-lg px-5">
                    <i class="bi bi-qr-code-scan me-2"></i>Ver carta digital
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════ CONTACTO ═══════════════ --}}
    @if($web['show_contact'])
    <section class="py-5" id="contacto">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="sec-tag"><i class="bi bi-geo-alt-fill me-2"></i>Contáctanos</span>
                <h2 class="sec-title mt-2">{{ $web['contact_title'] }}</h2>
            </div>
            <div class="row g-4 justify-content-center">
                @if($web['contact_phone'])
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ $tel_url }}" class="text-decoration-none">
                        <div class="contact-card">
                            <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                            <h6>Teléfono</h6>
                            <div class="cp-value">{{ $web['contact_phone'] }}</div>
                        </div>
                    </a>
                </div>
                @endif
                @if($whatsapp_url)
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ $whatsapp_url }}" target="_blank" rel="noopener" class="text-decoration-none">
                        <div class="contact-card">
                            <div class="contact-icon" style="background:#e9f9ef; color:#22c55e;"><i class="bi bi-whatsapp"></i></div>
                            <h6>WhatsApp</h6>
                            <div class="cp-value">Escríbenos</div>
                        </div>
                    </a>
                </div>
                @endif
                @if($web['contact_address'])
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="contact-card">
                        <div class="contact-icon" style="background:#eef3ff; color:#3b82f6;"><i class="bi bi-geo-alt-fill"></i></div>
                        <h6>Dirección</h6>
                        <div class="cp-value">{{ $web['contact_address'] }}</div>
                    </div>
                </div>
                @endif
                @if($web['contact_hours'])
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="contact-card">
                        <div class="contact-icon" style="background:#fff3e0; color:#f97316;"><i class="bi bi-clock-fill"></i></div>
                        <h6>Horario</h6>
                        <div class="cp-value">{{ $web['contact_hours'] }}</div>
                    </div>
                </div>
                @endif
                @if($web['contact_email'])
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="mailto:{{ $web['contact_email'] }}" class="text-decoration-none">
                        <div class="contact-card">
                            <div class="contact-icon" style="background:#fdeef4; color:#ec4899;"><i class="bi bi-envelope-fill"></i></div>
                            <h6>Email</h6>
                            <div class="cp-value" style="font-size:.85rem;">{{ $web['contact_email'] }}</div>
                        </div>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    @if($web['show_footer'])
    <footer class="site-footer py-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                @if($company['logo'])
                    <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" style="height:32px; width:32px; object-fit:contain; border-radius:8px;">
                @endif
                <strong>{{ $company['name'] }}</strong>
            </div>
            <div class="d-flex gap-3 align-items-center">
                @if($tel_url)<a href="{{ $tel_url }}"><i class="bi bi-telephone-fill me-1"></i>{{ $company['phone'] }}</a>@endif
                @if($whatsapp_url)<a href="{{ $whatsapp_url }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>@endif
                <a href="{{ route('menu.index') }}" target="_blank" rel="noopener"><i class="bi bi-qr-code-scan"></i> Carta</a>
            </div>
            <div class="small">
                @if($web['footer_text'])
                    {{ $web['footer_text'] }}
                @else
                    © {{ $year }} {{ $company['name'] }}. Todos los derechos reservados.
                @endif
            </div>
        </div>
    </footer>
    @endif

    <a href="javascript:void(0);" onclick="return false;" class="floating-whatsapp-demo" aria-label="WhatsApp demo">
        <span class="floating-whatsapp-icon"><i class="bi bi-whatsapp"></i></span>
        <span class="floating-whatsapp-text">
            <small>demo</small>
            <strong>+51 999 999 999</strong>
        </span>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const mainNav = document.getElementById('mainNav');
        if (mainNav) {
            document.addEventListener('click', (e) => {
                if (!mainNav.classList.contains('show')) return;
                if (mainNav.contains(e.target)) return;
                bootstrap.Collapse.getOrCreateInstance(mainNav).hide();
            });
            mainNav.querySelectorAll('a.nav-link').forEach(link => {
                link.addEventListener('click', () => bootstrap.Collapse.getOrCreateInstance(mainNav).hide());
            });
        }

        // Logo rectangular: oculta el nombre y agranda el logo
        const landingLogo = document.getElementById('landingLogo');
        if (landingLogo) {
            const apply = () => {
                const w = landingLogo.naturalWidth, h = landingLogo.naturalHeight;
                if (w && h && h < w * 0.92) {
                    document.documentElement.classList.add('brand-rect');
                }
            };
            if (landingLogo.complete) apply();
            else landingLogo.addEventListener('load', apply);
        }
    </script>
</body>
</html>
