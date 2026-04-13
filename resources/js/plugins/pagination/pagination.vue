<template>
    <div class="pagination justify-content-end mt-3">
        <div class="dataTables_paginate paging_simple_numbers" v-if="data.data !== undefined">
            <ul class="pagination" v-if="shouldShowPaginate" :class="[useStyle === 'default' ? 'default-style' : '', listClass, contentAlignClass, customAlign]">
                <li v-if="showNextPrev" :class="[listItemClass, !hasPrevious ? (disableClassIn === 'li' ? disableClass : '') : '']">
                    <a :class="[linkClass, !hasPrevious ? (disableClassIn === 'a' ? disableClass : '') : '']" href="#" @click.prevent="paginateTo(previousPage)">
                        {{ previousText }}
                    </a>
                </li>
                <li v-for="(_page, index) in pages" :key="index" :class="[listItemClass, activeClassIn === 'li' ? (isActive(_page) ? activeClass : '') : '', isDot(_page) ? disableClass : '']">
                    <a :class="[linkClass, activeClassIn === 'a' ? (isActive(_page) ? activeClass : '') : '']" href="#" @click.prevent="paginateTo(_page)">
                        {{ _page }}
                    </a>
                </li>
                <li v-if="showNextPrev" :class="[listItemClass, !hasNext ? (disableClassIn === 'li' ? disableClass : '') : '']">
                    <a :class="[linkClass, !hasNext ? (disableClassIn === 'a' ? disableClass : '') : '']" href="#" @click.prevent="paginateTo(nextPage)">
                        {{ nextText }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
    import { computed, toRefs } from "vue";

    export default {
        name: "Pagination",
        props: {
            data: { type: [Object, Array], required: true },
            onEachSide: { type: Number, default: 3 },
            showNextPrev: { type: Boolean, default: true },
            dots: { type: String, default: "..." },
            useStyle: { type: String, default: "default", validator: value => ["default", "bootstrap", "custom"].includes(value) },
            alignment: { type: String, default: null, validator: value => [null, "left", "center", "right"].includes(value) },
            activeClass: { type: String, default: "active" },
            activeClassIn: { type: String, default: "li", validator: value => ["li", "a"].includes(value) },
            listClass: { type: String, default: "pagination" },
            listItemClass: { type: String, default: "paginate_button page-item" },
            contentAlignClass: { type: String, default: "" },
            linkClass: { type: String, default: "page-link" },
            disableClass: { type: String, default: "disabled" },
            disableClassIn: { type: String, default: "li", validator: value => ["li", "a"].includes(value) },
            previousText: { type: String, default: "Previous" },
            nextText: { type: String, default: "Next" },
            autoHidePaginate: { type: Boolean, default: true }
        },
        setup(props, { emit }) {
            const { data, onEachSide, dots, activeClassIn, activeClass, disableClassIn, disableClass, autoHidePaginate, alignment } = toRefs(props);

            const isResourceApi = computed(() => !!data.value.meta);
            const currentPage = computed(() => isResourceApi.value ? data.value.meta.current_page : data.value.current_page);
            const totalPage = computed(() => isResourceApi.value ? data.value.meta.last_page : data.value.last_page);

            const hasPrevious = computed(() => currentPage.value > 1);
            const previousPage = computed(() => currentPage.value - 1);
            const nextPage = computed(() => currentPage.value + 1);
            const hasNext = computed(() => nextPage.value <= totalPage.value);

            const shouldShowPaginate = computed(() => {
                if (!data.value.data) return false;
                return totalPage.value === 1 ? !autoHidePaginate.value : true;
            });

            const pages = computed(() => {
                if (onEachSide.value <= -1) return totalPage.value;
                const pagesArr = [];
                for (let i = 1; i <= totalPage.value; i++) {
                    if (i === 1 || (currentPage.value - onEachSide.value <= i && currentPage.value + onEachSide.value >= i) || i === currentPage.value || i === totalPage.value) {
                        pagesArr.push(i);
                    } else if (i === currentPage.value - (onEachSide.value + 1) || i === currentPage.value + (onEachSide.value + 1)) {
                        pagesArr.push(dots.value);
                    }
                }
                return pagesArr;
            });

            const customAlign = computed(() => {
                if (alignment.value === "left") return "default-left";
                if (alignment.value === "center") return "default-center";
                if (alignment.value === "right") return "default-right";
                return "";
            });

            const isActive = (pageNumber) => pageNumber === currentPage.value;
            const isDot = (content) => content === dots.value;
            const paginateTo = (pageNumber) => {
                if (pageNumber < 1 || pageNumber > totalPage.value) return;
                emit("paginateTo", {page:pageNumber});
            };

            return { isResourceApi, currentPage, totalPage, hasPrevious, previousPage, nextPage, hasNext, shouldShowPaginate, pages, customAlign, isActive, isDot, paginateTo };
        }
    };
</script>

