<script setup>
import {dataTable,tableTop} from '@/components';

import {ref, onMounted, onUnmounted} from 'vue';
import {useStore} from 'vuex';
const store = useStore();
import {useBase, useHttp, appStore} from '@/lib';

// const {} = {...useHttp()};
const {_l,can, useGetters, dataList, httpRequest,characterLimit,changeStatus,handleSelectAll,deleteAllRecords} = {
    ...useBase(),
    ...useHttp(),
    ...appStore(),
    ...appStore().useGetters('dataList', 'httpRequest')
};

const tableHeaders = ref(["#", "device", "status", "time", "temperature", "humidity", "battery"]);
const {getDataList} = useHttp();

let intervalId = null;
onMounted(() => {
    getDataList();
    intervalId = setInterval(() => {
        getDataList();
    },60000);
});
onUnmounted(() =>{
    clearInterval(intervalId);
})
</script>


<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultAddButton="false"></tableTop>
        </template>

        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td class="fw-medium">{{parseInt(dataList.from)+index}}</td>
                <td>{{item?.device?.name}}</td>
                <td>
                    <span :class="item.online == 1 ? 'badge bg-success' : 'badge bg-danger'">{{ item.online == 1 ? 'Online' : 'Offline' }}</span>
                </td>
                <td>{{item.recorded_at}}</td>
                <td>{{item.temperature}} °C</td>
                <td>{{item.humidity}} %</td>
                <td>{{item.battery_percentage}} %</td>
            </tr>
        </template>
    </dataTable>
</template>
