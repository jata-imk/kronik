import { ref, reactive } from "vue";

export function useActivityLogs() {
    const logs = ref([]);
    const loading = ref(false);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: 0,
        to: 0,
    });

    const filters = reactive({
        search: "",
        user_id: undefined,
        action: undefined,
        subject_type: undefined,
        date_from: "",
        date_to: "",
        page: 1,
        per_page: 20,
    });

    // In a real Laravel + Inertia.js app, this would use Inertia router
    const fetchLogs = async () => {
        loading.value = true;
        try {
            // Simulated API call - replace with actual Inertia request
            const mockData = generateMockData();
            logs.value = mockData.data;
            pagination.value = {
                current_page: mockData.current_page,
                last_page: mockData.last_page,
                per_page: mockData.per_page,
                total: mockData.total,
                from: mockData.from,
                to: mockData.to,
            };
        } catch (error) {
            console.error("Failed to fetch activity logs:", error);
        } finally {
            loading.value = false;
        }
    };

    const applyFilters = () => {
        filters.page = 1;
        fetchLogs();
    };

    const clearFilters = () => {
        for (const key of Object.keys(filters)) {
            if (key === "page" || key === "per_page") continue;
            filters[key] =
                key === "search" || key.includes("date") ? "" : undefined;
        }
        fetchLogs();
    };

    const exportLogs = async () => {
        // In real app, this would trigger a Laravel export job
        console.log("Exporting logs with filters:", filters);
    };

    // Enhanced mock data generator for demonstration
    const generateMockData = () => {
        const users = [
            { id: 1, name: "John Doe", email: "john@example.com" },
            { id: 2, name: "Jane Smith", email: "jane@example.com" },
            { id: 3, name: "Mike Johnson", email: "mike@example.com" },
            { id: 4, name: "Sarah Wilson", email: "sarah@example.com" },
            { id: 5, name: "David Brown", email: "david@example.com" },
            { id: 6, name: "Lisa Garcia", email: "lisa@example.com" },
            { id: 7, name: "Tom Anderson", email: "tom@example.com" },
            { id: 8, name: "Emma Davis", email: "emma@example.com" },
        ];

        const actions = [
            "created",
            "updated",
            "deleted",
            "login",
            "logout",
            "viewed",
            "exported",
            "imported",
        ];
        const subjectTypes = ["Project", "User", "Document", "Task", "Comment"];
        const descriptions = [
            'Created a new project "Website Redesign"',
            "Updated user profile information",
            'Deleted document "Old Proposal.pdf"',
            "User logged into the system",
            "User logged out of the system",
            "Viewed project dashboard",
            "Exported user data to CSV",
            "Imported new task list",
            "Updated project settings",
            "Created new user account",
            "Deleted expired session",
            "Viewed activity reports",
            "Updated security settings",
            "Created backup of database",
            "Restored data from backup",
        ];

        const mockLogs = Array.from({ length: 25 }, (_, index) => {
            const user = users[Math.floor(Math.random() * users.length)];
            const action = actions[Math.floor(Math.random() * actions.length)];
            const description =
                descriptions[Math.floor(Math.random() * descriptions.length)];
            const subjectType =
                Math.random() > 0.3
                    ? subjectTypes[
                          Math.floor(Math.random() * subjectTypes.length)
                      ]
                    : undefined;

            // Generate realistic timestamps (last 30 days)
            const now = new Date();
            const daysAgo = Math.floor(Math.random() * 30);
            const hoursAgo = Math.floor(Math.random() * 24);
            const minutesAgo = Math.floor(Math.random() * 60);
            const createdAt = new Date(
                now.getTime() -
                    daysAgo * 24 * 60 * 60 * 1000 -
                    hoursAgo * 60 * 60 * 1000 -
                    minutesAgo * 60 * 1000,
            );

            return {
                id: index + 1,
                user,
                action,
                description,
                subject_type: subjectType,
                subject_id: subjectType
                    ? Math.floor(Math.random() * 1000) + 1
                    : undefined,
                ip_address: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
                properties:
                    Math.random() > 0.7
                        ? {
                              browser: "Chrome 120.0",
                              os: "Windows 10",
                              device: "Desktop",
                              referrer: "https://example.com/dashboard",
                          }
                        : undefined,
                created_at: createdAt.toISOString(),
                updated_at: createdAt.toISOString(),
            };
        });

        // Sort by created_at descending (newest first)
        mockLogs.sort(
            (a, b) =>
                new Date(b.created_at).getTime() -
                new Date(a.created_at).getTime(),
        );

        return {
            data: mockLogs,
            current_page: 1,
            last_page: 5,
            per_page: 20,
            total: 87,
            from: 1,
            to: 20,
        };
    };

    return {
        logs,
        loading,
        pagination,
        filters,
        fetchLogs,
        applyFilters,
        clearFilters,
        exportLogs,
    };
}
