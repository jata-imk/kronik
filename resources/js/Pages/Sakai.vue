<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

import BestSellingWidget from '@sakai-vue/components/dashboard/BestSellingWidget.vue';
import NotificationsWidget from '@sakai-vue/components/dashboard/NotificationsWidget.vue';
import RecentSalesWidget from '@sakai-vue/components/dashboard/RecentSalesWidget.vue';
import RevenueStreamWidget from '@sakai-vue/components/dashboard/RevenueStreamWidget.vue';
import StatsWidget from '@sakai-vue/components/dashboard/StatsWidget.vue';
</script>

<template>
    <AppLayout title="Sakai">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sakai
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden sm:rounded-lg">
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
                </div>
            </div>
        </div>
    </AppLayout>
</template>
