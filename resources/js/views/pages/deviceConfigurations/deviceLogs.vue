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
                <td class="fw-medium">{{index+1}}</td>
                <td>{{item?.device?.name}}</td>
                <td>
                    <span style="background:lightgreen;color:black;padding:5px; border-radius: 10px;" v-if="item.online===1">Online</span>
                    <span style="background:lightsalmon;color:black;padding:5px; border-radius: 10px;" v-else>Offline</span>
                </td>
                <td>{{item.recorded_at}}</td>
                <td>{{item.temperature}} °C</td>
                <td>{{item.humidity}} %</td>
                <td>{{item.battery_percentage}} %</td>
            </tr>
        </template>
    </dataTable>
</template>
