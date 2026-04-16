
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
                                <label style="margin: 0;padding: 6px 49px; " for="type2">{{_l('soil')}}</label>
                            </div>
                            <div v-if="filter.type_id == 2" class="mt-2 d-flex gap-2">
                                <button class="btn flex-fill" :class="filter.farmer_type == 1 ? 'btn-success' : 'btn-success'" @click="setFarmerType(1)">🌾{{_l('paddy')}}</button>

                                <button class="btn flex-fill" :class="filter.farmer_type == 2 ? 'btn-primary' : 'btn-primary'" @click="setFarmerType(2)">🥬 {{_l('vegetable')}}</button>
                            </div>
                        </div>

                        <div class="col-md-2 d-none d-md-block">
                            <datepicker :value="filter.date_from" v-model="filter.date_from" class="form-control"  :placeholder="_l('from_date')"/>
                        </div>

                        <div class="col-md-2 d-none d-md-block">
                            <datepicker :value="filter.date_to" v-model="filter.date_to" class="form-control"  :placeholder="_l('to_date')"/>

                        </div>

                        <div class="col-md-2 d-none d-md-block">
                            <button class="btn btn-primary w-100" @click="loadData">{{_l('get_data')}}</button>
                        </div>
                        <div class="col-12 col-md-2  d-none d-md-block">
                            <div class="limit-segment">
                                <input type="radio" id="s1" value="10" v-model="filter.limit" @change="loadData">
                                <label for="s1">10</label>

                                <input type="radio" id="s2" value="25" v-model="filter.limit" @change="loadData">
                                <label for="s2">25</label>

                                <input type="radio" id="s3" value="50" v-model="filter.limit" @change="loadData">
                                <label for="s3">50</label>

                                <input type="radio" id="s4" value="100" v-model="filter.limit" @change="loadData">
                                <label for="s4">100</label>
                            </div>
                        </div>
                        <div v-if="filter.farmer_type && pageDependencies.device?.length"
                             class="mt-2 d-flex flex-wrap gap-2">

                            <button v-for="device in pageDependencies.device"
                                    :key="device.device_id"
                                    class="btn btn-sm btn-info"
                                    :class="filter.device_id == device.device_id ? 'active btn-secondary' : ''"
                                    @click="selectDevice(device)">{{locale === 'bn' ? (device.device_name_bn || device.device_name) : device.device_name}}
                            </button>

                        </div>
                        <div class="w-100 d-block d-md-none mt-3">
                            <div class="mb-2">
                                <datepicker v-model="filter.date_from" class="form-control" :placeholder="_l('from_date')" />
                            </div>

                            <div class="mb-2">
                                <datepicker v-model="filter.date_to" class="form-control" :placeholder="_l('to_date')" />
                            </div>

                            <div>
                                <button class="btn btn-primary w-100" @click="loadData">
                                    {{ _l('get_data') }}
                                </button>
                            </div>

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
                                    <label> <i class='bx bx-spa me-2 text-success font-18'></i>{{_l('fertility')}}</label>
                                </div>
                                <div class="card-body">
                                    <apexchart type="bar" height="350" :options="fertilityOptions" :series="fertilitySeries"/>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <label> <i class="bx bxs-flask me-2 text-success font-18"></i>{{_l('npk_levels')}}</label>
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
    import { useI18n } from 'vue-i18n'

    const { locale } = useI18n();
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
        limit: 10,
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
        chart: { foreColor: '#ffffff', toolbar: { show: false } },
        stroke: { curve: 'smooth', width: 3 },
        legend: { position: 'bottom' },
        xaxis: { categories: [] },
        tooltip: {theme: 'dark'}

    };

    const tempOptions = ref({ ...baseOptions });
    const humOptions = ref({ ...baseOptions });
    const soilTempOptions = ref({ ...baseOptions });
    const soilHumOptions = ref({ ...baseOptions });
    const phOptions = ref({ ...baseOptions });

    const condOptions = ref({
        chart: { type: 'bar', foreColor: themeColor},
        xaxis: { categories: [] },
        tooltip: {theme: 'dark'}

    });

    const fertilityOptions = ref({
        chart: { type: 'bar', foreColor: themeColor},
        xaxis: { categories: [] },
        tooltip: {theme: 'dark'}

    });

    const npkOptions = ref({
        chart: { type: 'bar', foreColor: themeColor },
        xaxis: { categories: [] },
        tooltip: {theme: 'dark'}

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

            if (opt.value.chart) {
                opt.value.chart.foreColor = color;
            }

            if (opt.value.colors) {
                opt.value.colors = [color];
            }
        });
    };
    const updateXAxis = (dates) => {

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
                xaxis: {
                    categories: dates
                }
            };
        });
    };
    // METHODS
    const loadData = async () => {

        const res = await axios.get('/api/dashboard', {
            params: filter.value
        });

        const result = res.data.result;

        updateChartColors(res.data.theme_color);

        let allDates = [];

        tempSeries.value = [];
        humSeries.value = [];
        npkSeries.value = [];

        if (filter.value.type_id == 1) {

            const temp = [];
            const hum = [];

            Object.entries(result || {}).forEach(([name, device]) => {

                allDates = device.dates || [];

                temp.push({
                    name,
                    data: device.temperature || []
                });

                hum.push({
                    name,
                    data: device.humidity || []
                });
            });

            tempSeries.value = temp;
            humSeries.value = hum;

        } else {

            allDates = result?.dates || [];

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

            let npk = [];

            ['n', 'p', 'k'].forEach(key => {
                Object.entries(result?.chartData?.[key] || {}).forEach(([site, values]) => {
                    npk.push({
                        name: `${site} (${key.toUpperCase()})`,
                        data: values
                    });
                });
            });

            npkSeries.value = npk;
        }

        // 🔥 IMPORTANT: set categories once
        categories.value = allDates;

        // 🔥 update all charts
        updateXAxis(allDates);
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
        color: black;
        cursor: pointer;
    }
    .segment-control1 input:checked + label {
        background: #0d6efd;
        color: white;
    }
    .limit-segment {
        display: inline-flex;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }

    .limit-segment input {
        display: none;
    }

    .limit-segment label {
        padding: 6px 15px;
        margin: 0;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.35s ease;
        border-right: 1px solid #dee2e6;
        user-select: none;
        min-width: 48px;
        text-align: center;
    }

    .limit-segment label:last-child {
        border-right: none;
    }

    /* Initial State - Gradient Red to Orange */
    .limit-segment label[for="s1"] { background: #fff; color: #333; }           /* 10 - Default white */
    .limit-segment label[for="s2"] { background: #ffebee; color: #c62828; }     /* 25 - Light red */
    .limit-segment label[for="s3"] { background: #ffccbc; color: #d84315; }     /* 50 - Medium orange-red */
    .limit-segment label[for="s4"] { background: #ff8a65; color: #bf360c; }     /* 100 - Deep orange */

    /* Hover Effect */
    .limit-segment label:hover {
        filter: brightness(1.08);
    }

    /* Active State - Always Blue */
    .limit-segment input:checked + label {
        background: #0d6efd !important;
        color: white !important;
        font-weight: 600;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Active hover */
    .limit-segment input:checked + label:hover {
        background: #0b5ed7 !important;
    }
</style>