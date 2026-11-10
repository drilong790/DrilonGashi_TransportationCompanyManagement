(() => {
    const root = document.documentElement;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const revealItems = document.querySelectorAll('[data-reveal]');
    const journey = document.querySelector('[data-journey]');

    if (!reducedMotion.matches && 'IntersectionObserver' in window) {
        root.classList.add('has-motion');
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.16, rootMargin: '0px 0px -7% 0px' });

        revealItems.forEach((item) => revealObserver.observe(item));
    }

    if (!journey || reducedMotion.matches) {
        return;
    }

    const path = journey.querySelector('[data-route-path]');
    const truck = journey.querySelector('[data-route-truck]');
    const progressBar = journey.querySelector('[data-journey-progress]');
    const steps = Array.from(journey.querySelectorAll('[data-step]'));
    if (!(path instanceof SVGPathElement) || !(truck instanceof SVGGElement) || !progressBar || steps.length === 0) {
        return;
    }

    root.classList.add('has-journey-motion');
    const pathLength = path.getTotalLength();
    path.style.strokeDasharray = `${pathLength}`;
    path.style.strokeDashoffset = `${pathLength}`;
    let frameRequested = false;

    const updateJourney = () => {
        const rect = journey.getBoundingClientRect();
        const scrollRange = Math.max(journey.offsetHeight - window.innerHeight, 1);
        const progress = Math.min(1, Math.max(0, -rect.top / scrollRange));
        const distance = pathLength * progress;
        const point = path.getPointAtLength(distance);
        const nextPoint = path.getPointAtLength(Math.min(distance + 1, pathLength));
        const angle = Math.atan2(nextPoint.y - point.y, nextPoint.x - point.x) * (180 / Math.PI);

        path.style.strokeDashoffset = `${pathLength - distance}`;
        truck.setAttribute('transform', `translate(${point.x} ${point.y}) rotate(${angle})`);
        progressBar.style.transform = `scaleX(${progress})`;
        journey.style.setProperty('--journey-progress', `${progress}`);

        const activeStep = Math.min(steps.length - 1, Math.floor(progress * steps.length));
        steps.forEach((step, index) => {
            const isActive = index === activeStep;
            step.classList.toggle('is-active', isActive);
            step.setAttribute('aria-current', isActive ? 'step' : 'false');
        });
        frameRequested = false;
    };

    const requestJourneyUpdate = () => {
        if (!frameRequested) {
            frameRequested = true;
            window.requestAnimationFrame(updateJourney);
        }
    };

    window.addEventListener('scroll', requestJourneyUpdate, { passive: true });
    window.addEventListener('resize', requestJourneyUpdate);
    updateJourney();
})();
