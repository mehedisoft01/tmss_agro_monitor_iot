<script setup>
    import axios from 'axios'
    import { ref, onMounted, onBeforeUnmount } from 'vue';
    import { useRouter, useRoute } from 'vue-router';
    import { useBase, useHttp, appStore } from '@/lib';
    import { useI18n } from 'vue-i18n'
    const { t, locale } = useI18n();
    const user = ref(null);
    const router = useRouter();
    const route = useRoute()

    const {_l,  getImage,httpReq, formatDate, useGetters, urlGenerate, submitForm, assignStore } = {...useBase(), ...useHttp(), ...appStore()};
    const {localization, authUser, appNotifications, appConfigs} = useGetters('localization', 'authUser','appNotifications', 'appConfigs');

    let dfLocale = ref(window.locale || 'en');
    const switchLang = (lang) => {
        submitForm({
            data : {request:'locale',locale:lang},
            url : `api/profile`,
            validation : false,
            callback : function(retData){
                locale.value = lang;
                dfLocale.value = lang;
            }
        });
    };

    const unreadCount = ref(0);
    const notifications = ref([]);
    const showDropdown = ref(false);
    const pollingInterval = ref(null);


    const toggleNotifications = () => {
        showDropdown.value = !showDropdown.value;

        if (showDropdown.value) {
            fetchNotificationsList();
        }
    };

    const fetchUnreadCount = () => {
        axios.get('/api/notifications/unread_count')
            .then(res => {
                unreadCount.value = res.data.result;
            })
            .catch(err => console.error(err));
    };

    const fetchNotificationsList = () => {
        axios.get('/api/notification_alerts', {
            params: { is_read: 0 }
        })
            .then(res => {
                notifications.value = res.data.result.data;
            })
            .catch(err => console.error(err));
    };

    const markAsRead = (id) => {
        axios.post(`/api/notifications/${id}/read`)
            .then(() => {
                notifications.value = notifications.value.filter(n => n.id !== id);
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            })
            .catch(err => console.error(err));
    };

    const features = ref([]);
    const offcanvasInstance = ref(null);

    const loadFeatures = async () => {
        try {
            const res = await axios.get('/api/features');
            if (res.data && typeof res.data === 'object' && !Array.isArray(res.data)) {
                features.value = Object.values(res.data);
            } else {
                features.value = res.data || [];
            }
            console.log("Loaded Features:", features.value);
        } catch (err) {
            console.error('Features লোড করতে সমস্যা হয়েছে:', err);
        }
    };
    const openModal = () => {
        const el = document.getElementById('featureModal');
        if (!offcanvasInstance.value && window.bootstrap) {
            offcanvasInstance.value = new window.bootstrap.Offcanvas(el, {
                backdrop: true,
                keyboard: true,
                scroll: false
            });
        }
        if (offcanvasInstance.value) {
            offcanvasInstance.value.show();
        }
    };
    const formatTitle = (title) => {
        return (title || '').replace(/_/g, ' ');
    };

    const selectFeature = async (id) => {
        try {
            await axios.post('/api/select-feature', { feature_id: id });
            if (offcanvasInstance.value) {
                offcanvasInstance.value.hide();
            }
            window.location.href = '/admin/dashboard';
        } catch (err) {
            console.error(err);
        }
    };
    onMounted(() => {
        fetchUnreadCount();
        loadFeatures();

        pollingInterval.value = setInterval(fetchUnreadCount, 5000);
    });

    onBeforeUnmount(() => {
        clearInterval(pollingInterval.value);
    });


    const emit = defineEmits(['toggle-sidebar'])

const toggleSidebar = () => {
    emit('toggle-sidebar')
}


</script>

<style scoped>
.mobile-toggle-menu {
    cursor: pointer;
}
.msg-info {
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
}



@media (min-width: 768px) {
    .mobile-toggle-menu {
        display: none;
    }
}
</style>

<template>
    <header>
        <div class="topbar d-flex align-items-center">
            <nav class="navbar navbar-expand gap-3">
                <div class="mobile-toggle-menu" @click="toggleSidebar" ><i class='bx bx-menu'></i>
                </div>
                <div class="search-bar flex-grow-1">
                    <div class="position-relative search-bar-box">
                        <strong class="uppercase"><i class="bx bx-home-alt"></i> {{_l('home')}} / </strong>
                        <strong class="uppercase">{{_l(route.name)}}</strong>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <a class="btn" @click="openModal" style="border: 1px solid #6c757d !important;">
                        Panel
                    </a>
                </div>

                <div class="top-menu ms-auto">
                    <ul class="navbar-nav align-items-center gap-1">
                        <li class="nav-item mobile-search-icon d-flex d-lg-none" data-bs-toggle="modal" data-bs-target="#SearchModal">
                            <a class="nav-link" href="#"><i class='bx bx-search'></i>
                            </a>
                        </li>
                        <li class="nav-item dropdown dropdown-laungauge d-none d-sm-flex">
                            <a class="nav-link dropdown-toggle dropdown-toggle-nocaret text-uppercase" href="#" data-bs-toggle="dropdown">
                               <code> {{localization.find(l => l.locale === dfLocale)?.locale}}</code>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <template v-for="(locale, lIndex) in localization">
                                    <li @click="switchLang(locale.locale)">
                                        <a class="dropdown-item d-flex align-items-center py-2" href="#">
                                            <span class="ms-2 text-uppercase">{{locale.name}}</span>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </li>
                        <div class="position-relative dropdown notification-wrapper">

                            <button class="btn btn-icon" @click.stop="toggleNotifications">
                                <i class="bx bx-bell fs-18"></i>
                                <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</span>
                            </button>

                            <div v-if="showDropdown" class="dropdown-menu notification-dropdown show" @click.stop>

                                <div class="notification-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Notifications</h6>
                                    <small class="">{{ unreadCount }} New</small>
                                </div>

                                <div class="notification-body">
                                    <div v-if="notifications.length === 0" class="empty-state">No notifications</div>

                                    <div v-for="notif in notifications" :key="notif.id" class="notification-card">
                                        <div class="d-flex align-items-start">
                                            <div class="notification-icon">⚠️</div>

                                            <div class="flex-grow-1">
                                                <div class="notification-text">
                                                    {{ notif.message }}
                                                </div>

                                                <div class="notification-meta">
                                                    Value: {{ notif.current_value }}
                                                    ({{ notif.min_value }} - {{ notif.max_value }})
                                                </div>
                                            </div>

                                            <button class="btn btn-sm btn-success ms-2" @click="markAsRead(notif.id)">✓</button>
                                        </div>
                                    </div>

                                    <div v-if="notifications.length > 0" class="text-center mt-2">
                                        <a href="/admin/notification_alerts" class="btn btn-sm btn-primary w-100">
                                            View All Notifications
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </ul>
                </div>
                <div class="user-box dropdown px-3">
                    <a class="d-flex align-items-center nav-link dropdown-toggle gap-3 dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img :src="getImage(authUser.image, 'backend/images/avatars/avatar-26.png')" class="user-img" alt="user avatar">
                        <div class="user-info">
                            <p class="user-name mb-0">{{authUser.name}}</p>
                            <p class="designattion mb-0">{{authUser.designation}}</p>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <router-link class="dropdown-item d-flex align-items-center" to="/profile"><i class="bx bx-user fs-5"></i><span>Profile</span></router-link>
                        </li>
<!--                        <li>-->
<!--                            <router-link class="dropdown-item d-flex align-items-center" to="/app_settings">-->
<!--                                <i class="bx bx-cog fs-5"></i><span>Settings</span>-->
<!--                            </router-link>-->
<!--                        </li>-->
                        <li>
                            <router-link class="dropdown-item d-flex align-items-center" to="/activities"><i class="bx bx-home-circle fs-5"></i><span>Activities</span></router-link>
                        </li>
                        <li>
                            <div class="dropdown-divider mb-0"></div>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" :href="urlGenerate('logout')"><i class="bx bx-log-out-circle"></i><span>Logout</span></a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
        <div class="offcanvas offcanvas-end custom-offcanvas" tabindex="-1" id="featureModal">
            <div class="offcanvas-header custom-header">
                <div>
                    <h5 class="offcanvas-title mb-0">Select Your Software</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <hr class="m-0">
            <div class="offcanvas-body">
                <div class="row g-3">
                    <div class="col-6" v-for="feature in features" :key="feature.id">
                        <a @click="selectFeature(feature.id)" class="text-decoration-none pointer">
                            <div class="feature-card h-100 d-flex flex-column align-items-center justify-content-center">

                                <h6 class="mt-2 mb-0 text-center  text-dark">
                                    {{ formatTitle(feature.title) }} Management System
                                </h6>

                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>


<style scoped>
    .custom-offcanvas {
        width: 440px;
        max-width: 90%;
        top: 5vh;
        height: auto !important;
        max-height: 60vh;
        border-radius: 18px 0 0 18px;
        border: none;
        box-shadow: -10px 0 35px rgba(0,0,0,0.18);
        overflow: hidden;
    }
    .custom-header {
        padding: 18px 20px;
        background: linear-gradient(135deg, #0d6efd, #4f9bff);
        color: #fff;
    }
    .custom-header .btn-close {
        filter: invert(1);
        opacity: 0.9;
    }
    .offcanvas-body {
        padding: 18px;
        background: #f8f9fb;
    }
    .feature-card {
        background: #fff;
        border-radius: 14px;
        padding: 18px 12px;
        text-align: center;
        transition: all 0.25s ease;
        border: 1px solid #eef0f3;
        cursor: pointer;
    }
    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        border-color: #dbe7ff;
    }
    .custom-offcanvas .offcanvas-body {
        overflow-y: auto;
        max-height: calc(85vh - 70px);
    }
</style>