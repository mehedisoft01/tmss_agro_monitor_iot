<script setup>
    import {watch, onMounted, nextTick} from 'vue';
    import {appStore} from "./lib";
    import { useRoute } from 'vue-router';
    const route = useRoute();

    const {httpRequest} = {...appStore().useGetters('httpRequest')};

    const addRequiredStar = async () => {
        await nextTick();

        document.querySelectorAll('input[validation-activated="1"], select[validation-activated="1"], textarea[validation-activated="1"]').forEach(field => {
                let label = null;

                if (field.previousElementSibling?.tagName === 'LABEL') {
                    label = field.previousElementSibling;
                }
                if (!label) {
                    label = field.closest('[class*="col-"]')?.parentElement?.querySelector('label');
                }
                if (!label) {
                    label = field.closest('.row')?.querySelector('label');
                }
                if (!label) {
                    label = field.closest('div')?.querySelector('label');
                }
                if (label && !label.querySelector('.required-star')) {
                    label.insertAdjacentHTML('beforeend', `<span class="required-star"> *</span>`);
                }
            });
    };

    watch(httpRequest, (status) => {
        if (status) {
            document.querySelectorAll('.page-content button, .page-content input').forEach((el) => {
                    el.setAttribute('disabled', 'disabled')
                })
        } else {
            document.querySelectorAll('.page-content button, .page-content input').forEach((el) => {
                el.removeAttribute('disabled')
            });
        }
    });
    watch(
        () => route.fullPath,
        () => addRequiredStar()
    );
</script>

<template>
    <router-view></router-view>
</template>
