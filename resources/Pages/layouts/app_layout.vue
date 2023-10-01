<template>
<div>
  <h1 class="absolute top-1 opacity-30 bg-white text-black text-lg z-50">ISPWA: {{isPWa}} | isMobile: {{isMobile}} | <span class="text-xs"><br>csrf: {{csrf}}</span></h1>
  <slot></slot>
</div>
</template>

<script>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { UAParser } from 'ua-parser-js'
export default {
  name: "app_layout",
  setup() {
    const parser = new UAParser();
    const result = parser.getResult();
    let isPWa = false;
    let isMobile = false;
    const page = usePage()
    const csrf = computed(() => page.props.csrf)

    // check if on pwa
    if (window.matchMedia('(display-mode: standalone)').matches) {
      isPWa = true;
    }

    // check if on mobile
    if (result.device.type === 'mobile') {
      isMobile = true;
    }
    localStorage.setItem('device', JSON.stringify(result));
    return { isPWa, isMobile, csrf}
  }
}
</script>
