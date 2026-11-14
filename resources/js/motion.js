import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Flip } from 'gsap/Flip';

gsap.registerPlugin(ScrollTrigger, Flip);

/**
 * Daybridge motion system.
 *
 * The animation hooks use `data-anim` attributes so the design layer stays
 * decoupled from GSAP. If a hook is missing the animation is simply skipped.
 *
 * Logical selector  ->  Where it lives in this project
 * .sidebar-icon     ->  [data-anim="sidebar-icon"] on the desktop rail links
 * .main-content     ->  [data-anim="page"] on the Volt page root
 * .header-section   ->  [data-anim="header-section"] on the home intro block
 */
const SELECTORS = {
    sidebarNav: '[data-anim="sidebar"]',
    sidebarIcon: '[data-anim="sidebar-icon"]',
    sidebarIndicator: '[data-anim="sidebar-indicator"]',
    page: '[data-anim="page"]',
    pageFallback: 'main > [wire\\:snapshot]',
    header: '[data-anim="header-section"]',
    headerItem: '[data-anim="header-item"]',
    categories: '[data-anim="categories"]',
    category: '[data-anim="category"]',
    lead: '[data-anim="lead"]',
    leadLabel: '[data-anim="lead-label"]',
    leadTitle: '[data-anim="lead-title"]',
};

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

let context = null;
let listeners = null;
let hasPlayedIntro = false;
let isNavigating = false;
let lastInitAt = 0;

function prefersReducedMotion() {
    return motionQuery.matches;
}

function getPageRoot() {
    return document.querySelector(SELECTORS.page) ?? document.querySelector(SELECTORS.pageFallback);
}

function getSidebar() {
    return document.querySelector(SELECTORS.sidebarNav);
}

function reset() {
    listeners?.abort();
    listeners = null;

    context?.revert();
    context = null;

    // ScrollTrigger instances hold references to the old DOM after a Livewire
    // swap, so they must be released before the next page is mounted.
    ScrollTrigger.getAll().forEach((trigger) => trigger.kill());
}

/**
 * Master intro timeline.
 *
 * First load: the rail cascades from the left and the page eases forward from
 * 0.96. After a Livewire navigation the page enters from 1.02 so the swap
 * feels native instead of replaying the full page-load choreography.
 */
function buildIntro(nav, page) {
    const isFirstLoad = !hasPlayedIntro;
    hasPlayedIntro = true;

    const icons = nav ? gsap.utils.toArray(SELECTORS.sidebarIcon, nav) : [];
    const header = page?.querySelector(SELECTORS.header);
    const headerItems = header ? gsap.utils.toArray(SELECTORS.headerItem, header) : [];
    const categories = page?.querySelector(SELECTORS.categories);
    const categoryItems = categories ? gsap.utils.toArray(SELECTORS.category, categories) : [];
    const lead = page?.querySelector(SELECTORS.lead);
    const leadLabel = page?.querySelector(SELECTORS.leadLabel);

    const timeline = gsap.timeline({
        defaults: { ease: 'power4.out' },
        onComplete: () => ScrollTrigger.refresh(),
    });

    if (isFirstLoad && icons.length > 0) {
        timeline.from(
            icons,
            {
                x: -50,
                opacity: 0,
                duration: 0.6,
                stagger: 0.05,
                clearProps: 'transform,opacity',
            },
            0,
        );
    }

    if (page) {
        timeline.from(
            page,
            {
                scale: isFirstLoad ? 0.96 : 1.02,
                opacity: 0,
                duration: 0.7,
                clearProps: 'transform,opacity',
            },
            0,
        );
    }

    if (headerItems.length > 0) {
        timeline.from(
            headerItems,
            {
                y: 40,
                opacity: 0,
                duration: 0.7,
                stagger: 0.08,
                clearProps: 'transform,opacity',
            },
            0.15,
        );
    }

    if (categoryItems.length > 0) {
        timeline.from(
            categoryItems,
            {
                y: 20,
                opacity: 0,
                duration: 0.5,
                stagger: 0.04,
                clearProps: 'transform,opacity',
            },
            0.35,
        );
    }

    if (leadLabel) {
        timeline.from(
            leadLabel,
            {
                opacity: 0,
                duration: 0.45,
                clearProps: 'opacity',
            },
            0.35,
        );
    }

    if (lead) {
        timeline.from(
            lead,
            {
                y: 50,
                opacity: 0,
                duration: 0.7,
                clearProps: 'transform,opacity',
            },
            0.4,
        );
    }

    return timeline;
}

/**
 * Scroll choreography for the home edition.
 *
 * The header shrinks and fades under a scrubbed ScrollTrigger. If the design
 * ever wants a real sticky header, add `pin: true, pinSpacing: false` to the
 * header trigger and give the pinned block a solid `bg-canvas` background.
 */
function setupScroll(page) {
    if (!page) {
        return;
    }

    const header = page.querySelector(SELECTORS.header);
    const lead = page.querySelector(SELECTORS.lead);
    const leadTitle = page.querySelector(SELECTORS.leadTitle);

    if (header) {
        gsap.to(header, {
            scale: 0.94,
            y: -24,
            opacity: 0.35,
            transformOrigin: 'top center',
            ease: 'none',
            scrollTrigger: {
                trigger: header,
                start: 'top top+=16',
                end: '+=360',
                scrub: 1,
                invalidateOnRefresh: true,
            },
        });
    }

    if (lead) {
        gsap.to(lead, {
            yPercent: -15,
            ease: 'none',
            scrollTrigger: {
                trigger: lead,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true,
                invalidateOnRefresh: true,
            },
        });
    }

    if (leadTitle) {
        // Mask reveal: the title rises out of its overflow-hidden wrapper while
        // the lead block drifts in the opposite direction.
        gsap.fromTo(
            leadTitle,
            { yPercent: 110 },
            {
                yPercent: 0,
                ease: 'none',
                scrollTrigger: {
                    trigger: leadTitle,
                    start: 'top 88%',
                    end: 'top 45%',
                    scrub: true,
                    invalidateOnRefresh: true,
                },
            },
        );
    }
}

function setIndicatorPosition(indicator, nav, link) {
    const navRect = nav.getBoundingClientRect();
    const linkRect = link.getBoundingClientRect();

    gsap.set(indicator, {
        top: linkRect.top - navRect.top + nav.scrollTop,
    });
}

const NAVIGATION_TIMEOUT_MS = 4000;

/**
 * La salida deja la página en opacity 0 antes de que Livewire cambie el DOM.
 * Si la navegación no termina (red lenta, error del servidor, sesión caducada),
 * la página quedaba en blanco y todos los clics siguientes se ignoraban
 * porque isNavigating seguía en true. Tras el límite se hace una carga normal.
 */
function armNavigationWatchdog(href, page) {
    let settled = false;

    document.addEventListener('livewire:navigated', () => { settled = true; }, { once: true });

    window.setTimeout(() => {
        if (settled) {
            return;
        }

        isNavigating = false;

        if (page?.isConnected) {
            gsap.set(page, { clearProps: 'all' });
        }

        window.location.assign(href);
    }, NAVIGATION_TIMEOUT_MS);
}

/**
 * Coloca el indicador detrás del ítem activo al cargar la página. Antes solo
 * se posicionaba al hacer clic, así que quedaba en top: 0 (sobre «Inicio")
 * aunque la sección activa fuera otra. Sin ítem activo, se oculta.
 */
function placeIndicatorOnActive(indicator, nav) {
    const active = nav.querySelector('[aria-current="page"]');

    if (!active) {
        gsap.set(indicator, { opacity: 0 });
        return;
    }

    setIndicatorPosition(indicator, nav, active);
    gsap.set(indicator, { opacity: 1 });
}

/**
 * Sidebar state changes.
 *
 * A persistent indicator is flipped between rail items with Flip.from(). The
 * outgoing page runs a fast lateral/blur exit, then Livewire performs the
 * navigation and the intro timeline brings the new page in from 1.02.
 */
function setupSidebar(nav, signal) {
    if (!nav) {
        return;
    }

    const links = gsap.utils.toArray(SELECTORS.sidebarIcon, nav);

    if (links.length === 0) {
        return;
    }

    let indicator = nav.querySelector(SELECTORS.sidebarIndicator);

    if (!indicator) {
        indicator = document.createElement('span');
        indicator.setAttribute('aria-hidden', 'true');
        indicator.dataset.anim = 'sidebar-indicator';
        indicator.className = 'pointer-events-none absolute left-0 right-0 top-0 z-0 mx-auto h-11 w-11 rounded-md bg-brand opacity-0';
        nav.prepend(indicator);
    }

    placeIndicatorOnActive(indicator, nav);

    links.forEach((link) => {
        link.addEventListener(
            'click',
            (event) => {
                if (
                    isNavigating
                    || event.defaultPrevented
                    || event.button !== 0
                    || event.metaKey
                    || event.ctrlKey
                    || event.shiftKey
                    || event.altKey
                    || link.hasAttribute('download')
                    || (link.target && link.target !== '_self')
                ) {
                    return;
                }

                const href = link.getAttribute('href');

                if (!href) {
                    return;
                }

                const destination = new URL(href, window.location.href);

                if (destination.origin !== window.location.origin) {
                    return;
                }

                const current = new URL(window.location.href);

                if (destination.pathname === current.pathname && destination.search === current.search) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                isNavigating = true;

                const active = nav.querySelector('[aria-current="page"]');
                const fromLink = active ?? link;

                setIndicatorPosition(indicator, nav, fromLink);
                gsap.set(indicator, { opacity: 1 });

                const flipState = Flip.getState(indicator);

                setIndicatorPosition(indicator, nav, link);

                Flip.from(flipState, {
                    duration: 0.28,
                    ease: 'power3.out',
                });

                const page = getPageRoot();

                if (page) {
                    gsap.to(page, {
                        x: 14,
                        scale: 0.98,
                        opacity: 0,
                        filter: 'blur(4px)',
                        duration: 0.2,
                        ease: 'power2.in',
                    });
                }

                window.setTimeout(() => {
                    try {
                        if (window.Livewire?.navigate) {
                            window.Livewire.navigate(href);
                            armNavigationWatchdog(href, page);
                        } else {
                            window.location.assign(href);
                        }
                    } catch {
                        if (page) {
                            gsap.set(page, { clearProps: 'all' });
                        }

                        window.location.assign(href);
                    }
                }, 220);
            },
            { signal },
        );
    });
}

function init() {
    reset();

    if (prefersReducedMotion()) {
        isNavigating = false;
        return;
    }

    const nav = getSidebar();
    const page = getPageRoot();

    if (!nav && !page) {
        return;
    }

    listeners = new AbortController();

    context = gsap.context(() => {
        buildIntro(nav, page);
        setupScroll(page);
        setupSidebar(nav, listeners.signal);
    });

    ScrollTrigger.refresh();

    document.fonts?.ready.then(() => ScrollTrigger.refresh());
}

function safeInit() {
    const now = window.performance.now();

    // `livewire:navigated` can fire alongside the initial DOMContentLoaded
    // bootstrap; avoid mounting the choreography twice.
    if (now - lastInitAt < 150) {
        return;
    }

    lastInitAt = now;
    isNavigating = false;
    init();
}

export function initMotion() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', safeInit, { once: true });
    } else {
        safeInit();
    }

    document.addEventListener('livewire:navigated', safeInit);

    document.addEventListener('livewire:navigating', () => {
        // Release scrubbed triggers before the old DOM is replaced.
        ScrollTrigger.getAll().forEach((trigger) => trigger.kill());
    });

    window.addEventListener('load', () => ScrollTrigger.refresh(), { once: true });

    motionQuery.addEventListener?.('change', safeInit);
}

export { gsap, ScrollTrigger, Flip };
