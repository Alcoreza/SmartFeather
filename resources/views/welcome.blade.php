@extends('layouts.app')

@section('title', 'SmartFeather for MJBJ Corporation')

@push('styles')
    @vite('resources/css/landing.css')
@endpush

@section('content')
    <div class="landing-page">
        <header class="landing-navbar" aria-label="SmartFeather navigation">
            <a class="landing-brand" href="{{ route('landing') }}" aria-label="SmartFeather for MJBJ Corporation home">
                <img class="landing-brand-logo" src="{{ asset('images/AppLogoSmartFeather-favicon.png') }}" alt="" aria-hidden="true">
                <span class="landing-brand-copy">
                    <span class="landing-brand-title">SmartFeather</span>
                    <span class="landing-brand-subtitle">for MJBJ Corporation</span>
                </span>
            </a>

            <a class="landing-login-link" href="{{ route('login') }}">Login</a>
        </header>

        <main class="landing-main">
            <section class="landing-hero" aria-labelledby="landingHeroTitle">
                <div class="landing-hero-media">
                    <div class="landing-hero-overlay"></div>
                    <div class="landing-hero-copy">
                        <p class="landing-kicker">Poultry risk intelligence and farm operations</p>
                        <h1 id="landingHeroTitle">SmartFeather for MJBJ Corporation</h1>
                        <p class="landing-hero-text">
                            A focused farm operations platform for monitoring house conditions, coordinating work,
                            and keeping poultry records ready for review.
                        </p>
                        <div class="landing-hero-actions" aria-label="Landing page actions">
                            <a href="{{ route('login') }}">Login to system</a>
                            <span>Monitor conditions, assign tasks, review records</span>
                        </div>
                    </div>

                    <div class="landing-scroll-cue" aria-hidden="true">
                        <span></span>
                    </div>
                </div>
            </section>

            <section class="landing-support" aria-label="SmartFeather platform highlights">
                <article class="landing-risk-card landing-risk-biosecurity">
                    <span class="landing-support-number">HPAI</span>
                    <span class="landing-risk-source">Disease control</span>
                    <h2>Biosecurity under pressure</h2>
                    <p>Avian flu outbreaks continue to pressure poultry farms with movement limits, mortality risk, culling, and stricter visitor controls.</p>
                </article>
                <article class="landing-risk-card landing-risk-heat">
                    <span class="landing-support-number">35C</span>
                    <span class="landing-risk-source">Climate stress</span>
                    <h2>Heat stress needs visibility</h2>
                    <p>High house temperatures can reduce feed intake, weaken birds, and lower production, so SmartFeather keeps heat trends visible.</p>
                </article>
                <article class="landing-risk-card landing-risk-supply">
                    <span class="landing-support-number">Cost</span>
                    <span class="landing-risk-source">Market pressure</span>
                    <h2>Supply and price volatility</h2>
                    <p>Disease losses, feed prices, transport issues, and stock shortages can quickly affect egg and poultry supply planning.</p>
                </article>
            </section>

            <section class="landing-about" aria-labelledby="landingAboutTitle">
                <div class="landing-section-copy">
                    <span class="landing-section-label">Why this matters</span>
                    <h2 id="landingAboutTitle">SmartFeather gives MJBJ a clearer view of daily farm work.</h2>
                </div>
                <div class="landing-about-body">
                    <p>
                        Managers can see house conditions, responsibilities, and records in one place instead of
                        piecing together separate updates.
                    </p>
                    <div class="landing-about-metrics" aria-label="SmartFeather coverage areas">
                        <div><strong>Live</strong><span>Farm status</span></div>
                        <div><strong>Tasks</strong><span>Assigned work</span></div>
                        <div><strong>Logs</strong><span>Reviewable records</span></div>
                    </div>
                </div>
            </section>

            <section class="landing-specialties" aria-labelledby="landingSpecialtiesTitle">
                <div class="landing-section-copy">
                    <span class="landing-section-label">Risk response modules</span>
                    <h2 id="landingSpecialtiesTitle">Core modules cover the signals managers need most.</h2>
                </div>
                <div class="landing-specialty-grid">
                    <article>
                        <div class="landing-module-top">
                            <span class="landing-module-number">01</span>
                            <span class="landing-module-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M14 14.76V5a4 4 0 0 0-8 0v9.76a6 6 0 1 0 8 0Z"></path>
                                    <path d="M10 5v12"></path>
                                    <path d="M10 17h.01"></path>
                                </svg>
                            </span>
                        </div>
                        <h3>Heat & Ammonia Monitoring</h3>
                        <p>Track house readings so heat and air-quality concerns are easier to spot.</p>
                    </article>
                    <article>
                        <div class="landing-module-top">
                            <span class="landing-module-number">02</span>
                            <span class="landing-module-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"></path>
                                    <path d="m9 12 2 2 4-4"></path>
                                </svg>
                            </span>
                        </div>
                        <h3>Biosecurity Task Control</h3>
                        <p>Assign cleaning, inspection, and visitor-related work by house or pen.</p>
                    </article>
                    <article>
                        <div class="landing-module-top">
                            <span class="landing-module-number">03</span>
                            <span class="landing-module-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                                    <path d="m3.3 7 8.7 5 8.7-5"></path>
                                    <path d="M12 22V12"></path>
                                </svg>
                            </span>
                        </div>
                        <h3>Feed & Vitamin Stock Visibility</h3>
                        <p>Keep stock movement visible when farm supplies need closer attention.</p>
                    </article>
                    <article>
                        <div class="landing-module-top">
                            <span class="landing-module-number">04</span>
                            <span class="landing-module-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path>
                                    <path d="M14 2v6h6"></path>
                                    <path d="M8 13h2"></path>
                                    <path d="M13 13h3"></path>
                                    <path d="M8 17h8"></path>
                                </svg>
                            </span>
                        </div>
                        <h3>Mortality & Population Records</h3>
                        <p>Record flock changes for faster review during health or production checks.</p>
                    </article>
                </div>
            </section>

            <section class="landing-services" aria-labelledby="landingServicesTitle">
                <div class="landing-services-copy">
                    <span class="landing-section-label">Operational coverage</span>
                    <h2 id="landingServicesTitle">Built around the everyday flow of poultry management.</h2>
                    <p>
                        SmartFeather helps managers move from farm readings to assigned work and management review
                        without scattering information across separate updates.
                    </p>
                </div>
            </section>

            <section class="landing-flow" aria-label="SmartFeather process">
                <div>
                    <span>Detect</span>
                    <strong>Surface house conditions that need attention.</strong>
                </div>
                <div>
                    <span>Contain</span>
                    <strong>Assign the right farm action to the right area.</strong>
                </div>
                <div>
                    <span>Review</span>
                    <strong>Use records to support the next decision.</strong>
                </div>
            </section>
        </main>
    </div>
@endsection
