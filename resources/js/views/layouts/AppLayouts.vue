<template>
    <div>
        <div class="flex justify-center items-center h-full">
            <div class="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-green-500"></div>
        </div>
        <div class="wrapper">
            <LeftSidebar :is-visible="isSidebarVisible" @close-sidebar="closeSidebar" />
            <Header @toggle-sidebar="toggleSidebar" />
            <router-view />
            <div class="overlay toggle-icon"></div>
            <a href="#" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
            <appFooter />
        </div>
    </div>
</template>

<script setup>
import Header from "@/views/layouts/Header.vue";
import LeftSidebar from "@/views/layouts/LeftSidebar.vue";
import appFooter from "@/views/layouts/appFooter.vue";
import themeSwitcher from "@/views/layouts/themeSwitcher.vue";
import { useRouter } from 'vue-router'
import {ref} from 'vue';

const isSidebarVisible = ref(false)
const toggleSidebar = () => {
    isSidebarVisible.value = !isSidebarVisible.value
}

const closeSidebar = () => {
    isSidebarVisible.value = false
}

const router = useRouter()
router.afterEach(() => {
    closeSidebar()
})

</script>

<style scoped>
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    display: none;
    transition: all 0.3s ease;
}

.overlay.active {
    display: block;
}

/* Mobile responsive styles */
@media (max-width: 767px) {
    .overlay.active {
        display: block;
    }
}

@media (min-width: 768px) {
    .overlay {
        display: none !important;
    }
}
</style>
