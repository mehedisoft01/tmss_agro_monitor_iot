<script setup>
    import {dataTable,fromModal,tableTop} from '@/components';
    import axios from 'axios';
    import {ref, onMounted} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
    const {_l,can,formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId,statusBadge,getImage,changeStatus,handleSelectAll,deleteAllRecords} = {
        ...useBase(),
        ...useHttp(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#", "device_name","device_type", "sensor", "current_value","status","Actions"]);
    const {getDataList, httpReq,urlGenerate} = useHttp();

    const markAsRead = (id, index) => {
        axios.post(`/api/notifications/${id}/read`)
            .then(() => {
                dataList.value.data[index].is_read = 1;
            })
            .catch(err => console.error(err));
    };

    onMounted(() => {
        getDataList();
        getDependency({dependency : []});
    });
</script>


<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop  :defaultObject="{}" :defaultAddButton="false"></tableTop>
        </template>

        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data">
                <td class="fw-medium">{{parseInt(dataList.from)+index}}</td>
                <td>
                    {{ item.device?.display_name || item.device?.device_id || item.device_id }}
                </td>
                <td>{{item.device_category_id === 1 ? 'Warehouse' :
                    item.device_category_id === 2 ? 'Soil' : ''}}
                </td>                            <td>
                {{
                item.sensor_id == 1 ? 'Temperature' :
                item.sensor_id == 2 ? 'Humidity' :
                item.sensor_id == 5 ? 'Soil (N)' :
                item.sensor_id == 6 ? 'Soil (P)' :
                item.sensor_id == 7 ? 'Soil (K)' :
                item.sensor_id == 8 ? 'Soil (EC)' :
                item.sensor_id == 9 ? 'Soil (PH)' :
                item.sensor_id == 10 ? 'Soil Temperature' :
                item.sensor_id == 11 ? 'Soil Humidity' :
                item.sensor_id == 12 ? 'Soil Fertility' :
                ''
                }}
            </td>
                <td>
                    <div style="line-height: 1.4;">
                        <div>
                            <b>Current:</b>
                            <span style="font-size: 16px;">{{ item.current_value }}</span>
                        </div>

                        <div style="font-size: 12px;">
                            Min: {{ item.min_value }} | Max: {{ item.max_value }}
                        </div>
                    </div>
                </td>
                <td>
                    <span :class="item.is_read == 1 ? 'badge bg-success' : 'badge bg-danger'">{{ item.is_read == 1 ? 'Read' : 'Unread' }}</span>
                </td>
                <td>
                    <button
                            v-if="item.is_read == 0"
                            class="btn btn-sm btn-success"
                            @click="markAsRead(item.id, index)">
                        Mark as Read
                    </button>

                    <span v-else class="">—</span>
                </td>
            </tr>
        </template>
    </dataTable>
</template>
