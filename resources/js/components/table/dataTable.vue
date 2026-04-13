<script setup>
    import {tableHeader, tableTop, pageTop} from "@/components";
    import {pagination} from "@/plugins";

    import {appStore, useHttp} from "@/lib";

    const {useGetters, getDataList, dataList, httpRequest} = {
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters('httpRequest')
    };

    const props = defineProps({
        headings: Array,
        loader: {type:Boolean, default:true},
        defaultPagination: {type: Boolean, default: true},
        defaultObject: {type: Object, default: () => ({})},
        listPage: {type: Boolean, default: true},
        tableClass : {type: String, default: 'data_table'},
    });

    const headings = props.headings ?? [];

    defineEmits(['page-change'])

</script>


<template>
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <pageTop :listPage="listPage">
                <slot name="topRight"></slot>
            </pageTop>
        </div>
        <div class="card">
            <div class="card-body data-table">
                <slot name="tableTop"></slot>
                <div class="table-responsive mb-2" id="printDiv">
                    <table :class="`table mb-0 ${props}`">
                        <thead class="table-light">
                            <tr>
                                <template v-if="$slots.header">
                                    <slot name="header"></slot>
                                </template>
                                <template v-else>
                                    <tableHeader :headings="headings"></tableHeader>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <slot name="data"></slot>
                        </tbody>
                        <tfoot v-if="$slots.footer">
                            <tr>
                                <td :colspan="columnCount">
                                    <slot name="footer"></slot>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <slot name="bottom_data"></slot>
                </div>
                <template v-if="defaultPagination && dataList !== undefined">
                    <pagination v-if="dataList.data !== undefined" :data="dataList" @paginateTo="getDataList"/>
                </template>
                <slot name="pagination"></slot>
            </div>
        </div>
        <slot></slot>
        <div class="page_loader" v-if="loader && httpRequest">
            <i class='bx bx-loader bx-spin text-warning'></i>
        </div>
    </div>
</div>
</template>
