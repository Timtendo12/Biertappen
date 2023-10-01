<template>
  <div class="flex flex-row justify-center">
    <button class="rounded-xl font-dklongreach bg-yellow-300 active:bg-yellow-500 border-yellow-500 active:border-yellow-700 border-2 px-8 py-4 text-lg w-fit font-bold transition-all">{{text}}</button>
  </div>
</template>

<script>
export default {
  name: "menu_button",
  props: {
    text: String,
  }
}
</script>
