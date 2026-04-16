<script setup>
    import { computed } from 'vue'

    const props = defineProps({
        forecastData: {
            type: Object,
            default: () => ({})
        }
    });

    // 📅 Week Day
    const weekDay = (dateStr) => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { weekday: 'short' })
    };

    // 📅 Month Day
    const monthDay = (dateStr) => {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    };

    // 🌦️ Weather Icon
    const weatherIcon = (iconId) => {
        return `https://www.accuweather.com/images/weathericons/v2a/${iconId}.svg`
    }
</script>

<template>
    <div class="card" v-if="forecastData?.DailyForecasts">
        <div class="card-header">
            <h1 class="text-start"
                style="font-size: 1.1rem; border-bottom: 1px solid rgb(224 224 224); padding-bottom: 6px; text-transform: uppercase;">

                {{ forecastData.DailyForecasts.length }} Day weather forecast
            </h1>
        </div>

        <div class="card-body">
            <div
                    v-for="(forecast, idx) in forecastData.DailyForecasts"
                    :key="idx"
                    class="d-flex justify-content-between"
                    style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid rgb(224 224 224);"
            >
                <!-- Day -->
                <div style="width: 20%;">
                    <h3 class="m-0" style="font-size: 1rem; text-transform: uppercase;">
                        {{ idx === 0 ? 'TODAY' : weekDay(forecast.Date) }}
                    </h3>
                    <p class="m-0">{{ monthDay(forecast.Date) }}</p>
                </div>

                <!-- Day Icon -->
                <div style="width: 10%;">
                    <img :src="weatherIcon(forecast.Day.Icon)" width="36" height="36" />
                </div>

                <!-- Temp -->
                <div class="text-start" style="width: 20%;">
                    <span style="font-size: 1.2rem; font-weight: bold;">
                        {{ forecast.Temperature.Maximum.Value }}°
                    </span>
                    <span style="font-size: .8rem;">
                        {{ forecast.Temperature.Minimum.Value }}°
                    </span>
                </div>

                <!-- Description -->
                <div style="width: 40%;">
                    <p class="text-start"
                       style="margin-bottom: 4px; font-weight: bold;"
                       :title="forecast.Day.LongPhrase">
                        {{ forecast.Day.IconPhrase }}
                    </p>

                    <div class="d-flex" style="gap: 10px; color: #888;">
                        <img :src="weatherIcon(forecast.Night.Icon)" width="18" height="18"
                             :title="forecast.Night.LongPhrase" />

                        <span v-if="idx === 0">Night:</span>
                        <span>{{ forecast.Night.IconPhrase }}</span>
                    </div>
                </div>

                <!-- Rain -->
                <div class="d-flex align-items-center">
                    <p class="d-flex" style="margin: 0;">
                        <span style="width: 10px; height: 10px; margin-right: 4px;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 16">
                                <path fill="none" stroke="#878787" stroke-width=".714"
                                      d="M5.532.891c1.723.952 5.315 5.477 5.775 8.756..." />
                            </svg>
                        </span>
                        <span>{{ forecast.Day.PrecipitationProbability ?? 0 }}%</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>