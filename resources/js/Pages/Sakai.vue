<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";

import BestSellingWidget from "@sakai-vue/components/dashboard/BestSellingWidget.vue";
import NotificationsWidget from "@sakai-vue/components/dashboard/NotificationsWidget.vue";
import RecentSalesWidget from "@sakai-vue/components/dashboard/RecentSalesWidget.vue";
import RevenueStreamWidget from "@sakai-vue/components/dashboard/RevenueStreamWidget.vue";
import StatsWidget from "@sakai-vue/components/dashboard/StatsWidget.vue";
</script>

<template>
    <AppLayout title="Sakai">
        <div class="grid grid-cols-12 gap-8">
            <StatsWidget />

            <div class="col-span-12 xl:col-span-6">
                <RecentSalesWidget />
                <BestSellingWidget />
            </div>
            <div class="col-span-12 xl:col-span-6">
                <RevenueStreamWidget />
                <NotificationsWidget />
            </div>
        </div>
    </AppLayout>
</template>
