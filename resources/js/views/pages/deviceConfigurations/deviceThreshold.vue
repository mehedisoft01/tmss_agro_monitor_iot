<script setup>
    import {dataTable,fromModal,tableTop} from '@/components';

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

    const tableHeaders = ref(["#", "device_type", "sensor", "min_value","max_value","remarks","Actions"]);
    const {getDataList, httpReq,urlGenerate} = useHttp();

    onMounted(() => {
        getDataList();
        getDependency({dependency : []});
    });
</script>


<template>
    <dataTable :headings="tableHeaders" :setting="true">
        <template v-slot:tableTop>
            <tableTop  :defaultObject="{}"></tableTop>
        </template>

        <template v-slot:data>
            <tr v-for="(item, index) in dataList.data">
                <td class="fw-medium">{{index+1}}</td>
                <td>{{item.device_category_id === 1 ? 'Warehouse' :
                    item.device_category_id === 2 ? 'Soil' : ''}}
                </td>
                <td>
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
                <td>{{item.min_value}}</td>
                <td>{{item.max_value}}</td>
                <td>{{item.remarks}}</td>
                <td>
                    <a @click="editData({data:item, id:item.id, modal:'fromModal'})" class="btn btn-outline-secondary action">
                        <i class='bx bxs-edit text-warning'></i>
                    </a>
                    <a @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})"  class="btn btn-outline-secondary action">
                        <i class='bx bxs-trash text-danger'></i>
                    </a>
                </td>
            </tr>
        </template>

        <fromModal @submit="submitForm({
            modal: 'fromModal',
            callback: function (retData) {
                Object.assign(formObject, {});
                getDataList();
            }
        })">

            <div class="mb-2">
                <label class="col-form-label">Device Type:</label>
                <select class="form-control pointer" v-model="formObject.device_category_id">
                    <option value="">Select Type</option>
                    <option value="1">Warehouse</option>
                    <option value="2">Soil</option>
                </select>
            </div>
            <div v-if="formObject.device_category_id == '1'" class="mb-2">
                <label class="col-form-label">Sensor:</label>
                <select class="form-control pointer" v-model="formObject.sensor_id">
                    <option value="">Select Sensor</option>
                    <option value="1">Temperature</option>
                    <option value="2">Humidity</option>
                </select>
            </div>
            <div v-if="formObject.device_category_id == '2'" class="mb-2">
                <label class="col-form-label">Sensor:</label>
                <select class="form-control pointer" v-model="formObject.sensor_id">
                    <option value="">Select Sensor</option>
                    <option value="5">Soil (N)</option>
                    <option value="6">Soil (P)</option>
                    <option value="7">Soil (K)</option>
                    <option value="8">Soil (EC)</option>
                    <option value="9">Soil (PH)</option>
                    <option value="10">Soil Temperature</option>
                    <option value="11">Soil Humidity</option>
                    <option value="12">Soil Fertility</option>
                </select>
            </div>
            <div v-if="formObject.sensor_id">

                <div class="mb-2">
                    <label class="col-form-label">Min Value:</label>
                    <input type="number" v-model="formObject.min_value" class="form-control">
                </div>

                <div class="mb-2">
                    <label class="col-form-label">Max Value:</label>
                    <input type="number" v-model="formObject.max_value" class="form-control">
                </div>
            </div>
            <div class="mb-2">
                <label class="col-form-label">Remarks:</label>
                <textarea type="number" v-model="formObject.remarks" class="form-control"></textarea>
            </div>
        </fromModal>
    </dataTable>
</template>
