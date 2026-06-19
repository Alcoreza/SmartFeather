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
                            A green-coded operations hub built around the pressure points affecting poultry farms today:
                            disease control, heat stress, ammonia exposure, inventory movement, and field accountability.
                        </p>
                        <div class="landing-hero-actions" aria-label="Landing page actions">
                            <a href="{{ route('login') }}">Login to system</a>
                            <span>Monitor risks, assign work, record farm actions</span>
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
                    <h2 id="landingAboutTitle">SmartFeather keeps MJBJ farm operations visible, organized, and ready to act.</h2>
                </div>
                <div class="landing-about-body">
                    <p>
                        SmartFeather brings environment readings, house activity, worker tasks, inventory movement,
                        biosecurity logs, mortality records, and reports into one operational system for MJBJ
                        Corporation.
                    </p>
                    <div class="landing-about-metrics" aria-label="SmartFeather coverage areas">
                        <div><strong>24/7</strong><span>Farm visibility</span></div>
                        <div><strong>Tasks</strong><span>Worker coordination</span></div>
                        <div><strong>Logs</strong><span>Traceable records</span></div>
                    </div>
                </div>
            </section>

            <section class="landing-specialties" aria-labelledby="landingSpecialtiesTitle">
                <div class="landing-section-copy">
                    <span class="landing-section-label">Risk response modules</span>
                    <h2 id="landingSpecialtiesTitle">SmartFeather turns poultry issues into records managers can act on.</h2>
                </div>
                <div class="landing-specialty-grid">
                    <article>
                        <span>01</span>
                        <h3>Heat & Ammonia Monitoring</h3>
                        <p>Temperature and ammonia readings help managers spot uncomfortable houses before stress affects feeding and growth.</p>
                    </article>
                    <article>
                        <span>02</span>
                        <h3>Biosecurity Task Control</h3>
                        <p>Cleaning, disinfection, inspection, and visitor-related tasks can be assigned by house and pen for traceable action.</p>
                    </article>
                    <article>
                        <span>03</span>
                        <h3>Feed & Vitamin Stock Visibility</h3>
                        <p>Inventory histories make stock movement clearer when feed costs rise or farm supply becomes unpredictable.</p>
                    </article>
                    <article>
                        <span>04</span>
                        <h3>Mortality & Population Records</h3>
                        <p>Population updates, mortality notes, and flock activity records support faster review when disease risk is suspected.</p>
                    </article>
                </div>
            </section>

            <section class="landing-services" aria-labelledby="landingServicesTitle">
                <div class="landing-services-copy">
                    <span class="landing-section-label">Operational coverage</span>
                    <h2 id="landingServicesTitle">From outbreaks to heat waves, SmartFeather is framed as farm protection software.</h2>
                    <p>
                        SmartFeather connects dashboards, sensors, tasks, inventory, biosecurity logs, and reports
                        into one farm record system for faster action during outbreaks, heat stress, or supply
                        disruption.
                    </p>
                </div>
            </section>

            <section class="landing-flow" aria-label="SmartFeather process">
                <div>
                    <span>Detect</span>
                    <strong>Read heat, air-quality, house status, and flock movement signals.</strong>
                </div>
                <div>
                    <span>Contain</span>
                    <strong>Log visitors, cleaning, disinfection, mortality, and assigned response tasks.</strong>
                </div>
                <div>
                    <span>Review</span>
                    <strong>Compare reports, histories, and inventory movement before decisions are made.</strong>
                </div>
            </section>
        </main>
    </div>
@endsection
