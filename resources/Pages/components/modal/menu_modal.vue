<template>
  <div v-if="show" class="w-screen h-screen bg-black opacity-40 absolute z-40"></div>
  <div v-if="show" class="absolute w-screen h-screen flex flex-col items-center justify-center z-40 gap-3 px-4">
    <div class="bg-yellow-300 border-yellow-500 border-2 rounded-lg px-4 py-4">
      <slot></slot>
    </div>
    <div v-on:click="hideModal" class="rounded-full bg-yellow-300 border-yellow-500 border-2 p-1">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </div>
  </div>
</template>

<script>
import menu_button from "../menu/menu_button.vue";
export default {
  name: "menu_modal",
  emits:['updateModal'],
  components: {
    menu_button
  },
  props: {
    show: Boolean,
    type: String,
  },
  methods: {
    hideModal() {
      console.log('hideModal');
      this.$emit('updateModal', this.type, false);
    }
  }
}
</script>
