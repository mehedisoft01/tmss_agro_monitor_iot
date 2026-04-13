<script setup>
    import {ref, reactive, onMounted} from 'vue'
    import {useStore} from 'vuex'
    import {useRouter} from 'vue-router'

    import {dataTable, tableTop, detailsModal, pageTop} from '@/components'
    import moduleForm from "@/views/pages/rbac/moduleForm.vue";
    import {appStore, useHttp, useBase} from "@/lib";
    const store  = useStore();
    const router  = useRouter();

    const {_l, useGetters, getDataList, submitForm, editData, deleteRecord, getDependency,changeStatus, openModal, handleSelectAll, statusBadge, deleteAllRecords, assignStore} = {
        ...appStore(),
        ...useHttp(),
        ...useBase()
    };
    const { httpRequest, dataList, pageDependencies } = useGetters('httpRequest', 'dataList', 'pageDependencies');

    const tableHeaders = reactive(['sl', 'user','controller','method','route','ip','date','action']);
    const permissions = reactive(['directives.js', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'status']);

    const detailsData = (data) =>{
        openModal({
            modalId:'detailsModal',
            callback : () =>{
                assignStore('detailsData', data);
            }
        });
    };

    onMounted(()=>{
        getDataList();
    });

</script>
<template>
    <dataTable :headings="tableHeaders" :loader="true">
        <template v-slot:tableTop>
            <tableTop :defaultAddButton="false"></tableTop>
        </template>
        <template v-slot:data>
            <template v-for="(log, index) in dataList.data" :key="log.id">
                <tr>
                    <td>{{ parseInt(dataList.from) + index }}</td>
                    <td>{{ log.user?.name || 'Guest' }}</td>
                    <td>{{ log.controller ? `${log.controller}` : '-' }}</td>
                    <td>{{ log.method }}</td>
                    <td>{{ log.route_name }}</td>
                    <td>{{ log.ip_address }}</td>
                    <td>{{ new Date(log.created_at).toLocaleString() }}</td>
                    <td>
                        <a @click="detailsData(log)" class="btn btn-outline-secondary action">
                            <i class='bx bxs-show text-warning'></i>
                        </a>
                    </td>
                </tr>
            </template>
        </template>
    </dataTable>
    <detailsModal modalId="detailsModal" title="Details"></detailsModal>
</template>

<style scoped>

</style>