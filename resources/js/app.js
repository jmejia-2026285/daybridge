import { Flip, ScrollTrigger, gsap, initMotion } from './motion';

window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
window.Flip = Flip;

initMotion();

if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
