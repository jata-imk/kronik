import { onBeforeUnmount, readonly, ref } from "vue";

/**
 * Ejecuta una consulta asíncrona sin solapamientos hasta que devuelva `false`,
 * venza el tiempo límite o se alcance el máximo de errores consecutivos.
 */
export function useAsyncPolling(
    callback,
    {
        intervalMs = 2000,
        timeoutMs = 90000,
        maxConsecutiveErrors = 3,
        onTimeout = () => {},
        onError = () => {},
    } = {},
) {
    const isPolling = ref(false);
    const timedOut = ref(false);
    const lastError = ref(null);
    let pollTimer = null;
    let timeoutTimer = null;
    let runId = 0;
    let consecutiveErrors = 0;

    const clearTimers = () => {
        if (pollTimer !== null) clearTimeout(pollTimer);
        if (timeoutTimer !== null) clearTimeout(timeoutTimer);
        pollTimer = null;
        timeoutTimer = null;
    };

    const stop = () => {
        runId += 1;
        clearTimers();
        isPolling.value = false;
    };

    const reset = () => {
        stop();
        timedOut.value = false;
        lastError.value = null;
    };

    const schedule = (currentRunId) => {
        if (!isPolling.value || currentRunId !== runId) return;
        pollTimer = setTimeout(() => execute(currentRunId), intervalMs);
    };

    const execute = async (currentRunId) => {
        if (!isPolling.value || currentRunId !== runId) return;
        pollTimer = null;

        try {
            const shouldContinue = await callback();
            if (!isPolling.value || currentRunId !== runId) return;

            consecutiveErrors = 0;
            lastError.value = null;
            if (shouldContinue === false) {
                stop();
                return;
            }
        } catch (error) {
            if (!isPolling.value || currentRunId !== runId) return;

            consecutiveErrors += 1;
            lastError.value = error;
            if (consecutiveErrors >= maxConsecutiveErrors) {
                stop();
                onError(error);
                return;
            }
        }

        schedule(currentRunId);
    };

    const start = ({ immediate = true, restart = false } = {}) => {
        if (isPolling.value && !restart) return;
        if (isPolling.value) stop();

        runId += 1;
        const currentRunId = runId;
        consecutiveErrors = 0;
        lastError.value = null;
        timedOut.value = false;
        isPolling.value = true;
        timeoutTimer = setTimeout(() => {
            if (!isPolling.value || currentRunId !== runId) return;
            timedOut.value = true;
            stop();
            onTimeout();
        }, timeoutMs);

        if (immediate) execute(currentRunId);
        else schedule(currentRunId);
    };

    onBeforeUnmount(stop);

    return {
        isPolling: readonly(isPolling),
        timedOut: readonly(timedOut),
        lastError: readonly(lastError),
        start,
        stop,
        reset,
    };
}
