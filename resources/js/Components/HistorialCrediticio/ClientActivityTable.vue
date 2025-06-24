<script setup>
import { ref } from "vue";
import {
    Users as UsersIcon,
    Eye as EyeIcon,
    MoreHorizontal as MoreHorizontalIcon,
} from "lucide-vue-next";

const props = defineProps({
    sicQueriesPaginated: {
        type: Array,
        default: () => [],
    },
});

const sicQueriesPaginated = ref(props.sicQueriesPaginated);
const sicQueriesData = ref(props.sicQueriesPaginated.data);

const clientFullName = (client) => {
    return `${client.primer_nombre} ${client.segundo_nombre} ${client.apellido_paterno} ${client.apellido_materno}`;
};
const clientCreditScore = (sicQuery) => {
    const data = JSON.parse(sicQuery.response_data);
    return data?.scores?.[0].valor || data?.score?.valor || 0;
};

const getStatusBadge = (status) => {
    switch (status) {
        case "premium":
            return "bg-purple-100 text-purple-800";
        case "active":
            return "bg-green-100 text-green-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

const getCreditScoreColor = (score) => {
    if (score >= 800) return "text-emerald-600 bg-emerald-50";
    if (score >= 740) return "text-green-600 bg-green-50";
    if (score >= 670) return "text-yellow-600 bg-yellow-50";
    if (score >= 580) return "text-orange-600 bg-orange-50";
    return "text-red-600 bg-red-50";
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const formatJoinDate = (dateString) => {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    }).format(date);
};
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 transition-all duration-300 hover:shadow-md">
    <div class="p-6 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <UsersIcon size="20" class="text-indigo-600 mr-2" />
            Recent Client Activity
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            Latest user interactions and status updates
          </p>
        </div>
        <button class="flex items-center px-3 py-2 text-sm text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors">
          <EyeIcon size="14" class="mr-1" />
          View All
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. de consultas</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ultima consulta</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente desde</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="sicQuery in sicQueriesData" :key="sicQuery.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              <div>
                <div class="text-sm font-medium text-gray-900">{{ clientFullName(sicQuery.cliente) }}</div>
                <div class="text-sm text-gray-500">{{ sicQuery.cliente?.email }}</div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getCreditScoreColor(clientCreditScore(sicQuery))}`">
                {{ clientCreditScore(sicQuery) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Hoy</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatJoinDate(sicQuery.cliente.created_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadge('active')}`">
                Activo
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
      <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">
          Mostrando {{ sicQueriesData.length }} de {{ sicQueriesPaginated.total }} registros
        </p>
        <div class="flex items-center space-x-2">
          <button class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700 disabled:opacity-50" disabled>
            Previous
          </button>
          <button class="px-3 py-1 text-sm text-indigo-600 hover:text-indigo-500">
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
