// Selection comes only from the authorized navigation tree and the current route.
export function findMenuSelection(items, matchesRoute, parentKey = null) {
    for (const [index, item] of items.entries()) {
        if (item.visible === false) continue;
        const key =
            parentKey === null ? String(index) : `${parentKey}-${index}`;
        const descendant = findMenuSelection(
            item.items ?? [],
            matchesRoute,
            key,
        );
        if (descendant) {
            return { ...descendant, ancestors: [key, ...descendant.ancestors] };
        }
        if (
            (item.to && matchesRoute(item.to)) ||
            item.activeRoutes?.some(matchesRoute)
        ) {
            return { item, key, ancestors: [] };
        }
    }
    return null;
}
