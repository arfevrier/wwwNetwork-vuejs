<template>
  <v-card
  class="mx-auto"
  >
  <v-card-item :style="`background-color: ${color}; color: white`">
      {{ title }}
  </v-card-item>

    <v-divider></v-divider>

    <v-card-text v-if="loading">
      <v-progress-circular
        indeterminate
        color="black"
        :size="24"
      ></v-progress-circular>
    </v-card-text>
    <v-card-text v-html="text" v-if="!loading">

    </v-card-text>
  </v-card>
</template>

<script>
  export default {
    props: ['title','text','size','color','loading'],
  }
</script>