<script setup>
    import {useStore} from 'vuex';

    const store = useStore();

    import {useBase, appStore} from '@/lib';

    const {openModal, closeModal, httpRequest} = {
        ...useBase(),
        ...appStore().useGetters('httpRequest')
    };

    const props = defineProps({
        modalId: {type: String, default: 'fromModal'},
        title: {type: String, default: 'Add Form'},
        submitButton: {type: Boolean, default: true},
        closeButton: {type: Boolean, default: true},
        enableFooter: {type: Boolean, default: true},
        modalSize: {type: String, default: ''}
    });
    const emit = defineEmits(['submit']);
    const submitButton = props.submitButton ?? true;
    const closeButton = props.closeButton ?? true;

    const handleSubmit = () => {
        emit('submit');
    };
</script>
<template>
    <div class="modal fade" :id="modalId" tabindex="-1" :aria-labelledby="`${modalId}Label`" aria-modal="true" role="dialog" data-bs-backdrop="static">
        <div class="modal-dialog" :class="modalSize">
            <div class="modal-content">
                <form @submit.prevent="handleSubmit">
                    <div class="modal-header">
                        <h6 class="modal-title" :id="`${modalId}Label`"><strong>{{ title }}</strong></h6>
                        <button type="button" class="btn-close" @click="closeModal(modalId)"></button>
                    </div>
                    <div class="modal-body">
                        <slot></slot>
                    </div>
                    <div class="modal-footer" v-if="enableFooter">
                        <template v-if="httpRequest">
                            <a v-if="closeButton" type="button" class="btn btn-secondary"> <i class='bx bx-window-close'></i>Close</a>
                            <a v-if="submitButton" type="button" class="btn btn-success"><i class='bx bx-loader bx-spin text-warning'></i> Submitting...</a>
                        </template>
                        <template v-else>
                            <a v-if="closeButton" @click="closeModal(modalId)" type="button" class="btn btn-sm btn-secondary"><i class='bx bx-window-close'></i>Close</a>
                            <button v-if="submitButton" type="submit" class="btn btn-sm btn-success" ><i class='bx bx-save'></i>Submit</button>
                        </template>
                        <slot name="buttons"></slot>
                    </div>
                </form>
                <div class="modal_loader" v-if="httpRequest">
                    <i class='bx bx-loader bx-spin text-warning'></i>
                </div>
            </div>
        </div>
    </div>
</template>
