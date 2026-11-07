/**
 * Hero carousel.
 *
 * The horizontal movement is the browser's own scrolling, snapped to slide
 * boundaries by CSS. That is a deliberate choice over a hand-rolled drag: touch
 * swipe, trackpad, momentum, per-pixel dragging, rubber-banding at the ends and
 * mid-gesture direction changes all then behave exactly the way the platform
 * behaves everywhere else, on every device, for free — and none of it can be
 * broken by a pointer event we forgot to handle. The previous version moved on
 * a bare `setInterval` with nothing but two dots, so there was no way to go
 * back, no way to swipe, and no way to stop it.
 *
 * What is left for JavaScript is the part scroll-snap cannot express: autoplay,
 * the buttons, and telling assistive tech which slide is showing.
 *
 * Autoplay is suspended whenever nobody could be reading it — pointer over the
 * section, focus inside it, tab in the background, section scrolled out of
 * view, or a swipe in flight — and never starts at all under
 * `prefers-reduced-motion`, where the carousel becomes a manual one.
 *
 * @param {number} count     number of slides
 * @param {number} interval  ms each slide holds before advancing
 */
export default (count = 1, interval = 6500) => ({
    i: 0,
    n: Math.max(count, 1),
    interval,

    /* Autoplay runs only while every one of these is false. */
    paused: false, // the explicit play/pause button
    hovered: false,
    focused: false,
    tabHidden: false,
    offscreen: true, // until the observer says otherwise
    reduced: false,
    settling: false, // a scroll, ours or the visitor's, is in flight

    frame: null,
    stamp: 0,
    elapsed: 0,
    idle: null,
    teardown: [],

    init() {
        const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
        this.reduced = motion.matches;
        this.listen(motion, 'change', (e) => {
            this.reduced = e.matches;
            this.sync();
        });

        this.listen(document, 'visibilitychange', () => {
            this.tabHidden = document.hidden;
            this.sync();
        });

        // A carousel scrolled past is a carousel nobody is watching.
        if (window.IntersectionObserver) {
            const observer = new IntersectionObserver(
                ([entry]) => {
                    this.offscreen = !entry.isIntersecting;
                    this.sync();
                },
                { threshold: 0.4 },
            );
            observer.observe(this.$el);
            this.teardown.push(() => observer.disconnect());
        } else {
            this.offscreen = false;
        }

        // Resizing changes what `scrollLeft` means; put the live slide back.
        this.listen(
            window,
            'resize',
            () => this.goto(this.i, false),
            { passive: true },
        );

        this.sync();
    },

    destroy() {
        this.halt();
        clearTimeout(this.idle);
        this.teardown.forEach((off) => off());
        this.teardown = [];
    },

    /** addEventListener that remembers how to undo itself. */
    listen(target, event, handler, options) {
        target.addEventListener(event, handler, options);
        this.teardown.push(() => target.removeEventListener(event, handler, options));
    },

    /* -- autoplay ---------------------------------------------------------- */

    get running() {
        return (
            this.n > 1 &&
            !this.paused &&
            !this.reduced &&
            !this.hovered &&
            !this.focused &&
            !this.tabHidden &&
            !this.offscreen &&
            !this.settling
        );
    },

    sync() {
        this.running ? this.start() : this.halt();
    },

    start() {
        if (this.frame !== null) return;

        this.stamp = performance.now();

        const tick = (now) => {
            this.elapsed += now - this.stamp;
            this.stamp = now;

            // Written straight to the DOM rather than bound: this changes every
            // frame, and the progress bar is the only thing that cares.
            this.$el.style.setProperty('--dwell', Math.min(this.elapsed / this.interval, 1));

            if (this.elapsed >= this.interval) {
                this.next();
                return; // next() halts us; the scroll-idle timer restarts it
            }

            this.frame = requestAnimationFrame(tick);
        };

        this.frame = requestAnimationFrame(tick);
    },

    halt() {
        if (this.frame === null) return;
        cancelAnimationFrame(this.frame);
        this.frame = null;
    },

    /* -- navigation -------------------------------------------------------- */

    /**
     * Scroll to a slide. The index wraps, so the arrows and autoplay loop
     * rather than dead-ending on the first and last slide.
     */
    goto(index, smooth = true) {
        const rail = this.$refs.rail;
        if (!rail) return;

        const target = ((index % this.n) + this.n) % this.n;

        this.i = target;
        this.restart();

        rail.scrollTo({
            left: target * rail.clientWidth,
            behavior: smooth && !this.reduced ? 'smooth' : 'auto',
        });
    },

    prev() {
        this.goto(this.i - 1);
    },

    next() {
        this.goto(this.i + 1);
    },

    toggle() {
        this.paused = !this.paused;
        this.restart();
        this.sync();
    },

    /** A manual move earns the full dwell time, not whatever was left of it. */
    restart() {
        this.elapsed = 0;
        this.$el.style.setProperty('--dwell', 0);
    },

    /**
     * The single source of truth for which slide is live: where the rail
     * actually is. A swipe, a keypress, an arrow and autoplay all end up here,
     * so the dots can never disagree with the image on screen.
     */
    onScroll() {
        const rail = this.$refs.rail;
        if (!rail || !rail.clientWidth) return;

        const index = Math.round(rail.scrollLeft / rail.clientWidth);
        if (index !== this.i && index >= 0 && index < this.n) {
            this.i = index;
        }

        // Any movement restarts the clock, and holds autoplay off until the
        // rail has been still for a moment — `scrollend` is not everywhere yet.
        this.restart();
        this.settling = true;
        this.halt();

        clearTimeout(this.idle);
        this.idle = setTimeout(() => {
            this.settling = false;
            this.sync();
        }, 240);
    },
});
