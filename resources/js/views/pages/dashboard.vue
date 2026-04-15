
<template>
    <div class="page-wrapper">
        <div class="page-content">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="row mb-3">

                        <div class="col-12 col-md-2" style="width: 18%">
                            <div class="segment-control1">
                                <input type="radio" id="type1" value="1" v-model="filter.type_id"  @change="onTypeChange">
                                <label style="margin: 0;padding: 6px 26px;" for="type1">{{_l('warehouse')}}</label>

                                <input type="radio" id="type2" value="2" v-model="filter.type_id" @change="onTypeChange">
                                <label style="margin: 0;padding: 6px 49px;" for="type2">{{_l('soil')}}</label>
                            </div>
                            <div v-if="filter.type_id == 2" class="mt-2 d-flex gap-2">
                                <button class="btn flex-fill" :class="filter.farmer_type == 1 ? 'btn-success' : 'btn-success'" @click="setFarmerType(1)">🌾{{_l('paddy')}}</button>

                                <button class="btn flex-fill" :class="filter.farmer_type == 2 ? 'btn-primary' : 'btn-primary'" @click="setFarmerType(2)">🥬 {{_l('vegetable')}}</button>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <datepicker :value="filter.date_from" v-model="filter.date_from" class="form-control"  :placeholder="_l('from_date')"/>
                        </div>

                        <div class="col-md-2">
                            <datepicker :value="filter.date_to" v-model="filter.date_to" class="form-control"  :placeholder="_l('to_date')"/>

                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" @click="loadData">{{_l('get_data')}}</button>
                        </div>
                        <div v-if="filter.farmer_type && pageDependencies.device.length" class="mt-2 d-flex flex-row flex-nowrap gap-2">
                            <button v-for="device in pageDependencies.device"
                                    :key="device.device_id"
                                    class="btn btn-sm btn-light flex-shrink-0"
                                    :class="filter.device_id == device.device_id ? 'active btn-secondary' : ''"
                                    @click="selectDevice(device)">
                                {{ device.device_name }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="filter.type_id == 1" class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-thermometer me-2 text-danger font-18"></i>{{_l('temperature')}} (°C)</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="line" height="350" :options="tempOptions" :series="tempSeries" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <label class="text-uppercase"> <i class="bx bxs-droplet me-2 text-primary font-18"></i>{{_l('humidity')}} (%)</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="line" height="350" :options="humOptions" :series="humSeries" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="filter.type_id == 2" class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-thermometer me-2 text-danger font-18"></i>{{_l('temperature')}} (°C)</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="line" height="350" :options="soilTempOptions" :series="soilTempSeries" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-droplet me-2 text-primary font-18"></i>{{_l('humidity')}} (%)</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="line" height="350" :options="soilHumOptions" :series="soilHumSeries" />
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-bolt me-2 text-warning font-18"></i>{{_l('conductivity')}} (%) (us/com)</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="bar" height="350" :options="condOptions" :series="condSeries" />
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-flask me-2 text-success font-18"></i>{{_l('ph')}}</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="line" height="350" :options="phOptions" :series="phSeries" />
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class='bx bxs-leaf me-2 text-success'></i>{{_l('fertility')}}</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="bar" height="350" :options="fertilityOptions" :series="fertilitySeries"/>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-seedling me-2 text-success font-18"></i>{{_l('npk_levels')}}</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="bar" height="350" :options="npkOptions" :series="npkSeries"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>


<script setup>
    import { ref, onMounted } from 'vue'

    import axios from 'axios'
    import VueApexCharts from 'vue3-apexcharts'
    import { useHttp, useBase, appStore } from "@/lib";
    const { httpReq, getDependency } = useHttp();
    const { formFilter, pageDependencies, _l } = {
        ...useBase(),
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters("pageDependencies")
    };
    const {getDataList,urlGenerate} = useHttp();

    const apexchart = VueApexCharts;

    const filter = ref({
        type_id: 1,
        farmer_type: null,
        device_id: null,
        date_from: '',
        date_to: ''
    });

    // SERIES
    const tempSeries = ref([]);
    const humSeries = ref([]);
    const soilTempSeries = ref([]);
    const soilHumSeries = ref([]);
    const condSeries = ref([]);
    const phSeries = ref([]);
    const fertilitySeries = ref([]);
    const npkSeries = ref([]);

    const categories = ref([]);
    const themeColor = ref();

    // OPTIONS
    const baseOptions = {
        chart: { foreColor: themeColor.value, toolbar: { show: false } },
        stroke: { curve: 'smooth', width: 3 },
        legend: { position: 'bottom' },
        xaxis: { categories: [] }
    };

    const tempOptions = ref({ ...baseOptions });
    const humOptions = ref({ ...baseOptions });
    const soilTempOptions = ref({ ...baseOptions });
    const soilHumOptions = ref({ ...baseOptions });
    const phOptions = ref({ ...baseOptions });

    const condOptions = ref({
        chart: { type: 'bar', foreColor: themeColor.value},
        xaxis: { categories: [] }
    });

    const fertilityOptions = ref({
        chart: { type: 'bar', foreColor: themeColor.value},
        xaxis: { categories: [] }
    });

    const npkOptions = ref({
        chart: { type: 'bar', foreColor: themeColor },
        xaxis: { categories: [] }
    });
    const updateChartColors = (color) => {
        const optionsList = [
            tempOptions,
            humOptions,
            soilTempOptions,
            soilHumOptions,
            condOptions,
            phOptions,
            fertilityOptions,
            npkOptions
        ];

        optionsList.forEach(opt => {
            opt.value = {
                ...opt.value,
                chart: {
                    ...opt.value.chart,
                    foreColor: color
                }
            };
        });
    };
    // METHODS
    const loadData = async () => {
        const res = await axios.get('/api/dashboard', { params: filter.value });
        const result = res.data.result;
        const themeColor = res.data.theme_color;
        console.log(themeColor);
        updateChartColors(themeColor);

        if (filter.value.type_id == 1) {

            const temp = [];
            const hum = [];

            Object.entries(result || {}).forEach(([name, device]) => {
                categories.value = device.dates || [];

                temp.push({ name, data: device.temperature });
                hum.push({ name, data: device.humidity })
            });
            tempSeries.value = temp;
            humSeries.value = hum;

            tempOptions.value.xaxis.categories = categories.value;
            humOptions.value.xaxis.categories = categories.value

        } else {

            categories.value = result?.dates || [];

            soilTempSeries.value = Object.entries(result?.chartData?.temperature || {})
                .map(([k, v]) => ({ name: k, data: v }));

            soilHumSeries.value = Object.entries(result?.chartData?.humidity || {})
                .map(([k, v]) => ({ name: k, data: v }));

            condSeries.value = Object.entries(result?.chartData?.conductivity || {})
                .map(([k, v]) => ({ name: k, data: v }));

            phSeries.value = Object.entries(result?.chartData?.ph || {})
                .map(([k, v]) => ({ name: k, data: v }));

            fertilitySeries.value = Object.entries(result?.chartData?.fertility || {})
                .map(([k, v]) => ({ name: k, data: v }));

            let npk = []
            ;['n', 'p', 'k'].forEach(key => {
                Object.entries(result?.chartData?.[key] || {}).forEach(([site, values]) => {
                    npk.push({
                        name: `${site} (${key.toUpperCase()})`,
                        data: values
                    })
                })
            });

            npkSeries.value = npk;

            soilTempOptions.value.xaxis.categories = categories.value;
            soilHumOptions.value.xaxis.categories = categories.value;
            condOptions.value.xaxis.categories = categories.value;
            phOptions.value.xaxis.categories = categories.value;
            fertilityOptions.value.xaxis.categories = categories.value;
            npkOptions.value.xaxis.categories = categories.value
        }
    };

    // UI ACTIONS
    const onTypeChange = () => {
        filter.value.device_id = null;
        filter.value.farmer_type = null;

        getDependency({dependency:{device: {
                    type_id: filter.value.type_id,
                }}});

        loadData();
    };

    const setFarmerType = (type) => {
        filter.value.farmer_type = type;
        filter.value.device_id = null;

        getDependency({dependency:{device: {
                    type_id: filter.value.type_id,
                    farmer_type: filter.value.farmer_type,
                }}});
        loadData();
    };
    const selectDevice = (device) => {
        filter.value.device_id = device.device_id;
        loadData()
    };

    onMounted(() => {
        loadData();
        getDependency({dependency:{device: {
                    type_id: filter.value.type_id,
                }}})
    });
</script>
<style>
    .segment-control1 {
        display: inline-flex;
        border: 1px solid #ccc;
        border-radius: 5px;
        overflow: hidden;
    }
    .segment-control1 input {
        display: none;
    }
    .segment-control1 label {
        padding: 6px 20px;
        background: orange;
        cursor: pointer;
    }
    .segment-control1 input:checked + label {
        background: #0d6efd;
        color: white;
    }
</style>