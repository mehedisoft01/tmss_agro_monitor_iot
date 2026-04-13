<script setup>
    import {fileUpload} from '@/components'
    import {onMounted, ref, reactive} from 'vue'
    import {useStore} from 'vuex'
    const store = useStore();
    import {useBase, appStore, useHttp} from "@/lib";
    const {_l, getImage, formObject, submitForm, useGetters, httpReq, urlGenerate,pageDependencies,getDependency} = {
        ...useBase(),
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters('dataList', 'httpRequest', 'pageDependencies', 'updateId')

    };

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

    onMounted(async ()=>{
        const authUser = await httpReq({
            method : 'get',
            url : urlGenerate('api/profile'),
            loader:true
        });
        if (authUser){
            store.commit('formObject', authUser);
        }
        getDependency({dependency : ['staff_designation']});

    });
</script>
<template>
    <div class="page-wrapper">
        <div class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            </div>
            <div class="main-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-9">
                                        <form @submit.prevent="submitForm({data : formObject, method:'post'})">
                                            <div class="row mb-3">
                                                <div class="col-md-3 offset-4">
                                                    <fileUpload :object="formObject" :column="'image'" :height="200"></fileUpload>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Full Name</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input v-model="formObject.name" type="text" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Email</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" v-model="formObject.email" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">User Name</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" v-model="formObject.username" class="form-control"  />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Phone</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" v-model="formObject.phone" class="form-control"  />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Designation</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <select v-model="formObject.designation" class="form-control">
                                                        <option value="">Select</option>
                                                        <template v-for="item in pageDependencies.staff_designation">
                                                            <option :value="item.id">{{item.designation_name}}</option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-12">
                                                    <h5>Theme & Locale</h5>
                                                    <hr>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Theme</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" readonly v-model="formObject.theme" class="form-control"  />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Locale</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" readonly v-model="formObject.locale" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-12">
                                                    <h5>Password</h5>
                                                    <hr>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">Old Password</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" v-model="formObject.password" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-sm-3">
                                                    <h6 class="mb-0">New Password</h6>
                                                </div>
                                                <div class="col-sm-9">
                                                    <input type="text" v-model="formObject.new_password" class="form-control" />
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-3"></div>
                                                <div class="col-sm-9">
                                                    <button type="submit" class="btn btn-light px-4" >Save Changes </button>
                                                </div>
                                            </div>
                                        </form>
                                        <hr>
                                        <div class="row">
                                            <h6 class="col-md-3">{{_l('theme_setting')}}:</h6>
                                            <div class="col-md-9">
                                                <p class="mb-0">Gaussian Texture</p>
                                                <hr>
                                                <ul class="switcher">
                                                    <template v-for="theme in themeGaussian">
                                                        <li class="pointer" :id="theme.name" @click="changeTheme(theme)"></li>
                                                    </template>
                                                </ul>
                                                <p class="mb-0">Gradient Background</p>
                                                <hr>
                                                <ul class="switcher">
                                                    <template v-for="theme in themeGradient">
                                                        <li class="pointer" :id="theme.name" @click="changeTheme(theme)"></li>
                                                    </template>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
