<script setup>
    import {ref, reactive, onMounted, computed} from 'vue'
    import {useStore} from 'vuex'
    import {useRouter} from 'vue-router'

    import {dataTable, tableTop, fromModal, pageTop} from '@/components'
    import moduleForm from "@/views/pages/rbac/moduleForm.vue";
    import {appStore, useHttp, useBase} from "@/lib";
    import Datepicker from "../../plugins/datepicker.vue";
    const store  = useStore();
    const router  = useRouter();

    const {_l, useGetters, getDataList,copiedItem,copyText, submitForm, editData, deleteRecord, getDependency,changeStatus, openModal, handleSelectAll, statusBadge, deleteAllRecords} = {
        ...appStore(),
        ...useHttp(),
        ...useBase()
    };
    let {formObject} = useGetters('formObject');
    const { httpRequest, dataList, pageDependencies } = useGetters('httpRequest', 'dataList', 'pageDependencies');

    const tableHeaders = reactive(['sl', {name: '', listObject: dataList}, 'Date', 'Day', 'Year','remarks', 'action']);
    const permissions = reactive(['directives.js', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'status']);

    const startYear = 2025
    const currentYear = new Date().getFullYear()

    const yearRange = computed(() => {
        const endYear = currentYear + 1
        return Array.from(
            { length: endYear - startYear + 1 },
            (_, i) => startYear + i
        )
    });
    const days = ["Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
    const changeTigger = () =>{
        formObject.value.fix_holiday = pageDependencies.value.fix_holiday
    }

    onMounted(()=>{
        getDataList();
        getDependency({
            dependency : ['permissions', 'components', 'icons', 'fix_holiday'],
        });
    });

</script>
<template>
    <dataTable :headings="tableHeaders" :loader="true">
        <template v-slot:tableTop>
            <tableTop :defaultObject="{permissions:[]}">
                <div class="col-md-3">
                    <input type="text" class="form-control radius-30" placeholder="Search Order">
                </div>
            </tableTop>
        </template>
        <template v-slot:topRight v-if="dataList.data !== undefined">
            <a class="btn btn-sm btn-outline-danger radius-30 text-uppercase" @click="deleteAllRecords({dataObject:dataList.data})" v-if="dataList.data.some(each => parseInt(each.checked) === 1)">Delete All</a>
        </template>
        <template v-slot:data>
            <template v-for="(item, index) in dataList.data" :key="item.id">
                <tr>
                    <td>{{index+1}}</td>
                    <td class="checkbox">
                        <input :checked="item.checked" @change="handleSelectAll($event, [item])" class="form-check-input me-3 pointer" type="checkbox"/>
                    </td>
                    <td>{{ _l(item.holiday_date) }}</td>
                    <td>{{ _l(item.holiday_days) }}</td>
                    <td class="pointer" >{{ item.holiday_year }}</td>
                    <td class="pointer" >{{ item.remarks }}</td>
                    <td>
                        <a @click="deleteRecord({targetId:item.id,listIndex:index, listObject:dataList.data})" class="btn btn-outline-secondary action">
                            <i class='bx bxs-trash text-danger'></i>
                        </a>
                    </td>
                </tr>
            </template>
        </template>

        <fromModal title="Module Form" modal-size="modal-lg" @submit="submitForm({
            modal: 'fromModal',
            callback: function (retData) {
                getDataList();
            }
        })">
            <div class="row mb-2">
                <label class="col-md-2"><strong>Type :</strong></label>
                <div class="col-md-8">
                    <select @change="changeTigger" class="form-control" v-model="formObject.holiday_type">
                        <option value="1">{{_l('weekend_holiday')}}</option>
                        <option value="2">{{_l('PublicHoliday')}}</option>
                        <option value="3">{{_l('CustomHoliday')}}</option>
                    </select>
                </div>
            </div>
            <template v-if="parseInt(formObject.holiday_type) === 1">
                <div class="row mb-2">
                    <label class="col-md-2"><strong>Year : </strong></label>
                    <div class="col-md-8">
                        <select class="form-control" v-model="formObject.year">
                            <option v-for="year in yearRange" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-2"><strong>{{ _l('day') }} : </strong></label>
                    <div class="col-md-8">
                        <select class="form-control" v-model="formObject.holiday_days">
                            <option v-for="day in days" :key="day" :value="day">
                                {{ day }}
                            </option>
                        </select>
                    </div>
                </div>
            </template>
            <template v-if="parseInt(formObject.holiday_type) === 2">
                <div class="row mb-2">
                    <label class="col-md-2"></label>
                    <div class="col-md-8">
                        <div class="row">
                            <label class="col-md-4" v-for="(date, dIndex) in formObject.fix_holiday">
                                <input type="checkbox" v-model="date.checked" :checked="date.checked" :true-value="1" :false-value="0"> {{ date.name }} ({{ date.value }})
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-2"><strong>Year : </strong></label>
                    <div class="col-md-8">
                        <select class="form-control" v-model="formObject.year">
                            <option v-for="year in yearRange" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                </div>
            </template>
            <template v-if="parseInt(formObject.holiday_type) === 3">
                <div class="row mb-2">
                    <label class="col-md-2"><strong>{{_l('from_date')}} : </strong></label>
                    <div class="col-md-8">
                        <datepicker v-model="formObject.from_date"></datepicker>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-2"><strong>{{_l('to_date')}} : </strong></label>
                    <div class="col-md-8">
                        <datepicker v-model="formObject.to_date"></datepicker>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-2"><strong>{{_l('remarks')}} : </strong></label>
                    <div class="col-md-8">
                        <textarea class="form-control" v-model="formObject.remarks" rows="2"></textarea>
                    </div>
                </div>
            </template>
        </fromModal>
    </dataTable>
</template>

<style scoped>

</style>
