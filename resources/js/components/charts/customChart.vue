<script setup>
import VueApexCharts from "vue3-apexcharts";
import { ref } from "vue";

const apexchart = VueApexCharts;

const props = defineProps({
    chartData: {
        type: Object,
        required: true
    }
});

const chart4 = ref(null);
</script>

<template>
    <apexchart
        ref="chart4"
        :options="props.chartData.chartOptions"
        :series="props.chartData.series"
    />
</template>
