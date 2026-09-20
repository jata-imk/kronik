const guarded = Symbol("kronik-select-overlay-guard");

// PrimeVue 4.3.1 listens to orientation changes even when its overlay does not
// exist. Guard that lifecycle boundary, including Select inside Paginator.
// Remove after a dependency upgrade proves the orientation E2E passes unaided.
export function guardPrimeVueSelectOverlay(select) {
    const align = select.methods.alignOverlay;
    if (align[guarded]) return;
    const safeAlign = function (...args) {
        if (!this.overlay || !this.$el?.isConnected) return;
        return align.apply(this, args);
    };
    safeAlign[guarded] = true;
    select.methods.alignOverlay = safeAlign;
}
