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
    <div class="card" style="width: 500px;">
        <div class="card-header spaced-content">
            <h1>{{_l('ukhiya,_right_now')}}</h1>
            <p class="sub">{{recordedAt}}</p>
        </div>

        <div class="card-body">
            <div class="row mb-2">
                <div class="col-6">
                    <div class="d-flex">
                        <img class="icon" :src="weatherIcon" width="48" height="48">
                        <div class="text-start">
                            <div class="display-temp">{{weatherData.temperature}}°<span class="sub">C</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-start mt-2 weather-text">{{weatherData.weather_text}}</div>
                </div>
                <div class="col-6">
                    <div class="text-start">
                        Real Feel {{weatherData.real_feel_temperature}}°
                    </div>
                    <div class="text-start">
                        Real Feel Shade {{weatherData.real_feel_shade_temperature}}°
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="d-flex justify-content-between">
                        <div>Max UV Index</div>
                        <div>{{weatherData.uv_index}} {{ weatherData.uv_index_text ? `(${weatherData.uv_index_text})` : '' }}</div>

                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Wind</div>
                        <div>{{weatherData.wind_direction_text}} {{weatherData.wind}} km/h</div>

                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Wind Gusts</div>
                        <div>{{ weatherData.wind_gust }} km/h</div>

                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Humidity</div>
                        <div>{{weatherData.relative_humidity}}%</div>

                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Indoor Humidity</div>
                        <div>{{weatherData.indoor_relative_humidity}}%{{ humidityText }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-between">
                        <div>Dew Point</div>
                        <div>{{weatherData.dew_point}}° C</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Pressure</div>
                        <div>{{ weatherData.pressure_tendancy_code }} {{weatherData.pressure}} mb</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Cloud Cover</div>
                        <div>{{weatherData.cloud_cover}}%</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Visibility</div>
                        <div>{{weatherData.visibility}} km</div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div>Cloud Ceiling</div>
                        <div>{{weatherData.ceiling}} m</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
    .display-temp {
        margin-left: 10px;
        font-size: 2rem;
    }
    .weather-text {
        font-size: 1.2rem;
    }
</style>