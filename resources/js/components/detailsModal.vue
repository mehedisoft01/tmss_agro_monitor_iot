<script setup>
    import {useStore} from 'vuex';

    const store = useStore();

    import {useBase, appStore} from '@/lib';

    const {openModal, closeModal, httpRequest, detailsData} = {
        ...useBase(),
        ...appStore().useGetters('httpRequest', 'detailsData')
    };

    const props = defineProps({
        modalId: {type: String, default: 'fromModal'},
        title: {type: String, default: 'Add Form'},
        modalSize: {type: String, default: ''}
    });

    const handleSubmit = () => {
        emit('submit');
    };
</script>
<template>
    <div class="modal fade" :id="modalId" tabindex="-1" :aria-labelledby="`${modalId}Label`" aria-modal="true" role="dialog" data-bs-backdrop="static">
        <div class="modal-dialog" :class="modalSize">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" :id="`${modalId}Label`"><strong>{{ title }}</strong></h6>
                    <button type="button" class="btn-close" @click="closeModal(modalId)"></button>
                </div>
                <div class="modal-body">
                    <div class="row" v-for="(item, kIndex) in detailsData">
                        <div class="col-md-3">{{kIndex}}</div>
                        <div class="col-md-9">{{item}}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a @click="closeModal(modalId)" type="button" class="btn btn-sm btn-secondary"><i class='bx bx-window-close'></i>Close</a>
                </div>
                <div class="modal_loader" v-if="httpRequest">
                    <i class='bx bx-loader bx-spin text-warning'></i>
                </div>
            </div>
        </div>
    </div>
</template>