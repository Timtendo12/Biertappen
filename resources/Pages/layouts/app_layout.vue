<template>
<div>
  <h1 class="absolute top-1 opacity-30 bg-white text-black text-lg z-50">ISPWA: {{isPWA}} | isMobile: {{isMobile}} | <span class="text-xs"><br>csrf: {{csrf}}</span></h1>
  <div v-if="isPWA && isMobile">
    <slot></slot>
  </div>
  <div v-else-if="!isMobile">
    <nonMobilePage></nonMobilePage>
  </div>
  <div v-else-if="!isPWA && isMobile">
<!-- TODO: Reset this when going to production   <installPwaPromptPage></installPwaPromptPage>-->
    <slot></slot>
  </div>
</div>
</template>

<script>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { UAParser } from 'ua-parser-js'
import InstallPwaPromptPage from "../installPwaPromptPage.vue";
import NonMobilePage from "../nonMobilePage.vue";
export default {
  name: "app_layout",
  components: {NonMobilePage, InstallPwaPromptPage},
  setup() {
    const parser = new UAParser();
    const result = parser.getResult();
    let isPWA = false;
    let isMobile = false;
    const page = usePage()
    const csrf = computed(() => page.props.csrf)

    // check if on pwa
    if (window.matchMedia('(display-mode: standalone)').matches) {
      isPWA = true;
    }

    // check if on mobile
    if (result.device.type === 'mobile') {
      isMobile = true;
    }
    localStorage.setItem('device', JSON.stringify(result));
    return { isPWA, isMobile, csrf}
  }
}
</script>
