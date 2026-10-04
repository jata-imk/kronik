import { onMounted, onUnmounted } from "vue";

export function useScrollReveal(root) {
    let observer;

    onMounted(() => {
        if (
            !root.value ||
            window.matchMedia("(prefers-reduced-motion: reduce)").matches
        )
            return;

        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) continue;
                    entry.target.classList.remove("k-reveal-waiting");
                    entry.target.classList.add("k-reveal-visible");
                    observer.unobserve(entry.target);
                }
            },
            { threshold: 0.12, rootMargin: "0px 0px -5% 0px" },
        );

        for (const element of root.value.querySelectorAll("[data-reveal]")) {
            if (
                element.getBoundingClientRect().top >
                window.innerHeight * 0.9
            ) {
                element.classList.add("k-reveal-waiting");
                observer.observe(element);
            }
        }
    });

    onUnmounted(() => observer?.disconnect());
}
