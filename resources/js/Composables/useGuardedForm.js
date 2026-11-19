import { useForm } from "@inertiajs/vue3";
import { onScopeDispose } from "vue";
import { registerUnsavedChanges } from "@/Utils/unsavedChanges";

// Tracks the existing Inertia form; it never writes drafts or submits data.
export function useGuardedForm(...arguments_) {
    const form = useForm(...arguments_);
    const unregister = registerUnsavedChanges(() => form.isDirty);
    onScopeDispose(unregister);
    return form;
}
