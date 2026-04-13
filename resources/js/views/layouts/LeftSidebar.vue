<script setup>
import { useStore } from 'vuex';
import { ref, onMounted, nextTick, watch, defineProps, defineEmits } from 'vue';
import { useBase, useHttp, appStore } from '@/lib';
const { _l, getImage, allMenus, loadConfigurations, useGetters } = { ...useBase(), ...appStore(), ...useHttp() };
const { Config, appConfigs } = useGetters('Config', 'appConfigs');

const props = defineProps({
    isVisible: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close-sidebar']);

const store = useStore();
const menu_keyword = ref('');
const menus = ref([]);
const isSearchFocused = ref(false);

watch(() => props.isVisible, (newVal) => {
    if (newVal) {
        $(".wrapper").addClass("toggled");
    } else {
        $(".wrapper").removeClass("toggled");
    }
});

onMounted(() => {
    loadConfigurations({
        callback: async (retData) => {
            await nextTick(() => {
                $("#menu").metisMenu();

                let currentUrl = window.location.href;
                let activeItem = $(".metismenu li a").filter(function () {
                    return this.href === currentUrl;
                });
                if (activeItem.length) {
                    let li = activeItem.parent().addClass("mm-show");

                    while (li.length) {
                        let parentUl = li.parent();
                        if (parentUl.hasClass("metismenu")) break;

                        parentUl.addClass("mm-show");
                        dd(parentUl);
                        li = parentUl.parent().addClass("mm-active");
                    }
                }
            });
        }
    });
});

onMounted(() => {
    $(".mobile-toggle-menu").on("click", function () {
        $(".wrapper").addClass("toggled");
    });

    $(".toggle-icon").on("click", function () {
        if ($(".wrapper").hasClass("toggled")) {
            $(".wrapper").removeClass("toggled");
            $(".sidebar-wrapper").unbind("hover");
            emit('close-sidebar');
        } else {
            $(".wrapper").addClass("toggled");
            $(".sidebar-wrapper").hover(
                function () {
                    $(".wrapper").addClass("sidebar-hovered");
                },
                function () {
                    $(".wrapper").removeClass("sidebar-hovered");
                }
            );
        }
    });

    $(".overlay").on("click", function () {
        $(".wrapper").removeClass("toggled");
        emit('close-sidebar');
    });

    $(window).on("scroll", function () {
        $(this).scrollTop() > 300
            ? $(".back-to-top").fadeIn()
            : $(".back-to-top").fadeOut();
    });

    $(".back-to-top").on("click", function () {
        $("html, body").animate({ scrollTop: 0 }, 600);
        return false;
    });
});

const clearSearch = () => {
    menu_keyword.value = '';
    isSearchFocused.value = false;
};

const handleBlur = () => {
    setTimeout(() => {
        if (!menu_keyword.value) {
            isSearchFocused.value = false;
        }
    }, 150);
};

const icon = (icon) => {
    return icon ? icon : 'bx bx-home-alt';
};

const addMenu = (menu) => {
    menus.value.push({
        display_name: menu.display_name,
        id: menu.id,
        link: menu.link,
        name: menu.name,
        status: menu.status,
        submenus: [],
    });
};

watch(menu_keyword, (newVal) => {
    menus.value = [];
    if (newVal !== '') {
        allMenus.value.forEach(pMenu => {
            addMenu(pMenu);
            pMenu.submenus?.forEach(sMenu => {
                addMenu(sMenu);
                sMenu.submenus?.forEach(ssMenu => {
                    addMenu(ssMenu);
                });
            });
        });

        const foundValue = menus.value.filter(eachData => {
            const searchableText = (eachData.display_name || eachData.name || '').toString().toLowerCase();
            return searchableText.includes(newVal.toLowerCase());
        });

        store.commit('allMenus', foundValue);
    } else {
        store.commit('allMenus', Config.value.menus);
    }
});

import { useRouter } from 'vue-router';
const router = useRouter();
router.afterEach(() => {
    emit('close-sidebar');
});
</script>


<template>
    <div class="sidebar-wrapper" :class="{ 'active': isVisible }" data-simplebar="true">
        <div class="sidebar-header">
            <div class="logo_wrapper">
                <img :src="appConfigs.app_logo" class="logo-icon" alt="logo icon">
            </div>
            <div class="toggle-icon ms-auto" @click="emit('close-sidebar')">
                <i class='bx bx-arrow-back'></i>
            </div>
        </div>
        <div class="search-bar flex-grow-1">
            <div class="position-relative search-bar-box">
                <input v-model="menu_keyword" type="text" @focus="isSearchFocused = true"
        @blur="handleBlur" class="form-control search-control" placeholder="Type to search...">
        <span v-show="!menu_keyword && !isSearchFocused" class="position-absolute top-50 search-show translate-middle-y"><i class='bx bx-search'></i></span>
                <span  v-show="menu_keyword || isSearchFocused" @click="clearSearch" class="position-absolute top-50 search-show translate-middle-y cursor-pointer"><i class='bx bx-x'></i></span>
            </div>
        </div>
        <ul class="metismenu" id="menu">
            <template v-for="(mainMenu, mainIndex) in allMenus">
                <li v-if="mainMenu.submenus.length > 0">
                    <router-link class="has-arrow" :to="mainMenu.link">
                        <div class="parent-icon"><i :class="icon(mainMenu.icon)"></i></div>
                        <div class="menu-title">{{_l(mainMenu.name)}}</div>
                    </router-link>
                    <ul>
                        <template v-for="(sub2Menu, sub2Index) in mainMenu.submenus">
                            <li v-if="sub2Menu.submenus.length > 0">
                                <router-link class="has-arrow" :to="sub2Menu.link">
                                    <i :class="icon(sub2Menu.icon)"></i>
                                    <span>{{_l(sub2Menu.name)}}</span>
                                </router-link>
                                <ul>
                                    <template v-for="(sub3Menu, sub3Index) in sub2Menu.submenus">
                                        <li v-if="sub3Menu.submenus.length > 0">
                                            <router-link class="has-arrow" :to="sub3Menu.link">
                                                <i :class="icon(sub3Menu.icon)"></i>{{_l(sub3Menu.name)}}</router-link>
                                            <ul>
                                                <template v-for="(sub4Menu, sub4Index) in sub3Menu.submenus">
                                                    <li>
                                                        <router-link :to="sub4Menu.link">
                                                            <i :class="icon(sub4Menu.icon)"></i>
                                                            {{_l(sub4Menu.name)}}
                                                        </router-link>
                                                    </li>
                                                </template>
                                            </ul>
                                        </li>
                                        <li v-else>
                                            <router-link :to="sub3Menu.link">
                                                <i :class="icon(sub3Menu.icon)"></i>
                                                {{_l(sub3Menu.name)}}
                                            </router-link>
                                        </li>
                                    </template>
                                </ul>
                            </li>
                            <li v-else>
                                <router-link :to="sub2Menu.link">
                                    <i :class="icon(sub2Menu.icon)"></i>
                                    {{_l(sub2Menu.name)}}
                                </router-link>
                            </li>
                        </template>
                    </ul>
                </li>
                <li v-else>
                    <router-link :to="mainMenu.link">
                        <div class="parent-icon">
                            <i :class='icon(mainMenu.icon)'></i>
                        </div>
                        <div class="menu-title">{{_l(mainMenu.name)}}</div>
                    </router-link>
                </li>
            </template>
        </ul>
    </div>
</template>
