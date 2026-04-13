<script setup>
    import { ref, reactive } from 'vue'
    import {useStore} from 'vuex'
    const store  = useStore();

    import {appStore, useHttp} from "@/lib";
    const {useGetters} = appStore();
    const {submitForm} = useHttp();
    const activePanel = ref(false);
    const themeGaussian = reactive([
        {name: 'theme1', value: 'bg-theme bg-theme1'},
        {name: 'theme2', value: 'bg-theme bg-theme2'},
        {name: 'theme3', value: 'bg-theme bg-theme3'},
        {name: 'theme4', value: 'bg-theme bg-theme4'},
        {name: 'theme5', value: 'bg-theme bg-theme5'},
        {name: 'theme6', value: 'bg-theme bg-theme6'},
        {name: 'theme16', value: 'bg-default bg-theme2'},
    ]);
    const themeGradient = reactive([
        {name: 'theme7', value: 'bg-theme bg-theme7'},
        {name: 'theme8', value: 'bg-theme bg-theme8'},
        {name: 'theme9', value: 'bg-theme bg-theme9'},
        {name: 'theme10', value: 'bg-theme bg-theme10'},
        {name: 'theme11', value: 'bg-theme bg-theme11'},
        {name: 'theme12', value: 'bg-theme bg-theme12'},
        {name: 'theme13', value: 'bg-theme bg-theme13'},
        {name: 'theme14', value: 'bg-theme bg-theme14'},
        {name: 'theme15', value: 'bg-theme bg-theme15'},
    ]);

    const toggleThemePanel = () => {
        activePanel.value = !activePanel.value;
    };
    const changeTheme = (theme) => {
        submitForm({
            data : {request:'theme',theme:theme.value},
            url : `api/profile`,
            validation : false,
            callback : function(retData){
                $('body').attr('class', theme.value);
            }
        });
    }
</script>

<template>
    <div class="switcher-wrapper" :class="activePanel ? 'switcher-toggled' : ''">
        <div class="switcher-btn">
            <i @click="toggleThemePanel()" class='bx bx-cog bx-spin'></i>
        </div>
        <div class="switcher-body">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 text-uppercase">Theme Customizer</h5>
                <button type="button" class="btn-close ms-auto close-switcher"  @click="toggleThemePanel()" aria-label="Close"></button>
            </div>
            <hr/>
            <p class="mb-0">Gaussian Texture</p>
            <hr>
            <ul class="switcher">
                <template v-for="theme in themeGaussian">
                    <li class="pointer" :id="theme.name" @click="changeTheme(theme)"></li>
                </template>
            </ul>
            <hr>
            <p class="mb-0">Gradient Background</p>
            <hr>
            <ul class="switcher">
                <template v-for="theme in themeGradient">
                    <li class="pointer" :id="theme.name" @click="changeTheme(theme)"></li>
                </template>
            </ul>
        </div>
    </div>
</template>

<style scoped>

</style>
