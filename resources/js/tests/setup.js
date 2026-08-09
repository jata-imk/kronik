import { vi } from "vitest";

globalThis.route = vi.fn((name) => ({
    current: () => name ?? "dashboard",
}));
