<script setup>

    import {ref, reactive, onMounted} from "vue";
    import {useHttp, useBase, appStore} from "@/lib";
    import {reportComponent} from "@/components";


    const {httpReq, getDependency} = useHttp();
    const {formFilter, pageDependencies, _l} = {
        ...useBase(),
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters("pageDependencies")
    };


    const dataList = ref([]);
    const formFilterState = reactive({farmer_id: "", date_from: "", date_to: "",});
    const getDataList = async () => {

        try {
            const res = await httpReq({
                url: "/api/soil_data",
                method: "get",
                params: formFilterState,
                loader: true
            });
            if (res) {
                dataList.value = res;
            } else {
                dataList.value = [];
            }
        } catch (error) {
            console.error("Soil data error:", error);
            dataList.value = [];

        }

    };


    const excelUrl = () => {

        const params = new URLSearchParams({
            farmer_id:
                formFilterState.farmer_id || "",
            date_from:
                formFilterState.date_from || "",
            date_to:
                formFilterState.date_to || "",
        }).toString();

        window.open(`/api/soil_data_reports/excel?${params}`, "_blank");

    };


    const setTodayDate = () => {

        const today = new Date().toISOString().split("T")[0];
        formFilterState.date_from = today;
        formFilterState.date_to = today;
    };


    onMounted(() => {
        getDependency({
            dependency: ["farmers"]
        });
        setTodayDate();
    });

</script>

<template>

    <reportComponent :headings="['sl','farmer_name','date', 'temperature(°C)','soil_moisture(%)','conductivity', 'nitrogen','phosphorus','potassium','ph','remarks']" :setting="true">
        <template v-slot:filter>
            <div class="row">
                <div class="col-md-9 text-left">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-control" v-model="formFilterState.farmer_id">
                                <option value="">{{ _l('select_farmer') }}</option>
                                <option v-for="data in pageDependencies.farmers || []" :key="data.id" :value="data.id">
                                    {{ data.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <datepicker v-model="formFilterState.date_from" class="form-control" :placeholder="_l('from_date')"/>
                        </div>
                        <div class="col-md-3">
                            <datepicker v-model="formFilterState.date_to" class="form-control" :placeholder="_l('to_date')"/>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" @click="getDataList">{{ _l('get_data') }}</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <button class="btn btn-success" @click="excelUrl">
                        {{_l('export_excel')}}
                    </button>
                </div>
            </div>

        </template>
        <template v-slot:data>

            <template v-for="(deviceData, deviceName) in dataList":key="deviceName">

                <tr v-for="(item,index) in deviceData" :key="item.id">
                    <td>{{ index + 1 }}</td>
                    <td v-if="index === 0" :rowspan="deviceData.length" class="text-center align-middle">
                        {{ item.farmer_name || '-' }}
                    </td>
                    <td>{{ item.formatted_date }}</td>
                    <td>{{ item.temperature ?? '-' }} °C
                    </td>
                    <td>{{ item.humidity ?? '-' }} %
                    </td>
                    <td>{{ item.conductivity ?? '-' }}</td>
                    <td>{{ item.n ?? '-' }}</td>
                    <td>{{ item.p ?? '-' }}</td>
                    <td>{{ item.k ?? '-' }}</td>
                    <td>{{ item.ph ?? '-' }}</td>
                    <td>-</td>

                </tr>

            </template>

        </template>

    </reportComponent>

</template>
<style scoped>

</style>