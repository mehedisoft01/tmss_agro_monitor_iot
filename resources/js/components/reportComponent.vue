<script setup>
    import {tableHeader, tableTop, pageTop} from "@/components";
    import {pagination} from "@/plugins";

    import {appStore, useHttp,useBase} from "@/lib";

    const {_l,useGetters, getDataList, dataList, httpRequest,printData} = {
        ...useBase(),
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters('httpRequest')
    };

    const props = defineProps({
        headings: Array,
        loader: {type:Boolean, default:true},
        defaultPagination: {type: Boolean, default: true},
        defaultObject: {type: Object, default: () => ({})},
        defaultFilter: {type: Boolean, default: true},
        tableClass : {type: String, default: 'data_table'},

    });

    const headings = props.headings ?? [];

    defineEmits(['page-change'])

</script>
<template>
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <pageTop :list-page="false">
                    <slot name="topRight"></slot>
                </pageTop>
            </div>
            <div class="card">
                <div class="card-body data-table">
                    <div class="align-items-center mb-3 gap-3">
                        <div class="row">
                            <div class="col-md-9 text-left">
                                <div class="row">
                                    <slot></slot>
                                    <slot name="filter"></slot>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <slot name="action"></slot>
                                <a type="button" @click="printData()" class="btn btn-outline-secondary">
                                    <i class="bx bx-printer"></i> Print
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="print-header" id="printDiv">
                        <div>
                            <slot name="title-header"></slot>
                        </div>
                        <div class=" mb-2 shadow-sm border">
                            <table :class="`table mb-0 table-bordered ${tableClass}`">
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
                                <tr class="table-light">
                                    <slot name="footer"></slot>
                                </tr>
                                </tfoot>
                            </table>
                            <slot name="bottom_data"></slot>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page_loader" v-if="loader && httpRequest">
                <i class='bx bx-loader bx-spin text-warning'></i>
            </div>
        </div>
    </div>
</template>
