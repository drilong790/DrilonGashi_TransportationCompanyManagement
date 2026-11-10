<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07111b">
    <meta name="description" content="Një pamje e qartë për menaxhimin e transportit, flotës dhe operacioneve të kompanisë.">
    <title>Transporti — çdo rrugë nën kontroll</title>
    <link rel="stylesheet" href="assets/intro.css?v=20261110">
    <script src="assets/intro.js?v=20261110" defer></script>
</head>
<body class="intro-page">
    <a class="skip-link" href="#main-content">Kalo te përmbajtja</a>

    <header class="intro-header">
        <a class="intro-brand" href="#home" aria-label="Transporti, faqja kryesore">
            <span class="intro-brand-mark" aria-hidden="true"><img src="assets/brand-symbol.svg?v=20261110" alt=""></span>
            <span class="intro-brand-copy">
                <strong>TRANSPORTI</strong>
                <span>COMPANY MANAGEMENT</span>
            </span>
        </a>
        <nav class="intro-nav" aria-label="Navigimi i faqes">
            <a href="#platforma">Platforma</a>
            <a href="#rrjedha">Si funksionon</a>
        </nav>
        <a class="intro-login" href="?page=login">
            Hyr në sistem
            <span aria-hidden="true">↗</span>
        </a>
    </header>

    <main id="main-content">
        <section class="intro-hero" id="home" aria-labelledby="hero-title">
            <div class="hero-grid" aria-hidden="true"></div>
            <div class="hero-glow hero-glow-one" aria-hidden="true"></div>
            <div class="hero-glow hero-glow-two" aria-hidden="true"></div>
            <div class="hero-content">
                <p class="hero-kicker"><span class="live-dot" aria-hidden="true"></span> MENAXHIMI I TRANSPORTIT, I RIMENDUAR</p>
                <h1 id="hero-title">Çdo rrugë.<br><span>Një pamje e qartë.</span></h1>
                <p class="hero-description">
                    Transportet, ekipi dhe flota — të organizuara në një vend të vetëm,
                    që ti të përqendrohesh te lëvizja përpara.
                </p>
                <div class="hero-actions">
                    <a class="intro-button intro-button-primary" href="?page=login">
                        Hyr në platformë <span aria-hidden="true">↗</span>
                    </a>
                    <a class="intro-text-link" href="#platforma">
                        Zbulo platformën <span aria-hidden="true">↓</span>
                    </a>
                </div>
                <div class="hero-caption">
                    <span class="caption-line" aria-hidden="true"></span>
                    <span>Rruga jote, nën kontroll.</span>
                </div>
            </div>

            <div class="hero-art" aria-label="Ilustrim i një kamioni në një rrugë të ndriçuar">
                <div class="hero-coordinate coordinate-top" aria-hidden="true">TRANSPORT / SYSTEM</div>
                <div class="hero-coordinate coordinate-bottom" aria-hidden="true">ROUTE / 001</div>
                <svg class="hero-route" viewBox="0 0 720 580" role="img" aria-labelledby="hero-route-title">
                    <title id="hero-route-title">Rrugë e stilizuar me kamion në lëvizje</title>
                    <defs>
                        <linearGradient id="route-gradient" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0" stop-color="#f8b65b" stop-opacity=".1"/>
                            <stop offset=".5" stop-color="#ffc56f"/>
                            <stop offset="1" stop-color="#f18f43" stop-opacity=".42"/>
                        </linearGradient>
                        <radialGradient id="route-halo">
                            <stop offset="0" stop-color="#ffc56f" stop-opacity=".2"/>
                            <stop offset="1" stop-color="#ffc56f" stop-opacity="0"/>
                        </radialGradient>
                        <filter id="truck-shadow" x="-60%" y="-60%" width="220%" height="220%">
                            <feGaussianBlur in="SourceAlpha" stdDeviation="13"/>
                            <feOffset dy="12"/>
                            <feComponentTransfer><feFuncA type="linear" slope=".28"/></feComponentTransfer>
                            <feMerge><feMergeNode/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <circle cx="390" cy="292" r="248" class="route-orbit route-orbit-outer"/>
                    <circle cx="390" cy="292" r="190" class="route-orbit route-orbit-inner"/>
                    <circle cx="390" cy="292" r="132" fill="url(#route-halo)"/>
                    <path class="route-echo" d="M60 440C145 440 128 335 248 335s93-171 201-171 90 114 207 114"/>
                    <path class="hero-route-path" d="M60 440C145 440 128 335 248 335s93-171 201-171 90 114 207 114"/>
                    <circle cx="60" cy="440" r="8" class="route-node route-node-start"/>
                    <circle cx="656" cy="278" r="9" class="route-node route-node-end"/>
                    <circle cx="656" cy="278" r="19" class="route-node-pulse"/>
                    <g class="hero-truck" filter="url(#truck-shadow)">
                        <path d="M-43-13h46a8 8 0 0 1 8 8v29h-54Z" fill="#f3a94f"/>
                        <path d="M11-4h22l15 16v12H11Z" fill="#ffd38d"/>
                        <path d="M18 0h12l10 11H18Z" fill="#283643"/>
                        <rect x="-33" y="-4" width="21" height="13" rx="2" fill="#ffe2ad" opacity=".86"/>
                        <circle cx="-28" cy="25" r="7" fill="#101a22" stroke="#f4f1e8" stroke-width="2"/>
                        <circle cx="31" cy="25" r="7" fill="#101a22" stroke="#f4f1e8" stroke-width="2"/>
                        <path d="M-43 13h54" stroke="#fff1d8" stroke-opacity=".4"/>
                    </g>
                    <g class="route-label route-label-start">
                        <circle cx="60" cy="440" r="2" fill="#ffd18a"/>
                        <text x="43" y="474">START</text>
                        <text x="43" y="492" class="route-label-sub">PLANIFIKO</text>
                    </g>
                    <g class="route-label route-label-end">
                        <text x="600" y="238">DESTINACIONI</text>
                        <text x="600" y="256" class="route-label-sub">STATUSI NË NJË VEND</text>
                    </g>
                    <circle cx="536" cy="402" r="3" class="map-star"/>
                    <circle cx="181" cy="179" r="2.5" class="map-star"/>
                    <circle cx="570" cy="140" r="2" class="map-star"/>
                    <circle cx="319" cy="78" r="2.5" class="map-star"/>
                </svg>
                <div class="hero-art-note">
                    <span class="art-note-icon" aria-hidden="true">↗</span>
                    <span><strong>Një sistem.</strong><br>Çdo lëvizje e organizuar.</span>
                </div>
            </div>
            <a class="scroll-cue" href="#rrjedha">
                <span>VAZHDO RRUGËTIMIN</span>
                <span class="scroll-cue-line" aria-hidden="true"></span>
            </a>
            <span class="hero-index" aria-hidden="true">01 <i></i> 04</span>
        </section>

        <section class="journey-section" id="rrjedha" aria-labelledby="journey-title" data-journey>
            <div class="journey-pin">
                <div class="journey-heading">
                    <p class="section-kicker"><span>01</span> RRUGËTIMI I NJË TRANSPORTI</p>
                    <h2 id="journey-title">Nga planifikimi<br>te <span>destinacioni.</span></h2>
                    <p class="journey-intro">Lëvizjet e përditshme bëhen më të qarta kur çdo hap ka vendin e vet.</p>
                </div>
                <div class="journey-map" aria-hidden="true">
                    <div class="map-grid"></div>
                    <div class="map-contour contour-one"></div>
                    <div class="map-contour contour-two"></div>
                    <div class="map-contour contour-three"></div>
                    <svg class="journey-svg" viewBox="0 0 1000 600" preserveAspectRatio="xMidYMid meet">
                        <path class="journey-path-shadow" d="M65 495C185 480 143 364 286 375s122-187 246-170 112 172 235 95 111-26 168-101"/>
                        <path class="journey-path-base" d="M65 495C185 480 143 364 286 375s122-187 246-170 112 172 235 95 111-26 168-101"/>
                        <path class="journey-path-progress" data-route-path d="M65 495C185 480 143 364 286 375s122-187 246-170 112 172 235 95 111-26 168-101"/>
                        <circle class="journey-stop stop-one" cx="65" cy="495" r="10"/>
                        <circle class="journey-stop stop-two" cx="286" cy="375" r="8"/>
                        <circle class="journey-stop stop-three" cx="532" cy="205" r="8"/>
                        <circle class="journey-stop stop-four" cx="935" cy="199" r="11"/>
                        <circle class="journey-destination-ring" cx="935" cy="199" r="24"/>
                        <g data-route-truck class="journey-truck">
                            <rect x="-28" y="-13" width="36" height="24" rx="4" fill="#f8b65b"/>
                            <path d="M8-7h14l12 12v6H8Z" fill="#f8d7a4"/>
                            <path d="M12-3h9l8 8H12Z" fill="#23323c"/>
                            <circle cx="-17" cy="13" r="5" fill="#101820" stroke="#f8e9d1" stroke-width="2"/>
                            <circle cx="24" cy="13" r="5" fill="#101820" stroke="#f8e9d1" stroke-width="2"/>
                        </g>
                    </svg>
                    <div class="map-coordinates map-coordinates-a">TRANSPORTI / PLANIFIKIMI</div>
                    <div class="map-coordinates map-coordinates-b">LIVE ROUTE&nbsp; / &nbsp;TC-01</div>
                </div>
                <div class="journey-steps" aria-label="Hapat e menaxhimit të transportit">
                    <article class="journey-step is-active" data-step="0">
                        <span class="step-number">01 / PLANIFIKO</span>
                        <h3>Gjithçka nis<br>me një plan.</h3>
                        <p>Regjistro transportin, klientin, shoferin dhe destinacionin në një rrjedhë të vetme.</p>
                    </article>
                    <article class="journey-step" data-step="1">
                        <span class="step-number">02 / CAKTO</span>
                        <h3>Njerëzit dhe mjetet,<br>në vendin e duhur.</h3>
                        <p>Mbaji të dhënat e klientëve, shoferëve dhe automjeteve të organizuara.</p>
                    </article>
                    <article class="journey-step" data-step="2">
                        <span class="step-number">03 / NDIQ</span>
                        <h3>Statusi përditësohet.<br>Pamja mbetet e qartë.</h3>
                        <p>Shiko transportet sipas statusit dhe filtro listën për të gjetur atë që të duhet.</p>
                    </article>
                    <article class="journey-step" data-step="3">
                        <span class="step-number">04 / ANALIZO</span>
                        <h3>Çdo periudhë,<br>me më shumë kontekst.</h3>
                        <p>Krahaso raportet dhe eksporto rezultatet për analizë të mëtejshme.</p>
                    </article>
                </div>
                <div class="journey-progress" aria-hidden="true"><span data-journey-progress></span></div>
            </div>
        </section>

        <section class="platform-section" id="platforma" aria-labelledby="platform-title">
            <div class="section-heading-block" data-reveal>
                <p class="section-kicker"><span>02</span> NJË PAMJE, KONTROLL I PLOTË</p>
                <h2 id="platform-title">Më pak kërkim.<br><span>Më shumë përpara.</span></h2>
                <p>Një hapësirë e përbashkët për informacionin që mban operacionet në lëvizje.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card feature-card-wide" data-reveal>
                    <div class="feature-card-copy">
                        <span class="feature-index">01 — OPERACIONET</span>
                        <h3>Transportet,<br>qartë nga fillimi.</h3>
                        <p>Planifiko dërgesat, shiko statuset dhe gjej shpejt regjistrimet me filtra praktikë.</p>
                    </div>
                    <div class="mini-dashboard mini-dashboard-routes" aria-hidden="true">
                        <div class="mini-window-bar"><i></i><i></i><i></i><span>TRANSPORTET</span></div>
                        <div class="mini-route-row"><span class="mini-route-dot dot-orange"></span><span class="mini-route-lines"><i></i><i></i></span><b>Në proces</b></div>
                        <div class="mini-route-row"><span class="mini-route-dot dot-blue"></span><span class="mini-route-lines"><i></i><i></i></span><b>Planifikuar</b></div>
                        <div class="mini-route-row"><span class="mini-route-dot dot-green"></span><span class="mini-route-lines"><i></i><i></i></span><b>Përfunduar</b></div>
                    </div>
                    <span class="feature-glow" aria-hidden="true"></span>
                </article>
                <article class="feature-card" data-reveal>
                    <div class="feature-icon feature-icon-fleet" aria-hidden="true">
                        <svg viewBox="0 0 48 48"><path d="M5 13h25v21H5zM30 20h8l6 7v7H30z"/><path d="M34 23h3l4 5h-7z" class="icon-cutout"/><circle cx="14" cy="36" r="4"/><circle cx="37" cy="36" r="4"/></svg>
                    </div>
                    <span class="feature-index">02 — EKIPI DHE FLOTA</span>
                    <h3>Burimet e tua,<br>në një vend.</h3>
                    <p>Mbaj të rregullta të dhënat e klientëve, shoferëve dhe automjeteve.</p>
                    <div class="feature-orbit" aria-hidden="true"><span></span><span></span><span></span></div>
                </article>
                <article class="feature-card" data-reveal>
                    <div class="feature-icon feature-icon-insight" aria-hidden="true">
                        <svg viewBox="0 0 48 48"><path d="M8 39V25h8v14zM21 39V15h8v24zM34 39V8h8v31z"/><path d="M7 19 18 12l9 3 12-9" class="icon-line"/></svg>
                    </div>
                    <span class="feature-index">03 — RAPORTIMI</span>
                    <h3>Vendime me<br>më shumë pamje.</h3>
                    <p>Krahaso periudhat, shiko shpërndarjen sipas statusit dhe eksporto CSV.</p>
                    <div class="feature-chart" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><span></span></div>
                </article>
            </div>
        </section>

        <section class="closing-section" aria-labelledby="closing-title">
            <div class="closing-route" aria-hidden="true">
                <svg viewBox="0 0 1200 360" preserveAspectRatio="none">
                    <path d="M-30 290C160 280 176 85 390 120s180 172 374 117 228-164 466-112"/>
                </svg>
                <span class="closing-pin pin-one"></span><span class="closing-pin pin-two"></span>
            </div>
            <div class="closing-content" data-reveal>
                <p class="section-kicker"><span>03</span> RRUGA VAZHDON</p>
                <h2 id="closing-title">Gati për ta parë<br><span>nga brenda?</span></h2>
                <p>Hyr në platformë dhe vazhdo aty ku fillon dita jote.</p>
                <a class="intro-button intro-button-primary" href="?page=login">Hyr në sistem <span aria-hidden="true">↗</span></a>
            </div>
            <div class="closing-stamp" aria-hidden="true"><span>TC</span><i></i><small>MOVE WITH CLARITY</small></div>
        </section>
    </main>

    <footer class="intro-footer">
        <a class="intro-brand intro-brand-footer" href="#home">
            <span class="intro-brand-mark" aria-hidden="true"><img src="assets/brand-symbol.svg?v=20261110" alt=""></span>
            <span class="intro-brand-copy"><strong>TRANSPORTI</strong><span>COMPANY MANAGEMENT</span></span>
        </a>
        <span>Çdo rrugë, nën kontroll.</span>
        <a href="?page=login">Hyr në platformë <span aria-hidden="true">↗</span></a>
    </footer>
</body>
</html>
