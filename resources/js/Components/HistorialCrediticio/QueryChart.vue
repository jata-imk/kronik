<template>
  <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 transition-all duration-300 hover:shadow-md">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
          <!-- Usa tu icono SVG o componente -->
          <BarChart class="text-indigo-600 mr-2" />
          Consultas de crédito por tipo
        </h3>
        <p class="text-sm text-gray-500 mt-1">Ultimas 3 meses</p>
      </div>
      <div class="flex items-center space-x-4">
        <div class="text-right">
          <p class="text-sm text-gray-500">Total de consultas</p>
          <p class="text-xl font-bold text-gray-900">{{ totalQueries.toLocaleString() }}</p>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-500">Promedio diario</p>
          <p class="text-xl font-bold text-gray-900">{{ avgDaily }}</p>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="mb-4">
      <div class="flex items-center space-x-6 text-sm">
        <div class="flex items-center">
          <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
          <span class="text-gray-600">Experian</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
          <span class="text-gray-600">Equifax</span>
        </div>
        <div class="flex items-center">
          <div class="w-3 h-3 bg-purple-500 rounded-full mr-2"></div>
          <span class="text-gray-600">TransUnion</span>
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="relative h-64">
      <div class="flex items-end justify-between h-full space-x-1">
        <div
          v-for="(data, index) in queryData"
          :key="data.date"
          class="flex-1 flex flex-col items-center group"
        >
          <div class="relative w-full flex flex-col items-center">
            <div
              class="w-full bg-gray-100 rounded-t-sm relative overflow-hidden"
              :style="{ height: `${(data.total / maxValue) * 200}px` }"
            >
              <!-- Experian -->
              <div
                class="w-full bg-blue-500 absolute bottom-0"
                :style="{ height: `${(data.experian / data.total) * 100}%` }"
              ></div>
              <!-- Equifax -->
              <div
                class="w-full bg-green-500 absolute bottom-0"
                :style="{
                  height: `${(data.equifax / data.total) * 100}%`,
                  bottom: `${(data.experian / data.total) * 100}%`
                }"
              ></div>
              <!-- TransUnion -->
              <div
                class="w-full bg-purple-500 absolute bottom-0"
                :style="{
                  height: `${(data.transunion / data.total) * 100}%`,
                  bottom: `${((data.experian + data.equifax) / data.total) * 100}%`
                }"
              ></div>
            </div>

            <!-- Tooltip -->
            <div
              class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-900 text-white text-xs rounded py-2 px-3 whitespace-nowrap z-10"
            >
              <div class="text-center">
                <div class="font-medium">{{ formatDate(data.date) }}</div>
                <div class="mt-1 space-y-1">
                  <div>Experian: {{ data.experian }}</div>
                  <div>Equifax: {{ data.equifax }}</div>
                  <div>TransUnion: {{ data.transunion }}</div>
                  <div class="font-medium border-t border-gray-600 pt-1">
                    Total: {{ data.total }}
                  </div>
                </div>
              </div>
              <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
            </div>
          </div>

          <div class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-left">
            {{ formatDate(data.date) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="mt-6 pt-4 border-t border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button class="flex items-center px-3 py-1 text-sm text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors">
            <CalendarIcon class="mr-1" />
            Last 30 days
          </button>
          <button class="text-sm text-gray-500 hover:text-gray-700">Last 7 days</button>
          <button class="text-sm text-gray-500 hover:text-gray-700">Last 90 days</button>
        </div>
        <button class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">
          Export Data
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

// Sustituye esto por tus íconos reales o componentes
import { BarChart } from "lucide-vue-next";
import { CalendarIcon } from "lucide-vue-next";

const queryData = ref([
    {
        date: "2025-01-01",
        experian: 45,
        equifax: 38,
        transunion: 42,
        total: 125,
    },
    {
        date: "2025-01-02",
        experian: 52,
        equifax: 41,
        transunion: 39,
        total: 132,
    },
    {
        date: "2025-01-03",
        experian: 48,
        equifax: 44,
        transunion: 46,
        total: 138,
    },
    {
        date: "2025-01-04",
        experian: 41,
        equifax: 39,
        transunion: 43,
        total: 123,
    },
    {
        date: "2025-01-05",
        experian: 55,
        equifax: 47,
        transunion: 41,
        total: 143,
    },
    {
        date: "2025-01-06",
        experian: 49,
        equifax: 42,
        transunion: 45,
        total: 136,
    },
    {
        date: "2025-01-07",
        experian: 46,
        equifax: 40,
        transunion: 44,
        total: 130,
    },
    {
        date: "2025-01-08",
        experian: 53,
        equifax: 45,
        transunion: 47,
        total: 145,
    },
    {
        date: "2025-01-09",
        experian: 47,
        equifax: 43,
        transunion: 42,
        total: 132,
    },
    {
        date: "2025-01-10",
        experian: 51,
        equifax: 46,
        transunion: 48,
        total: 145,
    },
    {
        date: "2025-01-11",
        experian: 44,
        equifax: 41,
        transunion: 45,
        total: 130,
    },
    {
        date: "2025-01-12",
        experian: 49,
        equifax: 44,
        transunion: 43,
        total: 136,
    },
    {
        date: "2025-01-13",
        experian: 52,
        equifax: 47,
        transunion: 46,
        total: 145,
    },
    {
        date: "2025-01-14",
        experian: 48,
        equifax: 42,
        transunion: 44,
        total: 134,
    },
    {
        date: "2025-01-15",
        experian: 50,
        equifax: 45,
        transunion: 47,
        total: 142,
    },
]);

const maxValue = computed(() =>
    Math.max(...queryData.value.map((d) => d.total)),
);

const totalQueries = computed(() =>
    queryData.value.reduce((sum, d) => sum + d.total, 0),
);

const avgDaily = computed(() =>
    Math.round(totalQueries.value / queryData.value.length),
);

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "numeric",
    }).format(date);
}
</script>
