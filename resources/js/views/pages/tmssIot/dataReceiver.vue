<script setup>

    import { dataTable, tableTop } from '@/components';
    import axios from 'axios';
    import { ref, onMounted } from 'vue';
    import { useStore } from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency} = {...useHttp()};
    const {formFilter, pageDependencies, dataList, statusBadge, changeStatus, editData, deleteRecord} = {
        ...useBase(),
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };


    const tableHeaders = ref(["#", "device_id", "farmer", "ph", "n", "p", "k", "fertility", "temperature", "humidity"]);
    const {getDataList, httpReq} = useHttp();

    const farmerDevices = ref([]);
    const enableReceiver = ref({});
    const selectedFarmers = ref({});
    const fetchIntervals = {};


    onMounted(async () => {
        getDataList();
        await getDependency({
            dependency: ['farmers']
        });
        await loadFarmerDevices();

    });

    const loadFarmerDevices = async () => {

        try {

            const response = await axios.get(
                '/api/farmer/devices'
            );

            if (response.data.success) {

                farmerDevices.value =
                    response.data.data || [];

                selectedFarmers.value = {};

                enableReceiver.value = {};

                farmerDevices.value.forEach(device => {
                    if (Number(device.status) === 1) {
                        selectedFarmers.value[device.id] =
                            device.farmer_id || '';
                    } else {
                        selectedFarmers.value[device.id] = '';
                    }
                    enableReceiver.value[device.id] =
                        Number(device.status) === 1;
                });

            } else {
                farmerDevices.value = [];
                selectedFarmers.value = {};
                enableReceiver.value = {};
            }

        } catch (error) {
            console.error('Farmer device error:', error);
            farmerDevices.value = [];
            selectedFarmers.value = {};
            enableReceiver.value = {};
        }
    };
    const filterByFarmer = (device) => {
        const farmerId = selectedFarmers.value[device.id];

        formFilter.value.farmer_id = farmerId || '';
        getDataList();
    };

    const enableDataReceiver = async (device) => {

        const farmerId = selectedFarmers.value[device.id];

        if (!farmerId) {
            alert('Please select a farmer first.');
            return;
        }
        if (!device.device_id) {alert('Device ID not found.');return;}

        try {
            const response = await axios.post(
                '/api/farmer/fetch-soil',
                {
                    farmer_id: farmerId,
                    device_id: device.device_id
                }
            );

            if (!response.data.success) {

                alert(response.data.message || 'Unable to fetch device data');
                return;
            }

            enableReceiver.value[device.id] = true;
            selectedFarmers.value[device.id] = farmerId;
            formFilter.value.farmer_id = farmerId;
            getDataList();
            if (fetchIntervals[device.id]) {
                clearInterval(fetchIntervals[device.id]);

                delete fetchIntervals[device.id];
            }
            fetchIntervals[device.id] = setInterval(async () => {
                if (!enableReceiver.value[device.id]) {
                    clearInterval(
                        fetchIntervals[device.id]
                    );
                    delete fetchIntervals[device.id];

                    return;
                }

                try {

                    const result = await axios.post(
                        '/api/farmer/fetch-soil',
                        {
                            farmer_id: farmerId,
                            device_id: device.device_id
                        }
                    );
                    if (result.data.success) {
                        formFilter.value.farmer_id = farmerId;
                        getDataList();
                    }
                } catch (error) {
                    console.error('Automatic device fetch error:', error);
                }

            }, 60000);

        } catch (error) {
            console.error('Enable device error:', error);
            alert(error.response?.data?.message || 'Something went wrong');
        }
    };
    const disableDataReceiver = async (device) => {

        try {
            const response = await axios.post(
                '/api/farmer/disable-device',
                {
                    device_id: device.device_id
                }
            );

            if (response.data.success) {
                if (fetchIntervals[device.id]) {
                    clearInterval(
                        fetchIntervals[device.id]
                    );
                    delete fetchIntervals[device.id];
                }enableReceiver.value[device.id] = false;

                console.log('Device disabled:', device.device_id);

            } else {
                alert(response.data.message || 'Unable to disable device');
            }

        } catch (error) {
            console.error('Disable device error:', error);
            alert(error.response?.data?.message || 'Something went wrong');
        }
    };
</script>


<template>

    <dataTable :headings="tableHeaders" :setting="true">

        <template v-slot:tableTop>

            <tableTop :defaultFilter="false" :defaultAddButton="false" :defaultSearchButton="false">
                <template v-slot:filter>

                    <div v-for="(device, index) in farmerDevices" :key="device.id" class="row mb-3 align-items-center">
                        <label class="col-md-2 mb-0">
                            <strong>Device {{ index + 1 }} :</strong>
                        </label>

                        <div class="col-md-5">

                            <select v-model="selectedFarmers[device.id]" class="form-control radius-30" @change="filterByFarmer(device)">
                                <option value="">Select Farmers</option>
                                <option v-for="farmer in pageDependencies.farmers" :key="farmer.id" :value="farmer.id">
                                    {{ farmer.name }}
                                </option>
                            </select>

                        </div>

                        <div v-if="selectedFarmers[device.id]" class="col-md-2">

                            <button type="button" class="btn radius-30" :class=" enableReceiver[device.id] ? 'btn-danger'  : 'btn-success'"
                                    @click="enableReceiver[device.id]? disableDataReceiver(device): enableDataReceiver(device)">
                                <i :class="enableReceiver[device.id]? 'bx bx-stop me-1': 'bx bx-broadcast me-1'"></i>
                                {{enableReceiver[device.id] ? 'Disable' : 'Enable'}}
                            </button>

                        </div>

                    </div>
                    <div class="row mt-4">

                        <div class="col-md-12">

                            <h4 class="mb-3">
                                Recent Device Data :
                            </h4>

                        </div>

                    </div>

                </template>

            </tableTop>

        </template>
        <template v-slot:data>

            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td>{{ item.device_id }}</td>
                <td>{{ item.farmer_name || 'N/A' }}</td>
                <td>{{ item.ph }}</td>
                <td>{{ item.n }}</td>
                <td>{{ item.p }}</td>
                <td>{{ item.k }}</td>
                <td>{{ item.fertility }}</td>
                <td>{{ item.temperature }}</td>
                <td>{{ item.humidity }}</td>
            </tr>

        </template>

    </dataTable>

</template>