import { computed, reactive, watch } from "vue";

const storageKey = import.meta.env.VITE_JS_LOCAL_STORAGE_KEY || "layoutConfig";
const defaultConfig = {
    preset: "Aura",
    primary: "emerald",
    surface: null,
    darkTheme: false,
    menuMode: "static",
};

let storedConfig = {};

try {
    storedConfig = JSON.parse(localStorage.getItem(storageKey) || "{}");
} catch {
    storedConfig = {};
}

const savedConfig = { ...defaultConfig, ...storedConfig };

const layoutConfig = reactive(savedConfig);

document.documentElement.classList.toggle("app-dark", layoutConfig.darkTheme);

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
    expandedMenuItem: null,
    menuNavigationPath: null,
    menuStructure: null,
});

export function useLayout() {
    const toggleMenuGroup = (key) => {
        const expanded = layoutState.expandedMenuItem;
        layoutState.expandedMenuItem =
            expanded === key || expanded?.startsWith(`${key}-`)
                ? key.includes("-")
                    ? key.slice(0, key.lastIndexOf("-"))
                    : null
                : key;
    };

    const syncMenuNavigation = (path, structure, activeAncestor) => {
        if (
            path !== layoutState.menuNavigationPath ||
            structure !== layoutState.menuStructure
        ) {
            layoutState.expandedMenuItem = activeAncestor;
            layoutState.menuNavigationPath = path;
            layoutState.menuStructure = structure;
        }
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();

            return;
        }

        document.startViewTransition(() => executeDarkModeToggle());
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        document.documentElement.classList.toggle(
            "app-dark",
            layoutConfig.darkTheme,
        );
    };

    const toggleMenu = () => {
        if (window.innerWidth <= 991) {
            layoutState.staticMenuMobileActive =
                !layoutState.staticMenuMobileActive;
            layoutState.overlayMenuActive = false;
        } else if (layoutConfig.menuMode === "overlay") {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        } else {
            layoutState.staticMenuDesktopInactive =
                !layoutState.staticMenuDesktopInactive;
        }
    };
    const closeMenu = () => {
        layoutState.overlayMenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
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
        closeMenu,
        isSidebarActive,
        isDarkTheme,
        getPrimary,
        getSurface,
        toggleMenuGroup,
        syncMenuNavigation,
        toggleDarkMode,
    };
}
