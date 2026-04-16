<script setup>
    import { ref, reactive, computed, onMounted,watch } from 'vue'
    import VueApexCharts from "vue3-apexcharts"
    import { useStore } from 'vuex'
    import { useBase, useHttp, appStore } from '@/lib'

    const apexchart = VueApexCharts

    const store = useStore()

    const { getDependency, submitForm, editData, deleteRecord } = { ...useHttp() }

    const {_l, can, formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId, statusBadge, getImage, changeStatus, handleSelectAll, deleteAllRecords} = {
        ...useBase(),
        ...useHttp(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    }

    const { getDataList, httpReq, urlGenerate } = useHttp()

    const latestData = reactive({
        temperature: 0,
        humidity: 0,
        battery_percentage: 0
    })
    const filterObject = ref([])
    const chartData = ref([])
    const actions = ref([])
    const sensors = ref([])
    const farmHealth = reactive({})
    const chartSeries = ref([])


    const chartOptions = ref({
        chart: {
            type: 'line',
            height: 200,
            toolbar: { show: false },
            background: 'transparent'
        },

        stroke: {
            curve: 'smooth',
            width: 3
        },

        colors: ['#0d6efd', '#198754', '#dc3545'],

        tooltip: {
            theme: 'dark',
            style: {
                fontSize: '12px'
            }
        },

        xaxis: {
            categories: [],
            labels: {
                style: {
                    colors: '#d1d5db',
                    fontSize: '12px'
                }
            },
            axisBorder: {
                show: true,
                color: '#6c757d'
            },
            axisTicks: {
                show: true,
                color: '#6c757d'
            }
        },

        yaxis: {
            labels: {
                style: {
                    colors: '#d1d5db',
                    fontSize: '12px'
                }
            }
        },

        grid: {
            borderColor: 'rgba(255,255,255,0.1)'
        },

        legend: {
            show: false
        }
    })


    const sensorMap = computed(() => {
        const map = {}
        sensors.value.forEach(s => {
            map[s.name] = s
        })
        return map
    })

    watch(
        () => formFilter.value.date_from,
        (newVal) => {
            if (newVal) {
                fetchDashboardData()
            }
        }
    )

    // Storage API
    const fetchStorageData = async () => {
        try {
            const response = await httpReq({
                url: '/api/storageData',
                method: 'GET',
                params: {
                    device_id: filterObject.value.device_id
                }
            })

            if (response) {
                Object.assign(latestData, response.latest || {})
                actions.value = response.actions || []
            }

        } catch (error) {
            console.error('Storage API Error:', error)
        }
    }

    // Dashboard API
    const fetchDashboardData = async () => {
        try {
            const response = await httpReq({
                url: '/api/dashboardV2',
                method: 'GET',
                params: {
                    device_id: formFilter.value.device_id,
                    date_from: formFilter.value.date_from
                }
            })

            console.log("FINAL:", response)

            chartData.value = response.chartData || []
            sensors.value = response.sensors || []
            Object.assign(farmHealth, response.farmHealth || {})

            updateChart()

        } catch (error) {
            console.error('Dashboard API Error:', error)
        }
    }

    // ================= CHART LOGIC =================

    const updateChart = () => {
        const labels = chartData.value.map(i => i.label)
        const temperatureData = chartData.value.map(i => i.temperature)
        const humidityData = chartData.value.map(i => i.humidity)
        const phData = chartData.value.map(i => i.ph)

        chartOptions.value = {
            ...chartOptions.value,
            xaxis: {
                categories: labels
            }
        }

        chartSeries.value = [
            {
                name: 'Soil Moisture',
                data: humidityData
            },
            {
                name: 'PH',
                data: phData
            },
            {
                name: 'Temperature',
                data: temperatureData
            }
        ]
    }

    // ================= HELPERS =================

    const getColor = (status) => {
        if (status === 'LOW') return '#dc3545'
        if (status === 'HIGH') return '#fd7e14'
        return '#198754'
    }

    const getAlertColor = (alert) => {
        if (alert.includes('LOW')) return '#dc3545'
        if (alert.includes('HIGH')) return '#fd7e14'
        return '#198754'
    }

    const formatAlert = (alert) => {
        if (alert.includes('HUMIDITY')) {
            return "Low Soil Moisture → Irrigation needed"
        }

        if (alert.includes('TEMPERATURE')) {
            return "High Temperature → Cooling required"
        }

        if (alert.includes('N')) {
            return "Nitrogen Low → Apply fertilizer"
        }

        return alert
    }


    onMounted(() => {
        fetchDashboardData()
        formFilter.value.device_id='';
        filterObject.value.device_id='';

        getDependency({
            dependency: ['soil_device', 'warehouse_device']
        })
    })
</script>

<template>
    <div class="page-wrapper">
        <div class="page-content">
            <div class="row">
                <div class="total_counter">
                    <div class="row card_items p-4">

                        <h2 class="fw-bold mb-4">{{_l('farm_and_storage_dashboard')}}</h2>
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <select class="form-control pointer" v-model="formFilter.device_id" @change="fetchDashboardData">
                                    <option value="">{{_l('select_device')}}</option>
                                    <template v-for="(data, index) in pageDependencies.soil_device">
                                        <option :value="data.id">{{data.device_name}}</option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <datepicker class="form-control" v-model="formFilter.date_from" @change="fetchDashboardData" :placeholder="_l('from_date')"></datepicker>
                            </div>
                        </div>


                        <div class="row g-4">
                            <!-- LEFT SIDE -->
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm p-4 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                        <div>
                                            <h5 class="fw-bold d-flex align-items-center gap-2">
                                                <i class="bx bx-flask bx-lg"></i>
                                               {{_l('farm_health_overview')}}
                                            </h5>
                                            <div class="alert-box p-3 mt-3 vertical-alert">
                                                <p class="mb-2 fs-5">
                                                    <i class="text-orange fas fa-exclamation-triangle me-2 fs-5"></i>
                                                    <strong>{{_l('alert')}}</strong>
                                                </p>
                                                <div class="ticker-wrapper">
                                                    <div class="ticker" v-if="farmHealth.alerts && farmHealth.alerts.length">
                                                        <p v-for="(alert, index) in farmHealth.alerts"
                                                           :key="index"
                                                           class="ticker-item mb-2 fs-6">

                                                            <i class="fas fa-exclamation-circle me-2 fs-5"
                                                               :style="{ color: getAlertColor(alert) }"></i>

                                                            {{ formatAlert(alert) }}

                                                        </p>

                                                    </div>

                                                    <p v-else class="text-success">{{_l('no_sensor_data_available')}}</p>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <div class="gauge-storage"
                                                 :style="{ '--value': farmHealth.score }">
                                                <span class="h4">{{ farmHealth.score }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- text-muted removed -->
                                    <div class="row text-center mt-4">
                                        <div class="col-3" v-if="sensorMap.HUMIDITY">
                                            <i class="fas fa-tint fa-3x"
                                               :style="{ color: getColor(sensorMap.HUMIDITY.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.HUMIDITY.status) }">
                                                {{ sensorMap.HUMIDITY.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.HUMIDITY.value }}%
                                            </div>

                                            <div class="small">{{_l('soil_moisture')}}</div>
                                        </div>
                                        <div class="col-3" v-if="sensorMap.PH">
                                            <i class="fas fa-flask fa-3x"
                                               :style="{ color: getColor(sensorMap.PH.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.PH.status) }">
                                                {{ sensorMap.PH.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.PH.value }}
                                            </div>

                                            <div class="small">{{_l('soil_ph')}}</div>
                                        </div>
                                        <div class="col-3" v-if="sensorMap.TEMPERATURE">
                                            <i class="fas fa-thermometer-half fa-3x"
                                               :style="{ color: getColor(sensorMap.TEMPERATURE.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.TEMPERATURE.status) }">
                                                {{ sensorMap.TEMPERATURE.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.TEMPERATURE.value }}°C
                                            </div>

                                            <div class="small">{{_l('soil_temperature')}}</div>
                                        </div>
                                        <div class="col-3" v-if="sensorMap.FERTILITY">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                                 style="width:50px;height:50px;background:#198754;">
                                                <i class="fas fa-bolt text-white"></i>
                                            </div>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.FERTILITY.status) }">
                                                {{ sensorMap.FERTILITY.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.FERTILITY.value }}
                                            </div>

                                            <div class="small">{{_l('fertility')}}</div>
                                        </div>
                                    </div>

                                    <div class="row text-center mt-4">
                                        <div class="col-3" v-if="sensorMap.N">
                                            <i class="fas fa-flask fa-3x"
                                               :style="{ color: getColor(sensorMap.N.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.N.status) }">
                                                {{ sensorMap.N.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.N.value }}<small>mg/kg</small>
                                            </div>

                                            <div class="small">{{_l('n')}}</div>
                                        </div>
                                        <div class="col-3" v-if="sensorMap.P">
                                            <i class="fas fa-flask fa-3x"
                                               :style="{ color: getColor(sensorMap.P.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.P.status) }">
                                                {{ sensorMap.P.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.P.value }}<small>mg/kg</small>
                                            </div>

                                            <div class="small">{{_l('p')}}</div>
                                        </div>
                                        <div class="col-3" v-if="sensorMap.K">
                                            <i class="fas fa-flask fa-3x"
                                               :style="{ color: getColor(sensorMap.K.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.K.status) }">
                                                {{ sensorMap.K.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.K.value }}<small>mg/kg</small>
                                            </div>

                                            <div class="small">{{_l('k')}}</div>
                                        </div>
                                        <div class="col-3" v-if="sensorMap.EC">
                                            <i class="fas fa-flask fa-3x"
                                               :style="{ color: getColor(sensorMap.EC.status) }"></i>

                                            <div class="fw-bold mt-2"
                                                 :style="{ color: getColor(sensorMap.EC.status) }">
                                                {{ sensorMap.EC.status }}
                                            </div>

                                            <div class="h4 mb-0">
                                                {{ sensorMap.EC.value }}<small>mg/kg</small>
                                            </div>

                                            <div class="small">{{ _l('ec') }}</div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- ACTIONS UPDATED -->
                                    <h6 class="fw-bold fs-5">{{_l('actions')}}</h6>
                                    <div class="row fs-6">
                                        <div class="col-6">
                                            <p v-for="(a, index) in farmHealth.actions" :key="index"> <i class="fas fa-exclamation-triangle me-2 fs-5"style="color: #dc3545"></i> {{ a }} </p>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- RIGHT SIDE -->
                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm p-4 mb-4">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="fw-bold">{{_l('soil_deep_dive')}}</h5>
                                        <div class="small">
                                            <span class="me-2"><i class="bx bx-circle" style="color: #0d6efd"></i> {{_l('outlier')}}</span>
                                            <span><i class="bx bx-circle" style="color: #dc3545"></i> {{_l('high_outlier')}}</span>
                                        </div>
                                    </div>

                                    <p class="small mb-1">{{_l('soil_parameters_-_last_7_days')}}</p>

                                    <div style="height: 200px;">
                                        <apexchart
                                                type="line"
                                                height="200"
                                                :options="chartOptions"
                                                :series="chartSeries"
                                        />
                                    </div>

                                    <div class="d-flex justify-content-center small mt-2">
                                        <span class="mx-2"><i class="bx bx-circle me-1" style="color:#dc3545;"></i> {{_l('soil_moisture')}}</span>
                                        <span class="mx-2"><i class="bx bx-circle me-1" style="color: #198754"></i>{{_l('soil_temperature')}}</span>
                                        <span class="mx-2" style="color:#0d6efd;">-- PH</span>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm p-4">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <h5 class="fw-bold mb-4">{{_l('storage_monitoring')}}</h5>
                                        </div>
                                        <div class="col-md-5">
                                            <select name="option" class="form-control" v-model="filterObject.device_id" @change="fetchStorageData">
                                                <option value="">---{{_l('select_to_fetch_data')}}---</option>
                                                <template v-for="(parent, index) in pageDependencies.warehouse_device">
                                                    <option :value="parent.device_id">{{parent.display_name}}</option>
                                                </template>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row align-items-center">

                                        <div class="col-md-4 d-flex justify-content-center">
                                            <div class="gauge-storage"
                                                 :style="{ '--value': latestData.temperature }">
                                                <span class="h4">{{ latestData.temperature }}°C</span>
                                                <small>Temperature</small>
                                            </div>
                                        </div>

                                        <div class="col-md-4 d-flex justify-content-center">
                                            <div class="gauge-storage"
                                                 :style="{ '--value': latestData.humidity }">
                                                <span class="h4">{{ latestData.humidity }}%</span>
                                                <small>Humidity</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex justify-content-center">
                                            <div class="gauge-storage"
                                                 :style="{ '--value': latestData.battery_percentage }">
                                                <span class="h4">{{ latestData.battery_percentage }}%</span>
                                                <small>Battery</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- STORAGE ACTIONS -->
                                    <div class="mt-4">
                                        <h6 class="fw-bold fs-5">{{_l('actions')}}</h6>

                                        <div class="row mt-2 fs-6">
                                            <div class="col-12">
                                                <p v-for="(action, index) in actions"
                                                   :key="index"
                                                   class="mb-2">
                                                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>
                                                    {{ action }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page_loader" v-if="httpRequest">
                <i class='bx bx-loader bx-spin text-warning'></i>
            </div>
        </div>
    </div>
</template>

<style scoped>

    .alert-box {
        border-radius: 10px;
        border: 1px solid #ffe8cc;
    }

    .text-orange { color: #f39c12; }


    .gauge-storage {
        --value: 0;

        width: 100px;
        height: 100px;
        border-radius: 50%;
        position: relative;

        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        background: conic-gradient(
                #198754 calc(var(--value) * 1%),
                rgba(255,255,255,0.15) 0
        );

        box-shadow: 0 0 10px rgba(0,0,0,0.3);
    }

    /* inner circle */
    .gauge-storage::before {
        content: "";
        position: absolute;
        inset: 10px;
        background: #0d1b2a;
        border-radius: 50%;
        z-index: 1;
    }

    /* text styles */
    .gauge-storage span,
    .gauge-storage small {
        position: relative;
        z-index: 2;
        color: #ffffff;
        text-align: center;
        line-height: 1.2;
    }

    .gauge-storage span {
        font-size: 18px;
        font-weight: bold;
    }

    .gauge-storage small {
        font-size: 12px;
        color: #d1d5db;
    }

    .vertical-alert {
        height: 120px; /* control visible area */
        overflow: hidden;
        position: relative;
    }

    .ticker-wrapper {
        height: 100%;
        overflow: hidden;
        position: relative;
    }

    .ticker {
        display: flex;
        flex-direction: column;
        animation: scrollUp 15s linear infinite;
    }

    .ticker-item {
        height: 30px;
        line-height: 12px;
        display: flex;
        align-items: center;
    }


    /* animation */
    @keyframes scrollUp {
        0% {
            transform: translateY(100%);
        }
        100% {
            transform: translateY(-100%);
        }
    }
    .card {
        border-radius: 15px;
    }
</style>
