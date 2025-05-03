import { ref } from "vue";

const map = ref(null);

export function useMap() {
    const setMap = (instance) => {
        map.value = instance;
    };

    return {
        map,
        setMap,
    };
}
