<script setup>
    import {dataTable,fromModal,tableTop} from '@/components';

    import {ref, onMounted,computed} from 'vue';
    import {useStore} from 'vuex';
    const store = useStore();
    import {useBase, useHttp, appStore} from '@/lib';

    const {getDependency, submitForm, editData, deleteRecord} = {...useHttp()};
    const {_l,can,formFilter, formObject, openModal, closeModal, useGetters, dataList, httpRequest, pageDependencies, updateId,statusBadge,characterLimit,getImage,changeStatus,handleSelectAll,deleteAllRecords} = {
        ...useBase(),
        ...useHttp(),
        ...appStore(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')
    };

    const tableHeaders = ref(["#", "device_name", "date", "temperature(c)","humidity(%)","conductivity",'nitrogen','phosphorus','potassium',"ph", "remarks"]);
    const {getDataList, httpReq,urlGenerate} = useHttp();

    onMounted(() => {
        getDataList();
        getDependency({dependency : ['soil_device']});
        formFilter.value.device_id = '';

    });
</script>


<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop :defaultAddButton="false" :defaultFilter="false">
                <template v-slot:filter>
                    <div class="col-md-3">
                        <select class="form-control" v-model="formFilter.device_id">
                            <option value="">{{_l('select_device')}}</option>
                            <option v-for="data in pageDependencies.soil_device || []" :key="data.device_id" :value="data.device_id">
                                {{ data.device_name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <datepicker v-model="formFilter.date_from" class="form-control" :placeholder="_l('from_date')" />
                    </div>
                    <div class="col-md-3">
                        <datepicker v-model="formFilter.date_to" class="form-control"  :placeholder="_l('to_date')"/>
                    </div>

                </template>
            </tableTop>
        </template>
        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td>{{ item.device_name }}</td>
                <td>{{ item.formatted_date }}</td>
                <td>{{ item.temperature ?? '-' }} (°C)</td>
                <td>{{ item.humidity ?? '-' }} (%)</td>
                <td>{{ item.conductivity ?? '-' }}</td>
                <td>{{ item.n ?? '-' }}</td>
                <td>{{ item.p ?? '-' }}</td>
                <td>{{ item.k ?? '-' }}</td>
                <td>{{ item.ph ?? '-' }}</td>
                <td></td>
            </tr>
        </template>
    </dataTable>
</template>
