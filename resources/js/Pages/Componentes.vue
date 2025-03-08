<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Rating from 'primevue/rating';
import Tag from 'primevue/tag';

import { ProductService } from '@sakai-vue/service/ProductService';

onMounted(() => {
    ProductService.getProductsMini().then((data) => (products.value = data));
});

const products = ref();
const formatCurrency = (value) => {
    return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
};
const getSeverity = (product) => {
    switch (product.inventoryStatus) {
        case 'INSTOCK':
            return 'success';

        case 'LOWSTOCK':
            return 'warn';

        case 'OUTOFSTOCK':
            return 'danger';

        default:
            return null;
    }
};

</script>

<template>
    <AppLayout title="Componentes">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Listado de todos los componentes
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="overflow-hidden sm:rounded-lg">
                    <div class="grid grid-cols-12 gap-8">
                        <div class="card col-span-12">
                            <DataTable :value="products" tableStyle="min-width: 50rem">
                                <template #header>
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="text-xl font-bold">Products</span>
                                        <Button icon="pi pi-refresh" rounded raised />
                                    </div>
                                </template>
                                <Column field="name" header="Name"></Column>
                                <Column header="Image">
                                    <template #body="slotProps">
                                        <img :src="`https://primefaces.org/cdn/primevue/images/product/${slotProps.data.image}`" :alt="slotProps.data.image" class="w-24 rounded" />
                                    </template>
                                </Column>
                                <Column field="price" header="Price">
                                    <template #body="slotProps">
                                        {{ formatCurrency(slotProps.data.price) }}
                                    </template>
                                </Column>
                                <Column field="category" header="Category"></Column>
                                <Column field="rating" header="Reviews">
                                    <template #body="slotProps">
                                        <Rating :modelValue="slotProps.data.rating" readonly />
                                    </template>
                                </Column>
                                <Column header="Status">
                                    <template #body="slotProps">
                                        <Tag :value="slotProps.data.inventoryStatus" :severity="getSeverity(slotProps.data)" />
                                    </template>
                                </Column>
                                <template #footer> In total there are {{ products ? products.length : 0 }} products. </template>
                            </DataTable>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
