<script setup>
const props = defineProps(['body'])
</script>

<template>
    <div class="flex gap-2 flex-col">
        <div class="grid grid-cols-2 gap-2">
            <input type="text" v-model="body.name" class="input" placeholder="Name" />
            <input type="text" v-model="body.address" class="input" placeholder="Address" />
            <input type="text" v-model="body.latitude" class="input" placeholder="Latitude" />
            <input type="text" v-model="body.longitude" class="input" placeholder="Longitude" />
        </div>
    </div>
</template>
