<script setup>
    import { computed } from 'vue'
    import { useHttp, useBase, appStore } from "@/lib";
    const { formFilter, pageDependencies, _l } = {
        ...useBase(),
        ...appStore(),
        ...useHttp(),
        ...appStore().useGetters("pageDependencies")
    };
    const {getDataList,urlGenerate} = useHttp();
    const props = defineProps({
        weatherData: {
            type: Object,
            default: () => ({})
        }
    });
    const recordedAt = computed(() => {
        if (!props.weatherData.recorded_at) return '';
        const date = new Date(props.weatherData.recorded_at);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric'
        })
    });
    const humidityText = computed(() => {
        const val = props.weatherData.indoor_relative_humidity;

        if (val >= 90) return ' (Very High)';
        if (val >= 60) return ' (High)';
        if (val < 30) return ' (Low)';
        return ''
    });
    const weatherIcon = computed(() => {
        return `https://www.accuweather.com/images/weathericons/v2a/${props.weatherData.weather_icon}.svg`
    })
</script>

<template>
    <div class="card weather-card mx-auto">

        <div class="card-header spaced-content text-center text-md-start">
            <h6 class="mb-1">{{ _l('ukhiya,_right_now') }}</h6>
            <p class="sub mb-0 small">{{ recordedAt }}</p>
        </div>

        <div class="card-body">

            <!-- TOP SECTION -->
            <div class="row mb-3">

                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <img class="icon me-2" :src="weatherIcon" width="48" height="48">

                        <div>
                            <div class="display-temp">
                                {{ weatherData.temperature }}°
                                <span class="sub">C</span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center text-md-start mt-2 weather-text">
                        {{ weatherData.weather_text }}
                    </div>
                </div>

                <div class="col-12 col-md-6 text-center text-md-start">
                    <div>Real Feel {{ weatherData.real_feel_temperature }}°</div>
                    <div>Real Feel Shade {{ weatherData.real_feel_shade_temperature }}°</div>
                </div>

            </div>

            <!-- DETAILS -->
            <div class="row">

                <!-- LEFT -->
                <div class="col-12 col-md-6 mb-2">
                    <div class="d-flex justify-content-between"><span>Max UV</span><span>{{weatherData.uv_index}} {{ weatherData.uv_index_text ? `(${weatherData.uv_index_text})` : '' }}</span></div>
                    <div class="d-flex justify-content-between"><span>Wind</span><span>{{weatherData.wind_direction_text}} {{weatherData.wind}} km/h</span></div>
                    <div class="d-flex justify-content-between"><span>Wind Gusts</span><span>{{ weatherData.wind_gust }} km/h</span></div>
                    <div class="d-flex justify-content-between"><span>Humidity</span><span>{{weatherData.relative_humidity}}%</span></div>
                    <div class="d-flex justify-content-between"><span>Indoor</span><span>{{weatherData.indoor_relative_humidity}}%{{ humidityText }}</span></div>
                </div>

                <!-- RIGHT -->
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-between"><span>Dew Point</span><span>{{weatherData.dew_point}}°C</span></div>
                    <div class="d-flex justify-content-between"><span>Pressure</span><span>{{ weatherData.pressure_tendancy_code }} {{weatherData.pressure}} mb</span></div>
                    <div class="d-flex justify-content-between"><span>Cloud</span><span>{{weatherData.cloud_cover}}%</span></div>
                    <div class="d-flex justify-content-between"><span>Visibility</span><span>{{weatherData.visibility}} km</span></div>
                    <div class="d-flex justify-content-between"><span>Ceiling</span><span>{{weatherData.ceiling}} m</span></div>
                </div>

            </div>

        </div>
    </div>
</template>
<style scoped>

    .weather-card {
        width: 100%;
        max-width: 500px;
    }

    .card-body {
        overflow-x: hidden;
    }

    .display-temp {
        font-size: 1.8rem;
    }

    .weather-text {
        font-size: 1rem;
    }

    @media (max-width: 576px) {
        .display-temp {
            font-size: 1.5rem;
        }

        .weather-text {
            font-size: 0.9rem;
        }
    }

</style>