import { computed, reactive, watch } from "vue";

const storageKey = import.meta.env.VITE_JS_LOCAL_STORAGE_KEY || "layoutConfig";
const defaultConfig = {
    preset: "Aura",
    primary: "emerald",
    surface: null,
    darkTheme: false,
    menuMode: "static",
};

const savedConfig =
    JSON.parse(localStorage.getItem(storageKey)) || defaultConfig;

const layoutConfig = reactive(savedConfig);

watch(
    () => layoutConfig,
    (newVal) => {
        localStorage.setItem(storageKey, JSON.stringify(newVal));
    },
    { deep: true },
);

const layoutState = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activeMenuItem: route().current() || null,
});

export function useLayout() {
    const setActiveMenuItem = (item) => {
        layoutState.activeMenuItem = item.value || item;
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle(event));
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle("app-dark");
    };

    const toggleMenu = () => {
        if (layoutConfig.menuMode === "overlay") {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive =
                !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive =
                !layoutState.staticMenuMobileActive;
        }
    };

    const isSidebarActive = computed(
        () =>
            layoutState.overlayMenuActive || layoutState.staticMenuMobileActive,
    );

    const isDarkTheme = computed(() => layoutConfig.darkTheme);

    const getPrimary = computed(() => layoutConfig.primary);

    const getSurface = computed(() => layoutConfig.surface);

    return {
        layoutConfig,
        layoutState,
        toggleMenu,
        isSidebarActive,
        isDarkTheme,
        getPrimary,
        getSurface,
        setActiveMenuItem,
        toggleDarkMode,
    };
}
