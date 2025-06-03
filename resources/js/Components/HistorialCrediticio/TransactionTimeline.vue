<template>
  <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-6">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Cronología del historial crediticio</h2>

    <div class="flow-root">
      <div v-for="group in timelineGroups" :key="group.label" class="mb-8 last:mb-0">
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3 sticky top-0 py-2">
          {{ group.label }}
        </h3>

        <ul class="-mb-8">
          <li v-for="(transaction, index) in group.items" :key="transaction.id">
            <div class="relative pb-8">
              <span
                v-if="index !== group.items.length - 1"
                class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"
                aria-hidden="true"
              ></span>

              <div class="relative flex items-start space-x-3">
                <div class="relative">
                  <div
                    class="h-10 w-10 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-900"
                    :class="getTimelineDotClass(transaction.impact)"
                  >
                    <component :is="getIconForType(transaction.type, transaction.impact)" :size="18" />
                  </div>
                </div>

                <div class="min-w-0 flex-1 py-1.5">
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ transaction.description }}</p>
                      <p v-if="transaction.accountName" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ transaction.accountName }}
                      </p>
                    </div>
                    <div class="text-right">
                      <p
                        v-if="transaction.type !== 'credit_change'"
                        class="text-sm font-medium"
                        :class="{
                          'text-green-600': transaction.impact === 'positive',
                          'text-red-600': transaction.impact === 'negative',
                          'text-gray-600': transaction.impact === 'neutral'
                        }"
                      >
                        {{ formatCurrency(transaction.amount) }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ formatDate(transaction.date) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <div v-if="timelineGroups.length === 0" class="text-center py-12">
        <p class="text-gray-500 dark:text-gray-400">No se encontraron transacciones.</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
    Clock,
    AlertCircle,
    CheckCircle,
    CreditCard,
    Banknote,
} from "lucide-vue-next";

interface Transaction {
    id: string;
    date: string;
    description: string;
    amount: number;
    type: "payment" | "purchase" | "fee" | "credit_change" | "inquiry";
    accountName?: string;
    impact: "positive" | "negative" | "neutral";
}

const props = defineProps<{
    transactions: Transaction[];
}>();

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("es-MX", {
        month: "short",
        day: "numeric",
        year: "numeric",
    }).format(date);
};

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "USD",
        signDisplay: "always",
    }).format(amount);
};

const getIconForType = (type: string, impact: string) => {
    switch (type) {
        case "payment":
            return Banknote;
        case "purchase":
            return CreditCard;
        case "fee":
            return AlertCircle;
        case "credit_change":
            return impact === "positive" ? CheckCircle : AlertCircle;
        case "inquiry":
            return Clock;
        default:
            return Clock;
    }
};

const getTimelineDotClass = (impact: string) => {
    switch (impact) {
        case "positive":
            return "bg-green-500 dark:bg-green-400";
        case "negative":
            return "bg-red-500 dark:bg-red-400";
        default:
            return "bg-blue-500 dark:bg-blue-400";
    }
};

import { computed } from "vue";

const timelineGroups = computed(() => {
    const groups: Record<string, { label: string; items: Transaction[] }> = {};

    for (const transaction of props.transactions) {
        const date = new Date(transaction.date);
        const key = `${date.getMonth()}-${date.getFullYear()}`;
        if (!groups[key]) {
            groups[key] = {
                label: new Intl.DateTimeFormat("es-MX", {
                    month: "long",
                    year: "numeric",
                }).format(date),
                items: [],
            };
        }
        groups[key].items.push(transaction);
    }

    return Object.values(groups).sort((a, b) => {
        return (
            new Date(b.items[0].date).getTime() -
            new Date(a.items[0].date).getTime()
        );
    });
});
</script>
